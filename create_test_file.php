<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = [
    'Numero RPS',
    'Serie',
    'Tipo',
    'Data Emissao',
    'Competencia',
    'CPF/CNPJ Tomador',
    'Razao Social',
    'Inscricao Municipal',
    'Logradouro',
    'Numero',
    'Complemento',
    'Bairro',
    'Cod Municipio',
    'UF',
    'CEP',
    'Valor Servico',
    'Aliquota',
    'Item Lista',
    'Discriminacao'
];

$sheet->fromArray($headers, NULL, 'A1');

// Data Row 1
$data = [
    '1001',
    'A',
    '1',
    '2025-12-01',
    '2025-12',
    '12345678000199',
    'Empresa Teste LTDA',
    '123456',
    'Rua das Flores',
    '100',
    'Sala 1',
    'Centro',
    '3550308',
    'SP',
    '01001000',
    '1500.00',
    '0.05',
    '14.01',
    'Serviço de Consultoria em TI'
];

$sheet->fromArray($data, NULL, 'A2');

$writer = new Xlsx($spreadsheet);
$writer->save('test_rps.xlsx');

echo "Test file created: test_rps.xlsx\n";
