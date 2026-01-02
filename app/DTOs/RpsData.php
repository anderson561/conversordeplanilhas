<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class RpsData extends Data
{
    public function __construct(
        public ?string $numero,
        public ?string $serie,
        public ?string $tipo,
        public ?string $dataEmissao,
        public ?string $competencia,
        public ?TomadorData $tomador,
        public ?ServicoData $servico,
    ) {
    }
}


