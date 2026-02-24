<?php

namespace App\Contracts;

interface FileParserInterface
{
    /**
     * Parse the given file and return an array of rows.
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array;
}
