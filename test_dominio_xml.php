<?php
// Test script to verify correct XML structure for Domínio Sistemas

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

$root = $dom->createElement('CompNfse');
$root->setAttribute('xmlns', 'http://www.abrasf.org.br/nfse.xsd');
$dom->appendChild($root);

$nfse = $dom->createElement('Nfse');
$nfse->setAttribute('versao', '2.04');
$root->appendChild($nfse);

$infNfse = $dom->createElement('InfNfse');
$infNfse->setAttribute('Id', 'nfse1');
$nfse->appendChild($infNfse);

// Basic info
$infNfse->appendChild($dom->createElement('Numero', '1'));
$infNfse->appendChild($dom->createElement('CodigoVerificacao', 'ABC123'));
$infNfse->appendChild($dom->createElement('DataEmissao', '2025-12-02T00:00:00'));

// Servico
$servico = $dom->createElement('Servico');
$infNfse->appendChild($servico);

$valores = $dom->createElement('Valores');
$servico->appendChild($valores);
$valores->appendChild($dom->createElement('ValorServicos', '1500.00'));
$valores->appendChild($dom->createElement('Aliquota', '0.0500'));

$servico->appendChild($dom->createElement('IssRetido', '2'));
$servico->appendChild($dom->createElement('ItemListaServico', '01.01'));
$servico->appendChild($dom->createElement('Discriminacao', 'Teste'));
$servico->appendChild($dom->createElement('CodigoMunicipio', '3550308'));
$servico->appendChild($dom->createElement('ExigibilidadeISS', '1'));

// Prestador - RazaoSocial FIRST
$prestador = $dom->createElement('PrestadorServico');
$infNfse->appendChild($prestador);

$prestador->appendChild($dom->createElement('RazaoSocial', 'PRESTADOR LTDA'));

$cpfCnpjPrest = $dom->createElement('CpfCnpj');
$prestador->appendChild($cpfCnpjPrest);
$cpfCnpjPrest->appendChild($dom->createElement('Cnpj', '00000000000000'));

$prestador->appendChild($dom->createElement('InscricaoMunicipal', '000000'));

// Tomador
$tomador = $dom->createElement('TomadorServico');
$infNfse->appendChild($tomador);

$identificacaoTomador = $dom->createElement('IdentificacaoTomador');
$tomador->appendChild($identificacaoTomador);

$cpfCnpjTomador = $dom->createElement('CpfCnpj');
$identificacaoTomador->appendChild($cpfCnpjTomador);
$cpfCnpjTomador->appendChild($dom->createElement('Cnpj', '12345678901234'));

$tomador->appendChild($dom->createElement('RazaoSocial', 'TOMADOR LTDA'));

echo $dom->saveXML();
