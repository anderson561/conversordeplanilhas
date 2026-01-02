<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CnpjLookupService
{
    /**
     * Lookup CNPJ data from ReceitaWS API
     */
    public function lookup(string $cnpj): array
    {
        // Remove formatting
        $cnpj = preg_replace('/\D/', '', $cnpj);

        // Validate CNPJ length
        if (strlen($cnpj) !== 14) {
            throw new \Exception('CNPJ deve conter 14 dígitos');
        }

        try {
            // Query ReceitaWS API
            // Note: withoutVerifying() is used for development only
            // In production, ensure proper SSL certificates are configured
            $http = Http::timeout(10);

            if (!app()->isProduction()) {
                $http->withoutVerifying();
            }

            $response = $http->get("https://www.receitaws.com.br/v1/cnpj/{$cnpj}");

            if (!$response->successful()) {
                throw new \Exception('Erro ao consultar CNPJ na Receita Federal');
            }

            $data = $response->json();

            // Check if CNPJ was found
            if (isset($data['status']) && $data['status'] === 'ERROR') {
                throw new \Exception($data['message'] ?? 'CNPJ não encontrado');
            }

            // Return formatted data
            return [
                'razao_social' => $data['nome'] ?? '',
                'nome_fantasia' => $data['fantasia'] ?? '',
                'cnpj' => $data['cnpj'] ?? $cnpj,
                'situacao' => $data['situacao'] ?? '',
                'endereco' => [
                    'logradouro' => $data['logradouro'] ?? '',
                    'numero' => $data['numero'] ?? '',
                    'complemento' => $data['complemento'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'municipio' => $data['municipio'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'cep' => $data['cep'] ?? '',
                ],
                'telefone' => $data['telefone'] ?? '',
                'email' => $data['email'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('CNPJ Lookup Error', [
                'cnpj' => $cnpj,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate CNPJ check digits
     */
    public function isValid(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }

        // Check for known invalid CNPJs
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;
        $multiplier = 5;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $multiplier;
            $multiplier = ($multiplier == 2) ? 9 : $multiplier - 1;
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if ($cnpj[12] != $digit1) {
            return false;
        }

        // Validate second check digit
        $sum = 0;
        $multiplier = 6;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $multiplier;
            $multiplier = ($multiplier == 2) ? 9 : $multiplier - 1;
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cnpj[13] == $digit2;
    }
}
