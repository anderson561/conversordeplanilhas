<?php

require __DIR__ . '/vendor/autoload.php';

use App\DTOs\EnderecoData;
use App\DTOs\TomadorData;
use App\DTOs\ServicoData;
use App\DTOs\RpsData;

try {
    echo "Testando instanciacao de DTOs...\n";

    $endereco = new EnderecoData(
        logradouro: 'Rua Teste',
        numero: '123',
        complemento: null,
        bairro: 'Centro',
        codigoMunicipio: '1234567',
        uf: 'SP',
        cep: '12345-678'
    );
    echo "✅ EnderecoData OK\n";

    $tomador = new TomadorData(
        cpfCnpj: '12345678901',
        razaoSocial: 'Teste Ltda',
        inscricaoMunicipal: null,
        endereco: $endereco
    );
    echo "✅ TomadorData OK\n";

    echo "Todos os DTOs carregados corretamente!\n";

} catch (Error $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
