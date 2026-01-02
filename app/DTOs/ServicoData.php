<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class ServicoData extends Data
{
    public function __construct(
        public ?float $valorServico,
        public ?float $valorDeducoes,
        public ?float $valorPis,
        public ?float $valorCofins,
        public ?float $valorInss,
        public ?float $valorIr,
        public ?float $valorCsll,
        public ?float $issRetido,
        public ?float $valorIss,
        public ?float $valorIssRetido,
        public ?float $outrasRetencoes,
        public ?float $baseCalculo,
        public ?float $aliquota,
        public ?float $valorLiquidoNfse,
        public ?float $descontoIncondicionado,
        public ?float $descontoCondicionado,
        public ?string $itemListaServico,
        public ?string $codigoCnae,
        public ?string $codigoTributacaoMunicipio,
        public ?string $discriminacao,
        public ?string $codigoMunicipio,
    ) {
    }
}
