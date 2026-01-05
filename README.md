# ABRASF XML Converter SaaS 🚀

Converta planilhas financeiras (Excel/PDF) em arquivos XML compatíveis com sistemas de contabilidade (Domínio Sistemas, ABRASF Salvador, e muito mais) de forma simples e segura.

## ✨ Principais Funcionalidades

- **Mapeamento Inteligente**: Detecção automática de colunas críticas (Data, Valor, Razão Social, CNPJ).
- **Suporte Multi-Formato**: Processamento nativo de arquivos `.xlsx`, `.xls`, `.csv` e `.pdf`.
- **Fila de Processamento (Scalability)**: Arquivos grandes são processados em segundo plano via Redis.
- **Notificações em Tempo Real**: Alertas por e-mail assim que seus arquivos estão prontos.
- **Segurança Robusta**: Auditoria de segurança completa, sanitização de dados e isolamento de usuários (Multi-tenant).
- **Backups Automáticos**: Rotina diária de backup do banco de dados e arquivos.

## 🛠️ Stack Tecnológica

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue.js 3 + Inertia.js + Tailwind CSS
- **Fila/Cache**: Redis
- **Banco de Dados**: SQLite (Desenvolvimento) / MySQL (Recomendado para Produção)

## 📥 Instalação

### Pré-requisitos
- PHP 8.2 ou superior
- Composer
- Node.js & NPM
- Redis (Obrigatório para o sistema de filas)

### Passo a Passo

1. **Clonar o Repositório**
   ```bash
   git clone https://github.com/anderson561/conversordeplanilhas.git
   cd conversordeplanilhas
   ```

2. **Instalar Dependências**
   ```bash
   composer install
   npm install
   ```

3. **Configuração de Ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Banco de Dados & Assets**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   npm run build
   ```

5. **Iniciar o Sistema de Filas (Obrigatório)**
   ```bash
   php artisan queue:work
   ```

## 📖 Documentação Adicional

- [**Guia de Implantação (Vapor/Forge)**](.gemini/antigravity/brain/af1a3ff3-b7f4-43e7-af20-37e2509ce46d/deployment.md)
- [**Documentação Técnica**](DOCUMENTATION.md)
- [**Dicas para Importação no Domínio**](.gemini/antigravity/brain/af1a3ff3-b7f4-43e7-af20-37e2509ce46d/dominio_import_sem_lancamento.md)

## 📄 Licença

Este projeto é de uso exclusivo conforme acordado com o desenvolvedor.
