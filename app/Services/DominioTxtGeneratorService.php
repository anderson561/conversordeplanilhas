<?php

namespace App\Services;

use App\DTOs\RpsData;
use App\Contracts\OutputGeneratorInterface;
use Illuminate\Support\Facades\Storage;

class DominioTxtGeneratorService implements OutputGeneratorInterface
{
    public function generateBatch(array $rpsList, string $loteId = '1', array $providerInfo = [], array $options = []): string
    {
        $state = $options['state'] ?? 'BA';
        $acumulador = $options['acumulador'] ?? '1';
        $startingNumber = $options['starting_number'] ?? 1;

        return $this->generate($rpsList, $providerInfo, $state, $loteId, $acumulador, $startingNumber);
    }

    public function getExtension(): string
    {
        return 'txt';
    }

    /**
     * Generate a Semicolon-Delimited TXT file for Domínio Import (Saídas)
     * Layout based on Google Sheet (Solution 9263)
     */
    public function generate(array $rpsList, array $providerInfo, string $state, string $batchId, string $acumulador = '1', int $startingNumber = 1): string
    {
        $lines = [];
        $currentNumber = $startingNumber;

        // No Header Row (Domínio 9263 Import uses data only, mapping by column order)

        foreach ($rpsList as $rps) {
            /** @var RpsData $rps */

            // 1. CPF/CNPJ
            $col01 = $this->formatCnpjCpf($rps->tomador->cpfCnpj);

            // 2. Razão Social
            $col02 = $this->sanitize($rps->tomador->razaoSocial, 150);

            // 3. UF
            $col03 = $this->sanitize($rps->tomador->endereco->uf ?? 'BA', 2);

            // 4. Município
            $col04 = $this->sanitize($rps->tomador->endereco->xMun ?? 'Salvador', 60);

            // 5. Endereço
            $lgr = $rps->tomador->endereco->logradouro ?? '';
            $nro = $rps->tomador->endereco->numero ?? '';
            $endereco = $lgr ? "$lgr, $nro" : $nro;
            $col05 = $this->sanitize($endereco, 150);

            // 6. Número Documento
            $col06 = (string) ($rps->numero ?: $currentNumber++);

            // 7. Série
            $col07 = $rps->serie;

            // 8. Data (DD/MM/YYYY)
            $col08 = $this->formatDate($rps->dataEmissao);

            // 9. Situação (0- Regular)
            $col09 = '0';

            // 10. Acumulador
            $col10 = $acumulador;

            // 11. CFOP
            $destUf = $rps->tomador->endereco->uf ?? $state;
            $col11 = ($destUf === $state) ? '5949' : '6949';

            // 12. Valor Produtos
            $col12 = $this->formatMoney($rps->servico->valorServico);

            // 13. Valor Descontos
            $col13 = $this->formatMoney(0.0);

            // 14. Valor Contábil
            $col14 = $this->formatMoney($rps->servico->valorServico);

            // 15. Base de Calculo ICMS
            $col15 = $this->formatMoney(0.0);

            // 16. Alíquota ICMS
            $col16 = $this->formatMoney(0.0);

            // 17. Valor ICMS
            $col17 = $this->formatMoney(0.0);

            // 18. Outras ICMS
            $col18 = $this->formatMoney(0.0);

            // 19. Isentas ICMS
            $col19 = $this->formatMoney(0.0);

            // 20. Base de Calculo IPI
            $col20 = $this->formatMoney(0.0);

            // 21. Alíquota IPI
            $col21 = $this->formatMoney(0.0);

            // 22. Valor IPI
            $col22 = $this->formatMoney(0.0);

            // 23. Outras IPI
            $col23 = $this->formatMoney(0.0);

            // 24. Isentas IPI
            $col24 = $this->formatMoney(0.0);

            // 25. Código do Item
            $col25 = ''; // Optional

            // 26. Quantidade
            $col26 = $this->formatMoney(0.0);

            // 27. Valor Unitário
            $col27 = $this->formatMoney(0.0);

            // 28. CST PIS/COFINS
            $col28 = '';

            // 29. Base de Calculo PIS/COFINS
            $col29 = $this->formatMoney(0.0);

            // 30. Aíquota PIS
            $col30 = $this->formatMoney(0.0);

            // 31. Valor PIS
            $col31 = $this->formatMoney(0.0);

            // 32. Alíquota COFINS
            $col32 = $this->formatMoney(0.0);

            // 33. Valor COFINS
            $col33 = $this->formatMoney(0.0);

            // Assemble line with Semicolon delimiter
            $line = implode(';', [
                $col01,
                $col02,
                $col03,
                $col04,
                $col05,
                $col06,
                $col07,
                $col08,
                $col09,
                $col10,
                $col11,
                $col12,
                $col13,
                $col14,
                $col15,
                $col16,
                $col17,
                $col18,
                $col19,
                $col20,
                $col21,
                $col22,
                $col23,
                $col24,
                $col25,
                $col26,
                $col27,
                $col28,
                $col29,
                $col30,
                $col31,
                $col32,
                $col33
            ]);

            $lines[] = $line;
        }

        return implode("\r\n", $lines);
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, ',', '');
    }

    private function formatDate(?string $isoDate): string
    {
        if (!$isoDate)
            return '';
        $time = strtotime($isoDate);
        return $time ? date('d/m/Y', $time) : '';
    }

    private function formatCnpjCpf(string $val): string
    {
        return preg_replace('/\D/', '', $val);
    }

    private function sanitize(?string $text, int $limit): string
    {
        if (!$text)
            return '';
        $clean = str_replace(['|', ';', "\n", "\r"], ' ', $text);
        $clean = trim($clean);
        return substr($clean, 0, $limit);
    }
}
