<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class TomadorData extends Data
{
    public function __construct(
        public ?string $cpfCnpj,
        public ?string $razaoSocial,
        public ?string $inscricaoMunicipal,
        public ?EnderecoData $endereco,
    ) {
    }
}
