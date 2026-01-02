# Fix: Página de Mapeamento em Branco

## Problema Identificado
A página de mapeamento estava aparecendo em branco porque o campo `meta_data` dos uploads estava `null`. Isso acontecia quando havia algum erro durante a extração dos headers no momento do upload.

## Causa Raiz
No `UploadController::store()`, se ocorresse qualquer exceção durante a extração dos headers, o erro era silenciosamente ignorado (try-catch vazio), resultando em `meta_data = null`.

## Solução Implementada

### 1. Extração On-Demand de Headers
Modificado o método `UploadController::show()` para:
- Verificar se `meta_data` está vazio ou não contém headers
- Se estiver vazio, tentar extrair os headers naquele momento
- Se falhar, redirecionar com mensagem de erro clara

### 2. Debug Info Adicionado
Adicionado um painel de debug na página `Upload/Show.vue` que mostra:
- Quantas colunas foram detectadas
- Mensagem de erro se nenhuma coluna for encontrada

### 3. Melhor Tratamento de Erros
- Logs de erro são registrados quando a extração falha
- Usuário é redirecionado com mensagem clara do problema
- Não há mais falhas silenciosas

## Como Testar a Correção

1. **Recarregue a página de mapeamento** no navegador
   - O controller agora vai tentar extrair os headers automaticamente
   - Você verá um painel azul mostrando quantas colunas foram detectadas

2. **Se ainda aparecer em branco:**
   - Verifique o console do navegador (F12) para erros JavaScript
   - Verifique os logs do Laravel em `storage/logs/laravel.log`
   - O painel de debug mostrará "0 colunas detectadas"

3. **Para novos uploads:**
   - Faça um novo upload do arquivo `test_rps.xlsx`
   - Agora os headers serão extraídos corretamente

## Arquivos Modificados
- `app/Http/Controllers/UploadController.php` - Extração on-demand de headers
- `resources/js/Pages/Upload/Show.vue` - Painel de debug adicionado

## Próximos Passos
- Remover o painel de debug após confirmar que está funcionando
- Adicionar validação do formato do arquivo antes do upload
- Melhorar mensagens de erro para o usuário final
