<?php

namespace App\Factories;

use App\Contracts\OutputGeneratorInterface;
use App\Services\XmlGeneratorService;
use App\Services\XmlGeneratorNFeService;
use App\Services\CsvGeneratorService;
use App\Services\DominioTxtGeneratorService;
use InvalidArgumentException;

class GeneratorFactory
{
    /**
     * Resolve the appropriate generator based on the given type.
     *
     * @param string $type
     * @return OutputGeneratorInterface
     * @throws InvalidArgumentException
     */
    public function make(string $type): OutputGeneratorInterface
    {
        return match ($type) {
            'servico' => app(XmlGeneratorService::class),
            'saida' => app(XmlGeneratorNFeService::class),
            'dominio_txt' => app(DominioTxtGeneratorService::class),
            'csv' => app(CsvGeneratorService::class),
            default => throw new InvalidArgumentException("Unknown generator type: {$type}"),
        };
    }
}
