<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Collection;

class PdfParserService
{
    public function parse(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        \Log::info('PDF Raw Text Sample', ['text' => substr($text, 0, 1000)]);

        // Split into lines
        $lines = explode("\n", $text);
        $rows = [];

        // Regex patterns
        // Pattern 1: Date | Value | Name | CNPJ (Standard)
        // 01/07/2025 2.160,00 Sample Name 12.345.678/0001-90
        $pattern1 = '/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))\s+([\d\.,]+(?:R\$)?)\s+(.+?)\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})/u';

        // Pattern 2: Name | CNPJ | Value | Date (User Image)
        // Sample Name 12.345.678/0001-90 2.160,00 01/07/2025
        $pattern2 = '/(.+?)\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})\s+([\d\.,]+(?:R\$)?)\s+(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))/u';

        // Pattern 4: Bank Statement Format (BANK DATE VALUE NAME ... CNPJ)
        // BANCO BRASIL 04/11/2025 31.317,98IG PROJETO, CONSULTORIA E ENTRETENIMENTO LTDA ALUGUEL IMOVEL 40.690.212/001-90
        // Note: Value and Name may be concatenated without space
        $pattern4 = '/(?:BANCO\s+\w+|[\w\s\.-]+?)\s+(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))\s+([\d\.,]+)\s*(.+?)\s+(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})/ui';

        $count = count($lines);
        $lastSeenDate = null; // Store the last valid date found in the document

        for ($i = 0; $i < $count; $i++) {
            $line = trim($lines[$i]);
            if (empty($line))
                continue;

            // TRACKING: Look for any date or partial date (MM/YYYY) in the line to update current context
            if (preg_match('/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))/', $line, $dateCheck)) {
                $lastSeenDate = $dateCheck[1];
            } elseif (preg_match('/\b(\d{2}[\/\.]\d{4})\b/', $line, $partialCheck)) {
                // If we see something like 12/2025, treat it as 01/12/2025 for context
                $lastSeenDate = "01/" . str_replace('.', '/', $partialCheck[1]);
            }

            // Pattern 4: Bank Statement Format (BANK DATE VALUE NAME ... CNPJ/CPF)
            // BANCO BRASIL 04/11/2025 31.317,98IG PROJETO, CONSULTORIA E ENTRETENIMENTO LTDA	ALUGUEL IMOVEL 40.690.212/001-90
            // BANCO BRASIL 18/11/2025 23.750,00RONDINELE ALMIR SAMPAIO SILVA	VENDA IMOVEL 914.825.425-87
            // Note: Value and Name may be concatenated, and TAB separates name from description
            // CNPJ: XX.XXX.XXX/XXX-XX or XX.XXX.XXX/XXXX-XX
            // CPF: XXX.XXX.XXX-XX
            if (preg_match('/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))\s+([\d\.,]+)(.+?)(\d{2,3}[\.\/,\-]\d{3}[\.\/,\-]\d{3}[\.\/,\-][\d\.\/,\-]+\d{1,2})/u', $line, $matches)) {
                \Log::info('Pattern 4 Matched', ['line' => $line, 'matches' => $matches]);

                $date = $matches[1];
                $lastSeenDate = $date;
                $value = $matches[2];
                $middleSection = $matches[3]; // Everything between value and CNPJ
                $cnpj = $matches[4];

                // Split middle section by TAB or multiple spaces to separate company name from noise/description
                $parts = preg_split('/[\t]+|\s{4,}/', $middleSection);
                $companyName = '';
                foreach ($parts as $part) {
                    $cleanPart = trim($part);
                    // Skip empty or purely numeric/value patterns (e.g. 75.000,00)
                    if (empty($cleanPart) || preg_match('/^[\d\.,]+(?:R\$)?$/ui', $cleanPart)) {
                        continue;
                    }
                    $companyName = $cleanPart;
                    break;
                }

                \Log::info('Pattern 4 Extracted', [
                    'date' => $date,
                    'value' => $value,
                    'middle' => $middleSection,
                    'parts' => $parts,
                    'company_name' => $companyName,
                    'cnpj' => $cnpj
                ]);

                // Remove description keywords from company name if they leaked in
                $companyName = preg_replace('/\s+(ALUGUEL|VENDA|LOCACAO|SERVICO|PRESTACAO)\s+\w+\s*$/ui', '', $companyName);
                $companyName = trim($companyName);

                if (!empty($companyName) && !empty($cnpj)) {
                    $rows[] = [
                        'Data' => $date,
                        'Valor' => str_replace(['R$', ' '], '', $value),
                        'Razao Social' => $companyName,
                        'CNPJ' => $cnpj,
                    ];
                    \Log::info('Pattern 4 Row Added', ['row' => end($rows)]);
                    continue;
                } else {
                    \Log::warning('Pattern 4 Rejected', ['company_empty' => empty($companyName), 'cnpj_empty' => empty($cnpj)]);
                }
            }

            // Check Pattern 1 (Standard Single Line)
            if (preg_match($pattern1, $line, $matches)) {
                $lastSeenDate = $matches[1];
                $rows[] = [
                    'Data' => $matches[1],
                    'Valor' => str_replace(['R$', ' '], '', $matches[2]),
                    // Remove UND/UN column bleed
                    'Razao Social' => trim(preg_replace('/\b(UND|UN|UNID)\b/i', '', strip_tags($matches[3]))),
                    'CNPJ' => $matches[4],
                ];
                continue;
            }

            // Check Pattern 2 (User Image Single Line)
            if (preg_match($pattern2, $line, $matches)) {
                $lastSeenDate = $matches[4];
                $rows[] = [
                    'Data' => $matches[4],
                    'Valor' => str_replace(['R$', ' '], '', $matches[3]),
                    // Remove UND/UN column bleed
                    'Razao Social' => trim(preg_replace('/\b(UND|UN|UNID)\b/i', '', strip_tags($matches[1]))),
                    'CNPJ' => $matches[2],
                ];
                continue;
            }

            // Check Pattern 3 (Multi-line: Name \s+ ValueR$)
            // Look for: Name (tab/space) ValueR$
            // Example: JURIDICO\t15.338,91R$ or JURIDICO 15.338,91R$
            if (preg_match('/(.+?)\s+([\d\.,]+)R\$/u', $line, $matches) || preg_match('/(.+?)\s+-\s+R\$\s*([\d\.,]+)/u', $line, $matches)) {
                $razaoSocial = trim($matches[1]);
                // Remove invalid patterns like '<>'
                $razaoSocial = str_replace('<>', '', $razaoSocial);
                // Remove UND/UN column bleed
                // Remove trailing hyphen if captured
                $razaoSocial = rtrim($razaoSocial, '- ');
                $razaoSocial = trim(preg_replace('/\b(UND|UN|UNID)\b/i', '', $razaoSocial));

                $valor = str_replace(['R$', ' '], '', $matches[2]);

                // Look for CNPJ in previous lines (up to 3 lines back)
                $cnpj = '';
                for ($j = 0; $j <= 3; $j++) {
                    if (($i - $j) >= 0) {
                        $prevLine = $lines[$i - $j];
                        // Try typical CNPJ pattern or number only 14 digits
                        if (preg_match('/(\d{2,3}\.\d{3}\.\d{3}[\/\-]\d{2,4}-?\d{0,2})/', $prevLine, $cnpjMatches)) {
                            $cnpj = $cnpjMatches[1];
                            break;
                        }
                    }
                }

                // Look for Date in context (Last seen or next few lines)
                $data = $lastSeenDate; // Use memory as first fallback

                // If no memory, look forward briefly
                if (!$data) {
                    for ($k = 1; $k <= 3; $k++) {
                        if (($i + $k) < $count) {
                            $nextLine = $lines[$i + $k];
                            if (preg_match('/(\d{2}[\/\.]\d{2}[\/\.](?:\d{4}|\d{2}))/', $nextLine, $dateMatches)) {
                                $data = $dateMatches[1];
                                $lastSeenDate = $data;
                                break;
                            }
                        }
                    }
                }

                if ($cnpj) {
                    $rows[] = [
                        'Data' => $data,
                        'Valor' => $valor,
                        'Razao Social' => $razaoSocial,
                        'CNPJ' => $cnpj,
                    ];
                    \Log::info('Pattern 3/5 Matched', ['line' => $line, 'row' => end($rows)]);
                }
            }
        }

        \Log::info('PDF Parsed Rows', ['count' => count($rows)]);
        return $rows;
    }
}
