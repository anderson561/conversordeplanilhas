<?php

namespace App\Http\Controllers;

use App\Models\ConversionJob;
use App\Models\Mapping;
use App\Models\Upload;
use App\Services\FileParserService;
use App\Services\MappingService;
use App\Services\XmlGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConversionController extends Controller
{
    public function store(Request $request, Upload $upload)
    {
        \Log::info('ConversionController::store called', [
            'upload_id' => $upload->id,
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        if ($upload->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'mapping' => 'required|array',
            'save_template' => 'boolean',
            'template_name' => 'nullable|required_if:save_template,true|string|max:255',
        ]);

        // Save mapping if requested
        // Save mapping for history
        $mapping = Mapping::create([
            'user_id' => auth()->id(),
            'name' => 'Job ' . $upload->id . ' Mapping',
            'rules' => $request->input('mapping'),
            'is_template' => false,
        ]);
        $mappingId = $mapping->id;

        // Save as template if requested
        if ($request->boolean('save_template')) {
            \App\Models\MappingTemplate::create([
                'user_id' => auth()->id(),
                'name' => $request->input('template_name'),
                'mapping_rules' => $request->input('mapping'),
            ]);
        }

        // Create Job
        $job = ConversionJob::create([
            'upload_id' => $upload->id,
            'mapping_id' => $mappingId,
            'status' => 'processing', // For MVP, we process synchronously first to test
            'started_at' => now(),
        ]);

        \Log::info('ConversionJob created', ['job_id' => $job->id]);

        // Process immediately for MVP (Move to Queue later)
        try {
            $this->processConversion($job, $request->input('mapping'));

            \Log::info('Conversion completed successfully', ['job_id' => $job->id]);

            return redirect()->back()
                ->with('success', 'Conversão concluída com sucesso!')
                ->with('download_url', route('conversions.download', $job->id));
        } catch (\Exception $e) {
            \Log::error('Conversion failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $job->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return redirect()->back()->with('error', 'Erro na conversão: ' . $e->getMessage());
        }
    }

    private function processConversion(ConversionJob $job, array $mappingRules)
    {
        $fileParser = app(FileParserService::class);
        $mapper = app(MappingService::class);
        $xmlGen = app(XmlGeneratorService::class);

        $fullPath = Storage::path($job->upload->file_path);

        // 1. Parse
        $rows = $fileParser->getRows($fullPath, null, $job->upload->mime_type);
        \Log::info('Rows before mapping:', ['first_row' => $rows[0] ?? null, 'count' => count($rows)]);

        // 1.5 Validate Data
        $validator = app(\App\Services\DataValidationService::class);
        $allErrors = [];

        foreach ($rows as $index => $row) {
            $rowErrors = $validator->validateRow($row, $mappingRules, $index + 1); // 1-based index
            if (!empty($rowErrors)) {
                $allErrors = array_merge($allErrors, $rowErrors);
            }
        }

        if (!empty($allErrors)) {
            // Limit errors to first 10 to avoid huge messages
            $errorCount = count($allErrors);
            $displayErrors = array_slice($allErrors, 0, 10);
            $errorMessage = "Erros de validação encontrados ($errorCount):\n" . implode("\n", $displayErrors);

            if ($errorCount > 10) {
                $errorMessage .= "\n... e mais " . ($errorCount - 10) . " erros.";
            }

            throw new \Exception($errorMessage);
        }

        // 2. Map
        $rpsList = $mapper->mapRowsToRps($rows->toArray(), $mappingRules);

        // 3. Generate XML
        $providerInfo = [
            'cnpj' => $job->upload->provider_info['cnpj'] ?? '12314872000103',
            'razao_social' => $job->upload->provider_info['razao_social'] ?? 'PRESTADOR DE SERVICOS',
            'inscricao_municipal' => '000000',
            'endereco' => $job->upload->provider_endereco ?? 'RUA EXEMPLO, 123',
            'bairro' => $job->upload->provider_bairro ?? 'CENTRO',
            'cep' => $job->upload->provider_cep ?? '40000-000',
            'municipio' => $job->upload->provider_municipio ?? 'Salvador',
            'uf' => $job->upload->provider_uf ?? 'BA',
            'fone' => $job->upload->provider_fone ?? '',
        ];

        // Choose generator based on xml_type
        $xmlType = $job->upload->xml_type ?? 'servico';
        $state = $job->upload->provider_uf ?? 'BA';
        $startingNumber = $job->upload->starting_number ?? 1;

        \Log::info('XML Generation', [
            'xml_type' => $xmlType,
            'state' => $state,
            'starting_number' => $startingNumber,
            'generator' => $xmlType === 'saida' ? 'NF-e' : 'NFS-e'
        ]);

        if ($xmlType === 'saida') {
            // NF-e generator
            $nfeGen = app(\App\Services\XmlGeneratorNFeService::class);
            $xmlContent = $nfeGen->generateBatchXml($rpsList, (string) $job->id, $providerInfo, $state, $startingNumber);
        } else {
            // NFS-e generator (default)
            $xmlGen = app(XmlGeneratorService::class);
            $xmlContent = $xmlGen->generateBatchXml($rpsList, (string) $job->id, $providerInfo);
        }

        // 4. Save Result
        $fileName = 'lote_rps_' . $job->id . '.xml';
        $path = 'conversions/' . $fileName;
        Storage::put($path, $xmlContent);

        $job->update([
            'status' => 'completed',
            'result_file_path' => $path,
            'completed_at' => now(),
        ]);
    }

    public function download(ConversionJob $job)
    {
        if ($job->upload->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$job->result_file_path || !Storage::exists($job->result_file_path)) {
            abort(404);
        }

        return Storage::download($job->result_file_path);
    }
}
