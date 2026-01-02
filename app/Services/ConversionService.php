<?php

namespace App\Services;

use App\Models\ConversionJob;
use Illuminate\Support\Facades\Storage;

class ConversionService
{
    public function __construct(
        protected FileParserService $fileParser,
        protected MappingService $mapper,
        protected XmlGeneratorService $xmlGen,
        protected CsvGeneratorService $csvGen,
        protected DominioTxtGeneratorService $txtGen
    ) {
    }

    public function process(ConversionJob $job, array $mappingRules): string
    {
        $fullPath = Storage::path($job->upload->file_path);

        // 1. Parse
        $rows = $this->fileParser->getRows($fullPath, null, $job->upload->mime_type);

        // 2. Map
        // Debug: Log headers and mapping
        $headers = array_keys($rows->first() ?? []);
        $generatedMapping = $this->mapper->getStandardMapping($headers);

        \Log::info('ConversionService: Headers and Mapping', [
            'headers' => $headers,
            'mapping' => $generatedMapping
        ]);

        // MERGE: Prefer passed rules if specific, but fallback to generated smart rules
        // If mappingRules is empty or contains defaults that don't match file, generatedMapping wins
        $finalMapping = array_replace($generatedMapping, $mappingRules);

        // Critical Fix: If passed mappingRules are just the default keys (Key=Value) which don't exist in file,
        // we should prefer the detected ones.
        // For now, let's trust the auto-detection if it found something.
        if (!empty($generatedMapping)) {
            $finalMapping = $generatedMapping;
        }

        $rpsList = $this->mapper->mapRowsToRps($rows->toArray(), $finalMapping);

        // 3. Generate XML based on xml_type
        // For NF-e (Saídas): Emitter = Company (SALAM E FILHOS), Recipient = Customer (from PDF)
        // For NFS-e (Serviço): Provider = Company, Tomador = Customer (from PDF)

        $xmlType = $job->upload->xml_type ?? 'servico';

        // Treat 'excel' as the user-selected output type
        if ($xmlType === 'excel') {
            $metaData = $job->upload->meta_data ?? [];
            if (is_array($metaData)) {
                $xmlType = $metaData['excel_output_type'] ?? 'saida';
            } else {
                $xmlType = 'saida'; // Fallback if meta_data is malformed
            }
        }

        if ($xmlType === 'saida') {
            // NF-e: Emitter is the company itself (from user input in SaaS)
            $providerInfo = [
                'cnpj' => $job->upload->provider_info['cnpj'] ?? '00000000000000',
                'razao_social' => $job->upload->provider_info['razao_social'] ?? 'EMPRESA EMITENTE LTDA',
                'inscricao_municipal' => $job->upload->provider_info['inscricao_municipal'] ?? '000000',
                'endereco' => $job->upload->provider_endereco ?? 'RUA EXEMPLO, 123',
                'bairro' => $job->upload->provider_bairro ?? 'CENTRO',
                'cep' => $job->upload->provider_cep ?? '40000-000',
                'municipio' => $job->upload->provider_municipio ?? 'Salvador',
                'uf' => $job->upload->provider_uf ?? 'BA',
                'fone' => $job->upload->provider_fone ?? '',
            ];
        } else {
            // NFS-e: Provider from upload
            // Use user provided info or default
            $providerInfo = [
                'cnpj' => $job->upload->provider_info['cnpj'] ?? '00000000000000',
                'razao_social' => $job->upload->provider_info['razao_social'] ?? 'PRESTADOR PADRAO',
                'inscricao_municipal' => $job->upload->provider_info['inscricao_municipal'] ?? '000000',
                'endereco' => $job->upload->provider_endereco ?? 'RUA EXEMPLO, 123',
                'bairro' => $job->upload->provider_bairro ?? 'CENTRO',
                'cep' => $job->upload->provider_cep ?? '40000-000',
                'municipio' => $job->upload->provider_municipio ?? 'Salvador',
                'uf' => $job->upload->provider_uf ?? 'BA',
                'fone' => $job->upload->provider_fone ?? '',
            ];
        }

        $state = $job->upload->provider_uf ?? 'BA';
        $startingNumber = $job->upload->starting_number ?? 1;

        \Log::info('ConversionService XML Generation', [
            'xml_type' => $xmlType,
            'state' => $state,
            'starting_number' => $startingNumber,
            'generator' => $xmlType === 'saida' ? 'NF-e' : 'NFS-e',
            'rps_count' => count($rpsList)
        ]);

        if ($xmlType === 'saida') {
            // NF-e generator - Batch
            $nfeGen = app(\App\Services\XmlGeneratorNFeService::class);
            $xmlContent = $nfeGen->generateBatchXml($rpsList, (string) $job->id, $providerInfo, $state, $startingNumber);
        } elseif ($xmlType === 'dominio_txt') {
            $acumulador = $job->upload->acumulador ?? '1';
            \Log::info('Generating Domínio TXT', ['upload_id' => $job->upload->id, 'acumulador_db' => $acumulador]);

            // Domínio TXT generator (Pipe Delimited) for Saídas
            $txtContent = $this->txtGen->generate($rpsList, $providerInfo, $state, (string) $job->id, $acumulador, $startingNumber ?? 0);

            // Save Result
            $fileName = 'saidas_dominio_' . $job->id . '.txt';
            $path = 'conversions/' . $fileName;
            Storage::put($path, $txtContent);

            $job->update([
                'status' => 'completed',
                'result_file_path' => $path,
                'completed_at' => now(),
            ]);

            return $path;
        } else {
            // NFS-e generator (default)
            $xmlContent = $this->xmlGen->generateBatchXml($rpsList, (string) $job->id, $providerInfo);
        }

        // 3. Generate CSV (Instead of XML)
        // $xmlType = $job->upload->xml_type ?? 'servico';
        // ... (XML logic commented out for specific CSV task)

        // \Log::info('ConversionService CSV Generation');
        // $csvContent = $this->csvGen->generateCsv($rpsList);

        // 4. Save Result
        $fileName = 'lote_rps_' . $job->id . '.xml';
        $path = 'conversions/' . $fileName;
        Storage::put($path, $xmlContent);

        $job->update([
            'status' => 'completed',
            'result_file_path' => $path,
            'completed_at' => now(),
        ]);

        return $path;
    }
}
