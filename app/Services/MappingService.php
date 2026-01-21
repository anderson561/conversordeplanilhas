<?php

namespace App\Services;

use App\DTOs\RpsData;
use App\DTOs\TomadorData;
use App\DTOs\ServicoData;
use App\DTOs\EnderecoData;
use Illuminate\Support\Arr;

class MappingService
{
    public const STANDARD_MAPPING = [
        'Data' => 'Data',
        'Valor' => 'Valor',
        'Razao Social' => 'Razao Social', // Will be auto-detected from EMPRESA or Histórico
        'CNPJ' => 'CNPJ',
        'Logradouro' => 'Logradouro',
        'Numero' => 'Numero', // Optional
        'Complemento' => 'Complemento', // Optional
        'Bairro' => 'Bairro',
        'Municipio' => 'Municipio',
        'UF' => 'UF',
        'CEP' => 'CEP',
    ];

    /**
     * Get standard mapping with smart column detection.
     * Detects company name column: prefers "EMPRESA", falls back to "Histórico"
     */
    /**
     * Get standard mapping with smart column detection.
     */
    public function getStandardMapping(array $availableColumns = []): array
    {
        $mapping = self::STANDARD_MAPPING;

        if (empty($availableColumns)) {
            return $mapping;
        }

        // Smart detection for Company Name (Tomador)
        $company = $this->detectColumn($availableColumns, ['cliente', 'tomador', 'razao social', 'razão social', 'nome', 'empresa', 'locatarios', 'locatários']);
        if ($company)
            $mapping['Razao Social'] = $company;

        // Smart detection for Value
        $valor = $this->detectColumn($availableColumns, ['valor bruto', 'valor total', 'valor servico', 'valor liq', 'valor', 'vlr', 'total']);
        if ($valor)
            $mapping['Valor'] = $valor;

        // Smart detection for CNPJ/CPF
        $cnpj = $this->detectColumn($availableColumns, ['cnpj / cpf', 'cpf / cnpj', 'cnpj', 'cpf', 'documento']);
        if ($cnpj)
            $mapping['CNPJ'] = $cnpj;

        // Smart detection for Date
        $dataCol = $this->detectColumn($availableColumns, ['data recebimento', 'dt recebimento', 'data emissao', 'data docto', 'dt. pagto', 'dt.', 'vencimento', 'competencia', 'competência']);
        if ($dataCol)
            $mapping['Data'] = $dataCol;

        // Smart detection for Address Fields
        $logradouro = $this->detectColumn($availableColumns, ['logradouro', 'endereco', 'endereço', 'rua', 'av', 'avenida']);
        if ($logradouro)
            $mapping['Logradouro'] = $logradouro;

        $numero = $this->detectColumn($availableColumns, ['numero', 'número', 'nro', 'num']);
        if ($numero)
            $mapping['Numero'] = $numero;

        $complemento = $this->detectColumn($availableColumns, ['complemento', 'compl']);
        if ($complemento)
            $mapping['Complemento'] = $complemento;

        $bairro = $this->detectColumn($availableColumns, ['bairro', 'bair']);
        if ($bairro)
            $mapping['Bairro'] = $bairro;

        $municipio = $this->detectColumn($availableColumns, ['municipio', 'município', 'cidade', 'cid']);
        if ($municipio)
            $mapping['Municipio'] = $municipio;

        $uf = $this->detectColumn($availableColumns, ['uf', 'estado', 'est']);
        if ($uf)
            $mapping['UF'] = $uf;

        $cep = $this->detectColumn($availableColumns, ['cep']);
        if ($cep)
            $mapping['CEP'] = $cep;

        return $mapping;
    }

    /**
     * Detect column from available columns using keywords.
     */
    private function detectColumn(array $columns, array $keywords): ?string
    {
        $normalizedColumns = array_map(fn($c) => mb_strtolower(trim($c)), $columns);
        // Create map of lowerCase -> Original
        $columnMap = array_combine($normalizedColumns, $columns);

        foreach ($keywords as $keyword) {
            // First pass: Exact match
            if (isset($columnMap[$keyword])) {
                return $columnMap[$keyword];
            }

            // Second pass: Contains
            foreach ($normalizedColumns as $col) {
                if (str_contains($col, $keyword)) {
                    return $columnMap[$col];
                }
            }
        }

        return null;
    }

    /**
     * Convert a collection of raw rows into RpsData objects based on mapping rules.
     * 
     * @param array $rows Raw data from spreadsheet
     * @param array $mappingRules Key-value pair where Key is DTO property (dot notation) and Value is spreadsheet column header
     */
    public function mapRowsToRps(array $rows, array $mappingRules): array
    {
        $rpsList = [];

        foreach ($rows as $row) {
            $rps = $this->mapSingleRow($row, $mappingRules);

            // FILTER: Validate RPS before adding
            if ($this->isValidRps($rps)) {
                $rpsList[] = $rps;
            }
        }

        return $rpsList;
    }

