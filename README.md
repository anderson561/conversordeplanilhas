# XMLConverter: O Atalho para sua Automação Contábil 🚀

> **Transforme o caos de planilhas financeiras em arquivos XML impecáveis em segundos.**

O **XMLConverter** é uma plataforma SaaS desenhada para contadores e empresas que precisam de agilidade. Converta planilhas Excel e arquivos PDF (extratos, notas) em arquivos de importação padrão **Domínio Sistemas** e **ABRASF** com precisão cirúrgica e zero estresse.

## ✨ O que você ganha com o XMLConverter?

- **Mapeamento sem Esforço**: Detecção automática inteligente de colunas essenciais (Data, Valor, CNPJ). Você não precisa configurar nada.
- **DNA Contábil**: Extração avançada de PDFs complexos. Filtramos automaticamente ruídos (transferências/créditos) para focar apenas no que importa.
- **Zero Erros de Importação**: Datas e timestamps formatados rigorosamente nos padrões estritos (`AAAA-MM-DD` e ISO-8601).
- **Processamento em Escala**: Envie centenas de linhas de uma vez. Nosso sistema de fila processa tudo em segundo plano enquanto você foca no que importa.
- **Histórico e Segurança**: Acesse suas conversões passadas e baixe os arquivos a qualquer momento em um ambiente seguro e isolado.

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

# 🚀 SaaS Conversor: PDF para XML (ABRASF/Domínio)

[![GitHub Pages](https://img.shields.io/badge/Status-Online-success)](https://anderson561.github.io/conversordeplanilhas/)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red)](https://laravel.com)

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
