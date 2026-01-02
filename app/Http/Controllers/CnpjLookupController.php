<?php

namespace App\Http\Controllers;

use App\Services\CnpjLookupService;
use Illuminate\Http\Request;

class CnpjLookupController extends Controller
{
    public function lookup(string $cnpj, CnpjLookupService $service)
    {
        try {
            // Validate CNPJ format
            if (!$service->isValid($cnpj)) {
                return response()->json([
                    'error' => 'CNPJ inválido'
                ], 400);
            }

            // Lookup data
            $data = $service->lookup($cnpj);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
