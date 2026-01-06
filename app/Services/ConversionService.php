<?php

namespace App\Services;

use App\Models\ConversionJob;
use Illuminate\Support\Facades\Storage;

class ConversionService
{
    public function __construct(
        protected FileParserService $fileParser,
        protected MappingService $mapper,
        protected \App\Factories\GeneratorFactory $factory
    ) {
    }

    public function process(ConversionJob $job, array $mappingRules): string
    {
        $fullPath = Storage::path($job->upload->file_path);

        // 1. Parse
        $rows = $this->fileParser->getRows($fullPath, null, $job->upload->mime_type);

        // 2. Map
        $headers = array_keys($rows->first() ?? []);
        $generatedMapping = $this->mapper->getStandardMapping($headers);

        \Log::info('ConversionService: Headers and Mapping', [
            'headers' => $headers,
            'mapping' => $generatedMapping
        ]);

        // auto-detection wins if found
        $finalMapping = !empty($generatedMapping) ? $generatedMapping : array_replace($generatedMapping, $mappingRules);

        $rpsList = $this->mapper->mapRowsToRps($rows->toArray(), $finalMapping);

        // 3. Resolve Generator Type
        $xmlType = $job->upload->xml_type ?? 'servico';

        if ($xmlType === 'excel') {
            $metaData = $job->upload->meta_data ?? [];
            $xmlType = $metaData['excel_output_type'] ?? 'saida';
        }

        // 4. Prepare Generation Data
        $providerInfo = [
            'cnpj' => $job->upload->provider_info['cnpj'] ?? '00000000000000',
            'razao_social' => $job->upload->provider_info['razao_social'] ?? 'EMPRESA PADRAO',
            'inscricao_municipal' => $job->upload->provider_info['inscricao_municipal'] ?? '000000',
            'endereco' => $job->upload->provider_endereco ?? 'RUA EXEMPLO, 123',
            'bairro' => $job->upload->provider_bairro ?? 'CENTRO',
            'cep' => $job->upload->provider_cep ?? '40000-000',
            'municipio' => $job->upload->provider_municipio ?? 'Salvador',
            'uf' => $job->upload->provider_uf ?? 'BA',
            'fone' => $job->upload->provider_fone ?? '',
        ];

        $options = [
            'state' => $job->upload->provider_uf ?? 'BA',
            'starting_number' => $job->upload->starting_number ?? 1,
            'acumulador' => $job->upload->acumulador ?? '1',
        ];

        // 5. Generate using Factory & Strategy
        try {
            $generator = $this->factory->make($xmlType);
            $content = $generator->generateBatch($rpsList, (string) $job->id, $providerInfo, $options);
            $extension = $generator->getExtension();
        } catch (\Exception $e) {
            \Log::error("Factory failed to create generator for type {$xmlType}: " . $e->getMessage());
            throw $e;
        }

        // 6. Save Result
        $fileName = 'conversion_' . $job->id . '.' . $extension;
        $path = 'conversions/' . $fileName;
        Storage::put($path, $content);

        $job->update([
            'status' => 'completed',
            'result_file_path' => $path,
            'completed_at' => now(),
        ]);

        return $path;
    }
}
