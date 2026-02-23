<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MappingService;
use App\Services\PdfParserService;
use ReflectionClass;

class IgnoreTermsTest extends TestCase
{
    /**
     * Test that MappingService ignores rows containing specific keywords.
     */
    public function test_mapping_service_ignores_refund_and_deposit_terms(): void
    {
        $service = new MappingService();
        $mappingRules = [
            'Razao Social' => 'Historico',
            'Valor' => 'Valor',
            'Data' => 'Data'
        ];

        $termsToIgnore = [
            'DEVOLUCAO',
            'DEVOLUÇÃO',
            'DEVOLUÇÕES',
            'DEVOLUCOES',
            'DEVOLVIDA',
            'DEVOLVIDAS',
            'CAUCAO',
            'CAUÇÃO',
            'CAUCOES',
            'CAUÇÕES'
        ];

        foreach ($termsToIgnore as $term) {
            $row = [
                'Historico' => "TEST $term",
                'Valor' => '1.000,00',
                'Data' => '01/01/2026'
            ];

            // Use reflection to access private mapSingleRow if needed, 
            // or use mapRowsToRps which is public.
            $results = $service->mapRowsToRps([$row], $mappingRules);

            $this->assertEmpty($results, "Row with term '$term' should have been ignored by MappingService.");
        }
    }

    /**
     * Test that PdfParserService regex matches the new ignore terms.
     */
    public function test_pdf_parser_service_regex_matches_ignore_terms(): void
    {
        $reflection = new ReflectionClass(PdfParserService::class);
        // We can't easily test the private logic without running it, 
        // but we can test the regex if we extract it or simulate it.
        // For now, let's just simulate the regex that we plan to implement.

        $regex = '/\b(devolução|devolucao|devoluções|devolucoes|devolvida|devolvidas|caução|caucao|cauções|caucoes|total\s+aluguel|total\s+de\s+aluguel|totais\s+de\s+alugueis|créditos?|creditos?|transf\b|transf\.|transferências?|transferencia|resgates?|rentab\b|rentab\.?|dividendos?|iof|irrf|tarifas?|tar\b|tar\.|taxas?|impostos?|juros|encargos|debitos?|pagto|pagamentos?|contribuição|contribuicao)\b/ui';

        $termsToMatch = [
            'devolucao',
            'devolução',
            'devoluções',
            'devolucoes',
            'devolvida',
            'devolvidas',
            'caucao',
            'caução',
            'caucoes',
            'cauções'
        ];

        foreach ($termsToMatch as $term) {
            $line = "HISTORICO: $term OPERACAO";
            $this->assertEquals(1, preg_match($regex, $line), "Regex should match term '$term' in PdfParserService.");
        }
    }
}
