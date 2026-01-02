<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Upload;
use App\Services\FileParserService;
use Illuminate\Support\Facades\Storage;

$parser = new FileParserService();

echo "Verificando todos os uploads...\n\n";

foreach (Upload::all() as $upload) {
    echo "Upload ID: {$upload->id}\n";
    echo "Nome: {$upload->original_name}\n";
    echo "User ID: {$upload->user_id}\n";
    echo "Meta data: " . json_encode($upload->meta_data) . "\n";

    if (empty($upload->meta_data) || !isset($upload->meta_data['headers'])) {
        echo "⚠️  Headers faltando! Tentando extrair...\n";

        try {
            $fullPath = Storage::path($upload->file_path);
            $headers = $parser->getHeaders($fullPath);

            $upload->update([
                'meta_data' => ['headers' => $headers]
            ]);

            echo "✅ Headers extraídos: " . count($headers) . " colunas\n";
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ Headers OK: " . count($upload->meta_data['headers']) . " colunas\n";
    }

    echo str_repeat('-', 50) . "\n\n";
}

echo "Concluído!\n";
