<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\FileParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UploadController extends Controller
{
    public function index()
    {
        return Inertia::render('Upload/Index', [
            'uploads' => Upload::with('latestConversionJob')
                ->where('user_id', auth()->id())
                ->latest()
                ->get()
        ]);
    }

    public function store(Request $request, FileParserService $fileParser, \App\Services\MappingService $mapper, \App\Services\ConversionService $converter)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,pdf|max:10240', // 10MB max
            'overwrite' => 'nullable|boolean',
            'provider_cnpj' => 'required|string|max:18',
            'provider_razao_social' => 'required|string|max:255',
            'xml_type' => 'required|in:servico,saida,excel,dominio_txt',
            'excel_output_type' => 'nullable|in:servico,saida',
            'provider_endereco' => 'nullable|string|max:255',
            'provider_bairro' => 'nullable|string|max:100',
            'provider_cep' => 'nullable|string|max:10',
            'provider_municipio' => 'nullable|string|max:100',
            'provider_uf' => 'nullable|string|size:2',
            'provider_fone' => 'nullable|string|max:20',
            'starting_number' => 'nullable|integer|min:1',
        ]);

        \Log::info('Upload Request Data', [
            'xml_type' => $request->input('xml_type'),
            'acumulador_input' => $request->input('acumulador'),
            'all_inputs' => $request->all()
        ]);

        $file = $request->file('file');

        // Check for duplicate
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Increase limits for Large Excel files
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // Check for duplicate
        $existingUpload = Upload::where('user_id', auth()->id())
            ->where('file_hash', $fileHash)
            ->first();

        if ($existingUpload && !$request->boolean('overwrite')) {
            return redirect()->route('uploads.index')
                ->with('duplicate_file', [
                    'hash' => $fileHash,
                    'name' => $file->getClientOriginalName(),
                    'existing' => $existingUpload->original_name,
                    'uploaded_at' => $existingUpload->created_at->format('d/m/Y H:i'),
                ]);
        }

        // If overwriting, delete old upload and its conversions
        if ($existingUpload && $request->boolean('overwrite')) {
            Storage::delete($existingUpload->file_path);
            foreach ($existingUpload->conversionJobs as $job) {
                if ($job->result_file_path) {
                    Storage::delete($job->result_file_path);
                }
            }
            $existingUpload->delete();
        }

        $path = $file->store('uploads');

        $mimeType = $file->getMimeType();
        if (strtolower($file->getClientOriginalExtension()) === 'pdf') {
            $mimeType = 'application/pdf';
        }

        \Log::info('Upload Creation Debug', [
            'xml_type' => $request->input('xml_type'),
            'provider_uf' => $request->input('provider_uf'),
            'starting_number' => $request->input('starting_number'),
        ]);

        $upload = Upload::create([
            'user_id' => auth()->id(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'xml_type' => $request->input('xml_type', 'servico'),
            'provider_endereco' => $request->input('provider_endereco'),
            'provider_bairro' => $request->input('provider_bairro'),
            'provider_cep' => $request->input('provider_cep'),
            'provider_municipio' => $request->input('provider_municipio'),
            'provider_uf' => $request->input('provider_uf'),
            'provider_fone' => $request->input('provider_fone'),
            'starting_number' => $request->input('starting_number'),
            'acumulador' => $request->input('acumulador', '1'), // Default 1
            'size_bytes' => $file->getSize(),
            'file_hash' => $fileHash,
            'status' => 'pending',
            'provider_info' => [
                'cnpj' => $request->input('provider_cnpj'),
                'razao_social' => $request->input('provider_razao_social'),
                'inscricao_municipal' => '000000', // Default value
            ],
            'meta_data' => [
                'excel_output_type' => $request->input('excel_output_type', 'saida'),
            ],
        ]);

        try {
            // Create Conversion Job record (DB model)
            $job = \App\Models\ConversionJob::create([
                'upload_id' => $upload->id,
                'status' => 'pending',
            ]);

            // Dispatch background task
            \App\Jobs\ProcessConversionJob::dispatch($upload, $job);

            return redirect()->route('uploads.index')
                ->with('success', 'Arquivo enviado com sucesso! O processamento iniciou em segundo plano.');

        } catch (\Throwable $e) {
            \Log::error('Upload failed: ' . $e->getMessage());
            if (isset($upload)) {
                $upload->update(['status' => 'failed']);
            }

            return redirect()->route('uploads.index')
                ->with('error', 'Erro ao enviar arquivo: ' . $e->getMessage());
        }
    }

    public function show(Upload $upload)
    {
        if ($upload->user_id !== auth()->id()) {
            abort(403);
        }

        $headers = [];

        // If meta_data is empty, try to extract headers now
        if (empty($upload->meta_data) || !isset($upload->meta_data['headers'])) {
            try {
                $fileParser = app(FileParserService::class);
                $fullPath = Storage::path($upload->file_path);
                $headers = $fileParser->getHeaders($fullPath);

                $upload->update([
                    'meta_data' => ['headers' => $headers]
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to extract headers: ' . $e->getMessage());
                return redirect()->route('uploads.index')
                    ->with('error', 'Erro ao processar arquivo: ' . $e->getMessage());
            }
        } else {
            $headers = $upload->meta_data['headers'];
        }

        return Inertia::render('Upload/Show', [
            'upload' => $upload,
            'headers' => $headers
        ]);
    }

    public function destroy(Upload $upload)
    {
        \Log::info('Destroying upload: ' . $upload->id);

        if ($upload->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            // Delete original file
            if (Storage::exists($upload->file_path)) {
                Storage::delete($upload->file_path);
            }

            // Delete generated files
            foreach ($upload->conversionJobs as $job) {
                if ($job->result_file_path && Storage::exists($job->result_file_path)) {
                    Storage::delete($job->result_file_path);
                }
            }

            $upload->delete();

            return redirect()->route('uploads.index')
                ->with('success', 'Arquivo removido com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('uploads.index')
                ->with('error', 'Erro ao remover arquivo: ' . $e->getMessage());
        }
    }
}
