# Documentação Técnica - ABRASF XML Converter SaaS

Este documento detalha a arquitetura, as lógicas de negócio e as decisões técnicas tomadas durante o desenvolvimento do projeto.

## 📦 Arquitetura do Sistema

O sistema segue o padrão MVC do Laravel, enriquecido com a arquitetura de **Services** para separar a lógica de negócio pesada dos controladores.

### 🔌 Componentes Principais

1.  **FileParserService**: 
    - Responsável pela extração bruta de dados.
    - Utiliza `PhpSpreadsheet` para arquivos Excel e `pdfparser` para PDFs.
    - Implementa lógica de limpeza de caracteres especiais e normalização de números brasileiros.

2.  **MappingService**:
    - O "cérebro" do sistema. Analisa os cabeçalhos das planilhas e detecta automaticamente o significado de cada coluna.
    - **Palavras-chave**: Suporta múltiplos sinônimos para campos financeiros (ex: 'total', 'locatários', 'preço', etc).

3.  **Generator Factory & Strategy (Pattern)**:
    - Implementa o **Strategy Pattern** através da `OutputGeneratorInterface`.
    - Cada formato (Salvador, NFe, Domínio TXT, CSV) é uma estratégia isolada.
    - O `GeneratorFactory` centraliza a inteligência de qual gerador instanciar baseado no `xml_type` do upload.

4.  **ConversionService**:
    - Padronizado para utilizar a `GeneratorFactory`. O serviço está agora desacoplado dos geradores individuais, facilitando a adição de novos formatos.

## ⚙️ Fluxo de Processamento (Filas)

Para evitar que o servidor "trave" em uploads grandes, o sistema utiliza o padrão de **Background Jobs**:
1.  O `UploadController` salva o arquivo e despacha o job `ProcessConversionJob`.
2.  O worker do Redis assume o processamento.
3.  Ao finalizar, o status é atualizado no banco e o e-mail de conclusão é disparado via `ConversionCompletedMail`.

## 🛡️ Segurança e Integridade

- **Sanitização de XML**: Proteção robusta contra ataques XXE (XML External Entity) utilizando `DOMDocument` e desativação de carregamento de entidades externas.
- **Multitenancy**: Cada usuário só tem acesso aos seus próprios arquivos e registros de mapeamento.
- **Validação Condicional de SSL**: Em produção, a verificação SSL para lookup de CNPJ é obrigatória e inquebrável.

## 🛠️ Manutenção e Monitoramento

- **Backups**: Configurado com `spatie/laravel-backup` via `config/backup.php`. 
- **Logs**: Todas as conversões geram logs no banco de dados e em arquivos locais para depuração.
- **Agendador (Scheduler)**: Gerencia a limpeza automática de arquivos temporários e backups diários.

## 🧪 Suíte de Testes

O sistema conta com 32 testes automatizados (`tests/Feature` e `tests/Unit`) cobrindo:
- Fluxos de autenticação completos.
- Lógica de mapeamento de colunas financeiras.
- Disparo de e-mails asíncronos.
- Integridade estrutural dos XMLs gerados.

---
*Para dúvidas técnicas ou suporte, consulte o log de desenvolvimento em `task.md`.*
