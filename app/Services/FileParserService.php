<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Collection;

class FileParserService
{
    /**
     * Parse the uploaded file and return the header row.
     */
    public function getHeaders(string $filePath, ?string $mimeType = null): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf' || $mimeType === 'application/pdf') {
            $pdfParser = app(PdfParserService::class);
            $rows = $pdfParser->parse($filePath);
            return !empty($rows) ? array_keys($rows[0]) : [];
        }

        \Log::info('FileParserService: Loading file for headers', ['path' => $filePath]);

        try {
            $spreadsheet = IOFactory::load($filePath);
            \Log::info('FileParserService: File loaded successfully');

            $worksheet = $spreadsheet->getActiveSheet();
            \Log::info('FileParserService: Active sheet loaded');

            // Assuming headers are in the first row
            $headers = [];
            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $headers[] = $cell->getValue();
                }
            }

            \Log::info('FileParserService: Headers extracted', ['count' => count($headers)]);
            return $headers;

        } catch (\Throwable $e) {
            \Log::error('FileParserService: Failed to get headers', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Parse the file and return all rows as a collection of associative arrays.
     * Keys are the header names.
     */
    public function getRows(string $filePath, int $limit = null, ?string $mimeType = null): Collection
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf' || $mimeType === 'application/pdf') {
            $pdfParser = app(PdfParserService::class);
            $rows = $pdfParser->parse($filePath);
            return collect($rows);
        }



        \Log::info('FileParserService: Loading file for rows', ['path' => $filePath, 'memory' => memory_get_usage(true)]);

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true); // OPTIMIZATION: Ignore styles/formatting
            $spreadsheet = $reader->load($filePath);

            \Log::info('FileParserService: File loaded for rows', ['memory' => memory_get_usage(true)]);

            $worksheet = $spreadsheet->getActiveSheet();

            // Detect Header Row
            $headerRowIndex = 1;
            $highestScore = 0;
            $keywords = ['data', 'valor', 'cliente', 'cnpj', 'cpf', 'discriminacao', 'nota', 'serie', 'servico', 'total', 'bruto', 'liquido'];

            // Scan first 20 rows to find the best header candidate
            foreach ($worksheet->getRowIterator(1, 20) as $row) {
                $score = 0;
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true);

                foreach ($cellIterator as $cell) {
                    $val = mb_strtolower(trim((string) $cell->getValue()));
                    foreach ($keywords as $keyword) {
                        if (str_contains($val, $keyword)) {
                            $score++;
                        }
                    }
                }

                if ($score > $highestScore) {
                    $highestScore = $score;
                    $headerRowIndex = $row->getRowIndex();
                }
            }

            \Log::info('FileParserService: Detected header row', ['row' => $headerRowIndex, 'score' => $highestScore]);

            $rows = [];
            $headers = [];
            $isHeaderRow = true;

            // Start iteration from the detected header row
            foreach ($worksheet->getRowIterator($headerRowIndex) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $cells = [];
                foreach ($cellIterator as $cell) {
                    $value = $cell->getCalculatedValue();

                    // Convert numeric strings to actual numbers
                    if (is_string($value) && is_numeric($value)) {
                        $value = strpos($value, '.') !== false ? (float) $value : (int) $value;
                    }

                    $cells[] = $value;
                }

                if ($isHeaderRow) {
                    $headers = $cells;
                    $isHeaderRow = false;
                    continue;
                }

                // Combine headers with row values
                // Ensure we don't have mismatch in count
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header ?? "Column_$index"] = $cells[$index] ?? null;
                }

                $rows[] = $rowData;

                if ($limit && count($rows) >= $limit) {
                    break;
                }
            }

            // Free memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();

            \Log::info('FileParserService: Rows extracted', ['count' => count($rows), 'memory' => memory_get_usage(true)]);

            return collect($rows);

        } catch (\Throwable $e) {
            \Log::error('FileParserService: Failed to get rows', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
