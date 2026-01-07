<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$xmlGen = new \App\Services\XmlGeneratorService();

// Mock RpsData
$rpsData = new \App\DTOs\RpsData(
    numero: '123',
    serie: '1',
    tipo: '1',
    dataEmissao: '07/01/2026',
    competencia: '2026-01-01',
    tomador: new \App\DTOs\TomadorData(
        cpfCnpj: '12345678000190',
        razaoSocial: 'TOMADOR TESTE',
        inscricaoMunicipal: null,
        endereco: new \App\DTOs\EnderecoData(
            logradouro: 'RUA TESTE',
            numero: '123',
            complemento: null,
            bairro: 'CENTRO',
            codigoMunicipio: '2927408',
            uf: 'BA',
            cep: '40000000',
            xMun: 'Salvador'
        )
    ),
    servico: new \App\DTOs\ServicoData(
        valorServico: 100.0,
        valorDeducoes: 0,
        valorPis: 0,
        valorCofins: 0,
        valorInss: 0,
        valorIr: 0,
        valorCsll: 0,
        issRetido: 2,
        valorIss: 0,
        valorIssRetido: 0,
        outrasRetencoes: 0,
        baseCalculo: 100.0,
        aliquota: 0,
        valorLiquidoNfse: 100.0,
        descontoIncondicionado: 0,
        descontoCondicionado: 0,
        itemListaServico: '0101',
        codigoCnae: null,
        codigoTributacaoMunicipio: null,
        discriminacao: 'Serviço de teste',
        codigoMunicipio: '2927408'
    )
);

$xml = $xmlGen->generateBatchXml([$rpsData]);

if (strpos($xml, '<mod>44</mod>') !== false) {
    echo "✓ Tag <mod>44</mod> found in IdentificacaoRps!\n";
} else {
    echo "✗ Tag <mod>44</mod> NOT found!\n";
    echo $xml;
}