    /**
     * Check if RPS data is valid and not a header/total row
     */
    private function isValidRps(?RpsData $rps): bool
    {
        if (!$rps)
            return false;

        $name = mb_strtoupper($rps->tomador->razaoSocial ?? '');
        $cnpj = $rps->tomador->cpfCnpj;
        $valor = $rps->servico->valorServico;

        // Skip Header Repeats
        if ($name === 'CLIENTE' || $name === 'NOME' || $name === 'RAZAO SOCIAL') {
            \Log::info("MappingService: Valid - Skipping Header Row", ['name' => $name]);
            return false;
        }

        // Skip Totals / Titles
        if (str_contains($name, 'TOTAL') || str_contains($name, 'ALUGUEIS') || str_contains($name, 'RESIDENCIAIS')) {
            \Log::info("MappingService: Valid - Skipping Total/Title Row", ['name' => $name]);
            return false;
        }

        // Skip Empty Name AND Empty CNPJ
        if (empty($name) && empty($cnpj)) {
            // Only log if valor is significant, otherwise it's just a blank row
            if ($valor > 0) {
                \Log::info("MappingService: Valid - Skipping Row (Value > 0 but No Name/CNPJ)", ['valor' => $valor, 'raw_name' => $rps->tomador->razaoSocial, 'raw_cnpj' => $rps->tomador->cpfCnpj]);
            }
            return false;
        }

        // Skip Zero Value AND Empty CNPJ (likely a spacer row)
        if ($valor == 0 && empty($cnpj)) {
            // Common case for spacer rows
            return false;
        }

        return true;
    }

