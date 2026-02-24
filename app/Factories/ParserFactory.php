<?php

namespace App\Factories;

use App\Contracts\FileParserInterface;
use App\Services\Parsers\ExcelParser;
use App\Services\Parsers\PdfParser;
use Exception;

class ParserFactory
{
    /**
     * Create the appropriate parser based on the file extension.
     *
     * @param string $filePath
     * @return FileParserInterface
     * @throws Exception
     */
    public static function make(string $filePath): FileParserInterface
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'xlsx':
            case 'xls':
            case 'csv':
                return app(ExcelParser::class);
            case 'pdf':
                return app(PdfParser::class);
            default:
                throw new Exception("Tipo de arquivo não suportado: {$extension}");
        }
    }
}
