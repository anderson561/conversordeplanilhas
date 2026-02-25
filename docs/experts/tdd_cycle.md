# TDD Execution Cycle - Processamento de Planilhas

## Ciclo RED-GREEN-REFACTOR
Garantimos que cada funcionalidade crítica de conversão seja precedida por um teste.

1. **RED**: Escrever teste no `SystemFlowTest` que simula um erro de timeout se o job passar de 6s.
2. **GREEN**: Implementar `set_time_limit(6)` no `ProcessConversionJob`.
3. **REFACTOR**: Isolar a lógica de timeout em um middleware de job para facilitar a reutilização.

## Testing Coverage Map
- **Mapeamento de Colunas**: Coberto (Standard Mapping).
- **Geração XML**: Coberto (XmlGeneratorService).
- **Tratamento de PDF**: Em progresso (PdfParser).

> [!NOTE]
> Código sem teste é código legado. Mantenha a cobertura acima de 80%.