    private function mapSingleRow(array $row, array $mappingRules): RpsData
    {
        // Debug: Trace first few rows to see what keys are being accessed
        static $traceCount = 0;
        if ($traceCount++ < 5) {
            \Log::info('MappingService: Row Trace', [
                'row_keys' => array_keys($row),
                'mapping_rules' => $mappingRules
            ]);
        }

        // Helper to get value from row based on mapping
        $getValue = function ($targetField) use ($row, $mappingRules) {
            $sourceColumn = $mappingRules[$targetField] ?? null;
            if (!$sourceColumn)
                return null;
            return $row[$sourceColumn] ?? null;
        };

        // Helper to sanitize CNPJ/CPF - remove all non-numeric characters
        $sanitizeCpfCnpj = function ($value) {
            if (!$value)
                return null;
            return preg_replace('/[^0-9]/', '', $value);
        };

        // Get the 4 core fields
        $data = $getValue('Data');

        // Parse Brazilian currency format (e.g. 7.000,00 or 71.313,81)
        $valorRaw = $getValue('Valor');
        $valor = 0.0;
        if ($valorRaw) {
            $valorStr = (string) $valorRaw;

            // Check if it has a comma (common Brazilian format)
            if (str_contains($valorStr, ',')) {
                // Remove dots (thousands separator) and then replace comma with dot (decimal separator)
                $valorClean = str_replace('.', '', $valorStr);
                $valorClean = str_replace(',', '.', $valorClean);
                $valorClean = preg_replace('/[^0-9\.-]/', '', $valorClean);
            } else {
                // Standard dot decimal or raw integer
                $valorClean = preg_replace('/[^0-9\.-]/', '', $valorStr);
            }
            $valor = (float) $valorClean;
        }

        // Clean Date - Try multiple column names
        $dataRaw = $getValue('Data')
            ?? $getValue('Data Recebimento')
            ?? $getValue('Data de Recebimento');
        $dataNormalized = $this->normalizeDate($dataRaw);

        // Fallback: Try to use Competência field if Data is missing/invalid
        // Competência format: AAAAMM (e.g., 202602 = February 2026)
        if (!$dataNormalized) {
            $competenciaRaw = $getValue('Competencia') ?? $getValue('Competência');
            if ($competenciaRaw && preg_match('/^(\d{4})(\d{2})$/', $competenciaRaw, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                // Use first day of the competência month
                $dataNormalized = "$year-$month-01";
                \Log::info("MappingService: Using Competência as date fallback", [
                    'competencia' => $competenciaRaw,
                    'generated_date' => $dataNormalized
                ]);
            }
        }

        // Final fallback: Use a fixed old date to flag issues.
        $dataEmissao = $dataNormalized ?: '2000-01-01';

        if (!$dataNormalized && $dataRaw) {
            \Log::warning("MappingService: Date normalization failed", [
                'raw' => $dataRaw,
                'fallback' => $dataEmissao
            ]);
        }

        // Create Tomador with the 2 fields we have
        \Log::debug("MappingService: Normalized Date", [
            'raw' => $dataRaw,
            'normalized' => $dataNormalized,
            'emissao' => $dataEmissao
        ]);

        $razaoSocial = $getValue('Razao Social');

        // Clean Razão Social - remove invalid patterns
        if ($razaoSocial) {
            // Remove leading numeric values (leakage from totals)
            $razaoSocial = preg_replace('/^[\d\.,\s]+/', '', $razaoSocial);
            // Remove pattern like "CNPJ;14.578.289/0003-05 JURIDICO"
            $razaoSocial = preg_replace('/CNPJ;?\s*\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\s+\w+/i', '', $razaoSocial);
            // Remove standalone "JURIDICO" or similar keywords
            $razaoSocial = trim(preg_replace('/^\s*(JURIDICO|FISICO)\s*$/i', '', $razaoSocial));
            // Remove '<>' pattern
            $razaoSocial = str_replace('<>', '', $razaoSocial);
            $razaoSocial = trim($razaoSocial);
        }

        $cnpj = $sanitizeCpfCnpj($getValue('CNPJ'));

        // Debug Log for specific row failures (only log if meaningful data is present but dropped)
        if ($valor == 0 && empty($cnpj) && empty($razaoSocial)) {
            // likely a true empty row, ignore
        } else if ($valor == 0 && empty($cnpj)) {
            \Log::debug('MappingService: Row has 0 value and empty CNPJ', ['raw_value' => $valorRaw, 'parsed_valor' => $valor, 'name' => $razaoSocial]);
        }

        // Generate defaults for missing fields
        // Fix: Use the actual dataEmissao for competencia as well, so accounting software treats it as the transaction date.
        $competencia = $dataEmissao;

        // Create Endereco from mapped columns or defaults (Pass null to let Generator handle Fallbacks)
        $endereco = new EnderecoData(
            logradouro: $getValue('Logradouro'),
            numero: $getValue('Numero'),
            complemento: $getValue('Complemento'),
            bairro: $getValue('Bairro'),
            codigoMunicipio: null, // Will be resolved or defaulted in Generator
            uf: $getValue('UF') ?? 'BA',
            cep: $getValue('CEP') ?? '40000000',
            xMun: $getValue('Municipio') ?? 'Salvador'
        );

        // Create Tomador with the 2 fields we have
        $tomador = new TomadorData(
            cpfCnpj: $cnpj,
            razaoSocial: $razaoSocial,
            inscricaoMunicipal: null,
            endereco: $endereco
        );

        // Create Servico with valor and defaults
        $servico = new ServicoData(
            valorServico: $valor,
            valorDeducoes: 0,
            valorPis: 0,
            valorCofins: 0,
            valorInss: 0,
            valorIr: 0,
            valorCsll: 0,
            issRetido: 2, // Não retido
            valorIss: 0,
            valorIssRetido: 0,
            outrasRetencoes: 0,
            baseCalculo: $valor, // Same as valor
            aliquota: 0,
            valorLiquidoNfse: $valor, // Same as valor
            descontoIncondicionado: 0,
            descontoCondicionado: 0,
            itemListaServico: '0101', // Default service code
            codigoCnae: null,
            codigoTributacaoMunicipio: null,
            discriminacao: $razaoSocial, // Use razao social as description
            codigoMunicipio: '2927408', // Salvador/BA
        );

        // Generate sequential RPS number based on row index
        static $rpsCounter = 1;

        return new RpsData(
            numero: (string) $rpsCounter++,
            serie: '1',
            tipo: '1', // RPS
            dataEmissao: $dataEmissao,
            competencia: $competencia,
            tomador: $tomador,
            servico: $servico
        );
    }

    /**
     * Normalize date to ISO Y-m-d or d/m/Y consistently
     */
    private function normalizeDate($date): ?string
    {
        if (!$date)
            return null;
        if ($date instanceof \DateTime)
            return $date->format('Y-m-d');

        $dateStr = trim((string) $date);
        if (empty($dateStr))
            return null;

        // Clean common noise
        $dateStr = str_replace('.', '/', $dateStr);

        // Try Y-m-d (Excel default ISO)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateStr, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        // Try d/m/Y or d/m/y (supports both 12/02/2025 and 12/2/2025 formats)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4}|\d{2})/', $dateStr, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];

            if (strlen($year) === 2) {
                $year = '20' . $year;
            }
            return "{$year}-{$month}-{$day}";
        }

        // Fallback to strtotime
        $ts = strtotime(str_replace('/', '-', $dateStr));
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
