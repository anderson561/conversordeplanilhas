<?php

require 'vendor/autoload.php';

use App\DTOs\RpsData;
use App\DTOs\TomadorData;
use App\DTOs\ServicoData;
use App\Services\XmlGeneratorService;

$service = new XmlGeneratorService();

$rps = new RpsData(
    numero: '1',
    serie: 'A',
    tipo: 1,
    dataEmissao: '2025-12-02',
    competencia: '2025-12-02',
    tomador: new TomadorData(
        cpfCnpj: '12345678901',
        razaoSocial: 'Teste Cliente',
        inscricaoMunicipal: null,
        endereco: null
    ),
    servico: new ServicoData(
        valorServico: 1500.00,
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
        baseCalculo: 0,
        aliquota: 5.00,
        valorLiquidoNfse: 0,
        descontoIncondicionado: 0,
        descontoCondicionado: 0,
        itemListaServico: '01.01',
        codigoCnae: '',
        codigoTributacaoMunicipio: '',
        discriminacao: 'Servico de teste',
        codigoMunicipio: '3550308'
    )
);

echo $service->generateBatchXml([$rps], '1');
