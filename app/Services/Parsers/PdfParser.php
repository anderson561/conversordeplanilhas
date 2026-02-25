<?php

namespace App\Services\Parsers;

use App\Contracts\FileParserInterface;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class PdfParser implements FileParserInterface
{
    public function parse(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        Log::info('PdfParser: RAW Text extracted', ['length' => strlen($text)]);

        // Split into lines
        $lines = explode("\n", $text);
        $rows = [];

        // Regex patterns (Updated to capture optional UND code before CNPJ)
        $pattern1 = '/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))\s+([\d\.,]+(?:R\$)?)\s+(.+?)(?:\s+([A-Z]{2,4}\d{2,4}(?:E\d+)?))?\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})/u';
        $pattern2 = '/(.+?)(?:\s+([A-Z]{2,4}\d{2,4}(?:E\d+)?))?\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})\s+([\d\.,]+(?:R\$)?)\s+(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))/u';
        $pattern4 = '/(?:\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))\s+([\d\.,]+)\s*(.+?)(?:\s+([A-Z]{2,4}\d{2,4}(?:E\d+)?))?\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})/ui';

        $count = count($lines);
        $lastSeenDate = null;

        for ($i = 0; $i < $count; $i++) {
            $line = trim($lines[$i]);
            if (empty($line))
                continue;

            // TRACKING: Look for any date
            if (preg_match('/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))/', $line, $dateCheck)) {
                $lastSeenDate = $dateCheck[1];
            }

            // Keyword Filtering (Simplified for this version, keeping original logic)
            if (preg_match('/\b(transf\b|transf\.|transferência|resgate|crédito|credito|iof|irrf|tarifas?|taxas?|impostos?)\b/ui', $line)) {
                continue;
            }

            // Pattern 4 strategy
            if (preg_match('/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))\s+([\d\.,]+)(.+?)(?:\s+([A-Z]{2,4}\d{2,4}(?:E\d+)?))?\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})/u', $line, $matches)) {
                $rows[] = [
                    'Data' => $matches[1],
                    'Valor' => str_replace(['R$', ' '], '', $matches[2]),
                    'Razao Social' => trim($matches[3]),
                    'UND' => $matches[4] ?? null,
                    'CNPJ' => $matches[5],
                ];
                continue;
            }

            // Pattern 1
            if (preg_match($pattern1, $line, $matches)) {
                $rows[] = [
                    'Data' => $matches[1],
                    'Valor' => str_replace(['R$', ' '], '', $matches[2]),
                    'Razao Social' => trim($matches[3]),
                    'UND' => $matches[4] ?? null,
                    'CNPJ' => $matches[5],
                ];
                continue;
            }

            // Pattern 2
            if (preg_match($pattern2, $line, $matches)) {
                $rows[] = [
                    'Data' => $matches[5],
                    'Valor' => str_replace(['R$', ' '], '', $matches[4]),
                    'Razao Social' => trim($matches[1]),
                    'UND' => $matches[2] ?? null,
                    'CNPJ' => $matches[3],
                ];
                continue;
            }
        }

        return $rows;
    }
}
