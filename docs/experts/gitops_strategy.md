# GitOps Strategy & Automation

## Padrões de Commit
Utilizamos **Conventional Commits** para manter o histórico do repositório legível e automatizável.

- `feat(conversion)`: Novas funcionalidades de conversão.
- `fix(queue)`: Correções em jobs ou fila.
- `docs(experts)`: Atualizações na documentação técnica.
- `refactor(parser)`: Melhoria na estrutura do código sem mudar comportamento.

## Workflow de Release
1. **Branching**: `main` para produção, `staging` para testes. Features em branches curtas (`feat/nome-feature`).
2. **CI/CD**: Workflows em `.github/workflows` validam lint e testes antes do merge.
3. **Versioning**: Seguimos SemVer (Semantic Versioning).

## Commit Suggestion para este PR
`docs(experts): consolidate expert persona documentation and update job timeout`
