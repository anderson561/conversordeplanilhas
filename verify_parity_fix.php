<?php

use App\Services\XmlGeneratorService;
use App\DTOs\RpsData;
use App\DTOs\TomadorData;
use App\DTOs\ServicoData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tomador = new TomadorData(
    razaoSocial: 'TEST COMPANY',
    cpfCnpj: '00000000000100',
    inscricaoMunicipal: null,
    endereco: null
);

$servico = new ServicoData(
    valorServico: 100.0,
    valorDeducoes: 0.0,
    valorPis: 0.0,
    valorCofins: 0.0,
    valorInss: 0.0,
    valorIr: 0.0,
    valorCsll: 0.0,
    issRetido: 2.0,
    valorIss: 5.0,
    valorIssRetido: 0.0,
    outrasRetencoes: 0.0,
    baseCalculo: 100.0,
    aliquota: 0.05,
    valorLiquidoNfse: 100.0,
    descontoIncondicionado: 0.0,
    descontoCondicionado: 0.0,
    itemListaServico: '01.01',
    codigoCnae: '123456',
    codigoTributacaoMunicipio: '0101',
    discriminacao: 'TEST SERVICE',
    codigoMunicipio: '2927408'
);

$rps = new RpsData(
    numero: '1',
    serie: 'A',
    tipo: '1',
    dataEmissao: '2025-12-05',
    competencia: '2025-12-05',
    tomador: $tomador,
    servico: $servico
);

$generator = new XmlGeneratorService();
$xml = $generator->generateBatch([$rps], '1', ['cnpj' => '24890395000103']);

preg_match('/<DataEmissao>(.+?)<\/DataEmissao>/', $xml, $m1);
preg_match('/<Competencia>(.+?)<\/Competencia>/', $xml, $m2);

echo "DataEmissao: " . $m1[1] . "\n";
echo "Competencia: " . $m2[1] . "\n";

if ($m1[1] === $m2[1]) {
    echo "SUCCESS: EXACT PARITY ACHIEVED.\n";
} else {
    echo "FAIL: DATES ARE DIFFERENT.\n";
}
