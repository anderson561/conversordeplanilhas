<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class EnderecoData extends Data
{
    public function __construct(
        public ?string $logradouro,
        public ?string $numero,
        public ?string $complemento,
        public ?string $bairro,
        public ?string $codigoMunicipio,
        public ?string $uf,
        public ?string $cep,
        public ?string $xMun = null, // Added to store city name directly when code is not known
    ) {
    }
}
