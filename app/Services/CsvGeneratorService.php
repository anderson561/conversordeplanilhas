<?php

namespace App\Services;

use App\DTOs\RpsData;

class CsvGeneratorService
{
    public function generateCsv(array $rpsList): string
    {
        // Define Headers (Using Portuguese for user readability/Dominio)
        $headers = [
            'Data Emissão',
            'Competência',
            'Série',
            'Número RPS',
            'Valor Serviço',
            'CNPJ Prestador',
            'Inscrição Municipal Prestador',
            'CPF/CNPJ Tomador',
            'Razão Social Tomador',
            'Tipo Logradouro Tomador',
            'Logradouro Tomador',
            'Número Tomador',
            'Complemento Tomador',
            'Bairro Tomador',
            'Código Município Tomador',
            'UF Tomador',
            'CEP Tomador',
            'Discriminação',
            'Código Serviço',
            'Aliquota',
            'ISS Retido',
            'Valor ISS',
            'Valor Base Cálculo'
        ];

        // Open memory stream
        $fp = fopen('php://temp', 'r+');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($fp, "\xEF\xBB\xBF");

        // Write Headers
        fputcsv($fp, $headers, ';');

        foreach ($rpsList as $rps) {
            /** @var RpsData $rps */

            // Format Data
            $dataEmissao = \DateTime::createFromFormat('d/m/Y', $rps->dataEmissao);
            $dataIso = $dataEmissao ? $dataEmissao->format('d/m/Y') : $rps->dataEmissao;

            $competenciaObj = \DateTime::createFromFormat('Y-m-d', $rps->competencia);
            $competenciaIso = $competenciaObj ? $competenciaObj->format('m/Y') : $rps->competencia;

            // Format Values (Brazilian format: 1.000,00)
            $valorServico = number_format($rps->servico->valorServico, 2, ',', '.');
            $aliquota = number_format($rps->servico->aliquota ?? 0, 2, ',', '.');
            $valorIss = number_format($rps->servico->valorIss ?? 0, 2, ',', '.');
            $baseCalculo = number_format($rps->servico->baseCalculo ?? 0, 2, ',', '.');

            // Prepare Row Data
            $row = [
                $dataIso,
                $competenciaIso,
                $rps->serie,
                $rps->numero,
                $valorServico,
                // Prestador (not explicitly in RPS object usually, but can be added if needed, leaving empty or generic if not in DTO)
                '', // CNPJ Prestador
                '', // IM Prestador
                $rps->tomador->cpfCnpj,
                $rps->tomador->razaoSocial,
                '', // Tipo Logradouro (often part of logradouro)
                $rps->tomador->endereco->logradouro,
                $rps->tomador->endereco->numero,
                $rps->tomador->endereco->complemento,
                $rps->tomador->endereco->bairro,
                $rps->tomador->endereco->codigoMunicipio,
                $rps->tomador->endereco->uf,
                $rps->tomador->endereco->cep,
                str_replace(["\r", "\n"], " ", $rps->servico->discriminacao), // Flatten multiline descriptions
                $rps->servico->itemListaServico,
                $aliquota,
                $rps->servico->issRetido == 1 ? 'S' : 'N',
                $valorIss,
                $baseCalculo
            ];

            // Write Row
            fputcsv($fp, $row, ';');
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content;
    }
}
