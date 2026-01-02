<?php

namespace App\Services;

class DataValidationService
{
    /**
     * Validate a single row of data based on mapping rules.
     * Returns an array of error messages, or empty array if valid.
     *
     * @param array $row The raw row data (key = header, value = cell content)
     * @param array $mappingRules Mapping rules (key = DTO field, value = header name)
     * @param int $rowIndex The row index (for error reporting)
     * @return array List of error messages
     */
    public function validateRow(array $row, array $mappingRules, int $rowIndex): array
    {
        $errors = [];

        // 1. Validate Data (Date)
        $dataHeader = $mappingRules['Data'] ?? null;
        if ($dataHeader && isset($row[$dataHeader])) {
            if (!$this->validateData($row[$dataHeader])) {
                $errors[] = "Linha {$rowIndex}: Campo 'Data' ({$row[$dataHeader]}) inválido. Formato esperado: DD/MM/YYYY.";
            }
        } else {
            $errors[] = "Linha {$rowIndex}: Coluna 'Data' não encontrada.";
        }

        // 2. Validate Valor (Value)
        $valorHeader = $mappingRules['Valor'] ?? null;
        if ($valorHeader && isset($row[$valorHeader])) {
            if (!$this->validateValor($row[$valorHeader])) {
                $errors[] = "Linha {$rowIndex}: Campo 'Valor' ({$row[$valorHeader]}) inválido. Deve ser um número maior que zero.";
            }
        } else {
            $errors[] = "Linha {$rowIndex}: Coluna 'Valor' não encontrada.";
        }

        // 3. Validate Razão Social (Company Name)
        $razaoSocialHeader = $mappingRules['Razao Social'] ?? null;
        if ($razaoSocialHeader && isset($row[$razaoSocialHeader])) {
            if (!$this->validateRazaoSocial($row[$razaoSocialHeader])) {
                $errors[] = "Linha {$rowIndex}: Campo 'Razão Social' ({$row[$razaoSocialHeader]}) inválido. Deve ter pelo menos 3 caracteres.";
            }
        } else {
            $errors[] = "Linha {$rowIndex}: Coluna 'Razão Social' (ou EMPRESA/Histórico) não encontrada.";
        }

        // 4. Validate CNPJ
        $cnpjHeader = $mappingRules['CNPJ'] ?? null;
        if ($cnpjHeader && isset($row[$cnpjHeader])) {
            if (!$this->validateCnpj($row[$cnpjHeader])) {
                $errors[] = "Linha {$rowIndex}: Campo 'CNPJ' ({$row[$cnpjHeader]}) inválido. Deve conter 14 dígitos.";
            }
        } else {
            $errors[] = "Linha {$rowIndex}: Coluna 'CNPJ' não encontrada.";
        }

        return $errors;
    }

    private function validateData(?string $data): bool
    {
        if (empty($data))
            return false;
        // Check format DD/MM/YYYY
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
            return false;
        }

        $d = \DateTime::createFromFormat('d/m/Y', $data);
        return $d && $d->format('d/m/Y') === $data;
    }

    private function validateValor(?string $valor): bool
    {
        if (empty($valor))
            return false;

        // Convert Brazilian format (1.000,00) to float
        $valorClean = str_replace('.', '', $valor); // Remove thousands separator
        $valorClean = str_replace(',', '.', $valorClean); // Replace decimal separator

        return is_numeric($valorClean) && (float) $valorClean > 0;
    }

    private function validateRazaoSocial(?string $razaoSocial): bool
    {
        if (empty($razaoSocial))
            return false;
        return strlen(trim($razaoSocial)) >= 3;
    }

    private function validateCnpj(?string $cnpj): bool
    {
        if (empty($cnpj))
            return false;

        // Remove non-numeric chars
        $cnpjClean = preg_replace('/[^0-9]/', '', $cnpj);

        return strlen($cnpjClean) === 14;
    }
}
