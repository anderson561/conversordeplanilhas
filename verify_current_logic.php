<?php

require 'vendor/autoload.php';

use App\Services\XmlGeneratorNFeService;

$service = new XmlGeneratorNFeService();

// Mock Rps structure using stdClass
$rps = new stdClass();
$rps->dataEmissao = '19/12/2025';

$rps->tomador = new stdClass();
$rps->tomador->cpfCnpj = '12345678000199';
$rps->tomador->razaoSocial = 'DUAS GERAÇÕES LTDA';

$rps->tomador->endereco = new stdClass();
$rps->tomador->endereco->logradouro = ''; // Empty to test fallback
$rps->tomador->endereco->numero = '';
$rps->tomador->endereco->bairro = '';
$rps->tomador->endereco->codigoMunicipio = '';
$rps->tomador->endereco->xMun = '';
$rps->tomador->endereco->uf = '';
$rps->tomador->endereco->cep = '';

$rps->servico = new stdClass();
$rps->servico->valorServico = 7000.00;

// Generate XML
$xml = $service->generateBatchXml([$rps], 'BATCH_TEST', [
    'cnpj' => '12314872000103',
    'razao_social' => 'EMPRESA PRESTADORA',
    'endereco' => 'RUA TESTE, 100',
    'bairro' => 'CENTRO',
    'municipio' => 'SALVADOR',
    'uf' => 'BA',
    'cep' => '40000000'
], 'BA', 247100);

file_put_contents('c:/Users/ANDERSON/php/output_test.xml', $xml);
echo "XML generated to output_test.xml\n";
