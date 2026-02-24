<?php

namespace App\Services\Parsers;

use App\Contracts\FileParserInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ExcelParser implements FileParserInterface
{
    public function parse(string $filePath): array
    {
        Log::info('ExcelParser: Loading file for rows', ['path' => $filePath, 'memory' => memory_get_usage(true)]);

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true); // OPTIMIZATION: Ignore styles/formatting
            $spreadsheet = $reader->load($filePath);

            Log::info('ExcelParser: File loaded for rows', ['memory' => memory_get_usage(true)]);

            $worksheet = $spreadsheet->getActiveSheet();

            // Detect Header Row
            $headerRowIndex = 1;
            $highestScore = 0;
            $keywords = ['data', 'valor', 'cliente', 'cnpj', 'cpf', 'discriminacao', 'nota', 'serie', 'servico', 'total', 'bruto', 'liquido'];

            // Scan first 100 rows to find the best header candidate
            foreach ($worksheet->getRowIterator(1, 100) as $row) {
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

            Log::info('ExcelParser: Detected header row', ['row' => $headerRowIndex, 'score' => $highestScore]);

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
                    $headers = array_map(function ($h) {
                        return is_string($h) ? trim($h) : $h;
                    }, $cells);
                    $isHeaderRow = false;
                    continue;
                }

                // Combine headers with row values
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header ?? "Column_$index"] = $cells[$index] ?? null;
                }

                $rows[] = $rowData;
            }

            // Free memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();

            Log::info('ExcelParser: Rows extracted', ['count' => count($rows), 'memory' => memory_get_usage(true)]);

            return $rows;

        } catch (\Throwable $e) {
            Log::error('ExcelParser: Failed to parse Excel', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
