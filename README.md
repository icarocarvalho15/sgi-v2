# SGI v2.0 — ERP Especializado para Indústrias Gráficas & Brindes Personalizados

[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL RLS](https://img.shields.io/badge/PostgreSQL-18%20(Native%20RLS)-336791?logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![React 19](https://img.shields.io/badge/React-19.x-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![Inertia.js v2](https://img.shields.io/badge/Inertia.js-v2.x-9553E9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tests Passing](https://img.shields.io/badge/Tests-59%20passed%20(265%20assertions)-success?logo=github-actions&logoColor=white)](tests)
[![Security Architecture](https://img.shields.io/badge/Security-Double--Lock%20RLS-emerald)](#segurança-double-lock--multi-tenancy)

O **SGI v2.0** é um ERP Multi-Tenant SaaS de alta performance projetado especificamente para as complexidades operacionais e financeiras de **Indústrias Gráficas (Offset, Digital, Grandes Formatos, Flexografia)** e **Fabricantes de Brindes Personalizados**.

A plataforma combina uma fundação com **isolamento nativo de dados via PostgreSQL RLS**, matemática financeira de alta precisão sem ponto flutuante (**Zero-Float com `bcmath`**), motor de **status operacionais dinâmicos customizáveis por gráfica**, e uma divisão clara entre o **Painel Landlord SaaS** (administração de assinaturas e tenants) e o **ERP Operacional da Gráfica**.

---

## 🏛️ Pilares da Arquitetura

### 1. Segurança Double-Lock & Multi-Tenancy Nativo
- **Nível 1 (Aplicação):** Todos os Models operacionais herdam de `TenantModel`, aplicando automaticamente o `TenantScope` do Eloquent, injeção estrita de `tenant_id` e bloqueio de *Mass Assignment* e *Cross-Tenant Tampering*.
- **Nível 2 (Banco de Dados):** Ativação nativa de **PostgreSQL Row-Level Security (RLS)** em todas as tabelas de tenant via `PostgresRlsHelper::enableTenantRls()`. O PostgreSQL isola as linhas no kernel através da variável de sessão `app.current_tenant_id`, impedindo vazamento de dados mesmo em caso de falhas na camada da aplicação ou queries manuais.
- **Cofre de Credenciais (`VaultEncryption`):** Criptografia de ponta a ponta utilizando **AES-256-GCM com derivação de chaves via HKDF** para certificados digitais A1 e chaves de API.

### 2. Precisão Financeira Zero-Float (`bcmath`)
- Operações de formação de preços, markup, margem de contribuição, Custo Médio Ponderado (CMP) no estoque e conciliação bancária utilizam matemática de precisão arbitrária (`bcmath`).
- Dízimas periódicas e imprecisões de ponto flutuante (`float`) são terminantemente proibidas na regra de negócio.

### 3. Motor de Status Dinâmicos (Zero Hardcoded)
- Nenhuma etapa operacional é fixa no código-fonte.
- Cada gráfica define suas próprias etapas, cores hexadecimais, ordenação numérica, status inicial padrão e status conclusivo para os módulos de **Funil Comercial (CRM)**, **Orçamentos Técnicos** e **Chão de Fábrica (PCP / OPs)** através da tabela `tenant_statuses`.

### 4. Separação Rigorosa: Landlord SaaS vs Tenant ERP
- **Painel Landlord (`/admin/*`):** Exclusivo para SuperAdmins. Não pertence a nenhum tenant e não tem acesso aos dados operacionais das gráficas. Focado na gestão de empresas, subdomínios, planos de assinatura, métricas de faturamento (MRR/ARR) e logs de compliance.
- **Personificação Técnica Auditada (`Impersonation`):** O SuperAdmin só visualiza o ERP de um cliente mediante acionamento explícito de personificação técnica com justificativa de suporte. Todas as ações gravam endereço IP, data/hora e operador no log imutável de compliance.

---

## 📦 Módulos Implementados e em Operação

```
sgi-v2/
├── app/
│   ├── Core/Tenancy/          # Engine Multi-Tenant, RLS Helper, Scopes e TenantModel
│   ├── Modules/
│   │   ├── CentralAdmin/      # Painel Landlord, Gestão de Planos, Métricas SaaS e Impersonation
│   │   ├── CoreAdmin/         # Gestão de Status Dinâmicos Customizáveis do Tenant
│   │   ├── CRM/               # Clientes, Fornecedores, Endereços e Funil de Vendas Kanban
│   │   ├── Quotes/            # Orçamentos Técnicos Gráficos e Conversão 1-Clique em OPs
│   │   ├── SalesOrders/       # Pedidos de Venda Aprovados
│   │   ├── Production/        # Fichas Técnicas (BOM), Centros de Trabalho e Ordens de Produção
│   │   ├── Inventory/         # Múltiplos Depósitos, CMP e Razão Kardex Imutável
│   │   ├── Finance/           # Plano de Contas, Centros de Custo, Títulos, Fluxo de Caixa e DRE
│   │   └── Integrations/      # Gateways de Pagamento, Emissão Fiscal NFe/NFSe e Webhooks HMAC
```

### 🏢 1. Painel Landlord SaaS (Administração Central)
- **Gestão de Empresas & Assinaturas (`/admin/tenants`):**
  - Criação de tenants com provisionamento automático de subdomínio e status operacionais padrão.
  - Alteração de planos de assinatura e controle de status (`Ativa`, `Suspensa`, `Cancelada`).
- **Gestão de Planos SaaS (`/admin/plans`):**
  - Cadastro de pacotes comerciais (*Gráfica Starter*, *PCP Pro*, *Indústria Enterprise*).
  - Configuração de mensalidades, limites de usuários simultâneos, limites de OPs mensais e módulos liberados.
- **Métricas SaaS Globais (`/admin/metrics`):**
  - Monitoramento de **MRR** (Receita Recorrente Mensal), **ARR** anual projetado, contagem de usuários do ecossistema e composição da receita por plano.
- **Auditoria de Suporte (`/admin/audit-logs`):**
  - Rastreabilidade de sessões de suporte técnico (operador, empresa acessada, motivo, IP e duração).

### 👥 2. CRM & Funil de Vendas (CRM)
- **Clientes & Fornecedores (`/crm/partners`):**
  - Cadastro completo com múltiplos endereços de entrega e faturamento.
  - Validação estrita de documentos fiscais (CNPJ com algoritmo de dígitos verificadores e CPF).
  - Diferenciação de tipo de contato: Cliente Final, Agência de Publicidade, Revendedor Gráfico ou Fornecedor de Matéria-Prima.
- **Funil de Vendas Kanban (`/crm/kanban`):**
  - Quadro interativo estruturado sobre os status dinâmicos do tenant.
  - Movimentação de propostas e negócios com atualização de probabilidade e valor ponderado.

### 📄 3. Orçamentos Técnicos Gráficos & Conversão 1-Clique
- **Motor de Cálculo Técnico Gráfico (`/quotes`):**
  - Cálculo de tiragem, formato aberto, formato fechado e substrato/gramatura.
  - Custos detalhados de gravação de **Chapas CTP / Fotolitos**, tintas (escala CMYK e cores especiais Pantone), horas de máquina e acabamentos especiais (laminação BOPP, verniz UV localizado, corte-e-vinco).
  - Cálculo rigoroso de margem líquida e markup via `bcmath`.
- **Conversão em 1-Clique (`quotes/{quote}/convert`):**
  - A aprovação da proposta converte automaticamente o orçamento em um **Pedido de Venda** e dispara a criação da **Ordem de Produção (OP)** com a reserva técnica de insumos.

### 🏭 4. Engenharia de Produtos & Chão de Fábrica (PCP)
- **Fichas Técnicas / BOM (`/production/boms`):**
  - Árvore de insumos e matérias-primas gráficas com detecção e prevenção de dependência circular (grafo acíclico direcionado / DAG).
- **Ordens de Produção (`/production/orders`):**
  - Máquina de estados baseada nas etapas da gráfica (Aguardando Gravação CTP, Impressão Offset, Acabamento, Expedição).
  - Apontamento de tempo de produção por operador e máquina (`production_time_logs`).
  - Painel responsivo otimizado para tablets e monitores no chão de fábrica.

### 📦 5. Suprimentos & Controle de Estoque com Kardex Imutável
- **Razão Kardex Imutável (`/inventory/kardex`):**
  - Livro-razão com transações atômicas de banco e locks pessimistas (`SELECT ... FOR UPDATE`), garantindo consistência em altas concorrências.
  - Cálculo automatizado do **Custo Médio Ponderado (CMP)** a cada entrada de compra.
  - Bloqueio rigoroso de saldos negativos em baixas por consumo de OPs.
- **Saldos por Depósito (`/inventory/balances`):**
  - Visão em tempo real de matérias-primas (papéis, substratos, tintas, chapas) por almoxarifado/galpão.

### 💰 6. Módulo Financeiro, Relatórios & DRE
- **Contas a Pagar e Receber (`/finance/entries`):**
  - Lançamentos vinculados a Plano de Contas e Centros de Custo.
  - Suporte a parcelamentos, juros, multas, descontos e baixas parciais/totais.
- **Relatórios Gerenciais (`/finance/reports`):**
  - **Fluxo de Caixa Operacional:** Projeção diária e mensal de entradas e saídas.
  - **DRE Gerencial:** Demonstração do Resultado do Exercício com cálculo de Receita Bruta, Deduções, Custos dos Produtos Vendidos (CPV) e Margem de Contribuição.

### 🛡️ 7. Integrações Fiscais, Certificados & Gateways
- **Configurações Fiscais (`/settings/fiscal`):**
  - Armazenamento criptografado do Certificado Digital A1 no cofre `VaultEncryption` (AES-256-GCM).
  - Parâmetros tributários de NFe (produtos) e NFSe (serviços de impressão gráfica).
- **Emissão Fiscal Assíncrona via Fila:** Processamento seguro de notas sem travar a interface do usuário.
- **Receptor Global de Webhooks (`/api/webhooks/{provider}`):**
  - Validação de assinatura criptográfica HMAC.
  - Tabela de idempotência (`webhook_events`) que previne processamentos duplicados de baixas financeiras.

---

## 🛠️ Stack Tecnológica

| Camada | Tecnologia | Detalhes |
|---|---|---|
| **Linguagem Backend** | PHP 8.5.x | Recursos modernos de tipagem estrita (`declare(strict_types=1);`) |
| **Framework Web** | Laravel 12.x | Arquitetura modular por domínios (`app/Modules/*`) |
| **Banco de Dados** | PostgreSQL 18.x | **Native Row-Level Security (RLS)** em todas as tabelas de tenant |
| **Camada Frontend** | React 19.x | Componentização moderna com Hooks e TypeScript |
| **Integração SPA** | Inertia.js v2.x | Experiência de SPA sem a complexidade de APIs REST desacopladas |
| **Estilização** | Tailwind CSS v4.x | Design System escuro (Dark Theme) voltado à usabilidade industrial |
| **Iconografia** | Lucide React | Biblioteca de ícones vetoriais modernos |
| **Build & Bundler** | Vite 8.x | Compilação ultrarrápida com Hot Module Replacement (HMR) |
| **Suíte de Testes** | PHPUnit 12.x | 59 testes automatizados cobrindo isolamento cross-tenant e regras de negócio |

---

## 🚀 Como Executar o Projeto Localmente

### Pré-requisitos
- **PHP 8.5+** com as extensões ativas: `pdo_pgsql`, `bcmath`, `openssl`, `mbstring`, `fileinfo`.
- **Composer 2.x**
- **Node.js 22+** e **npm**
- **PostgreSQL 16+** (porta padrão 5432) com uma base de dados criada (ex: `sgi_v2`).

### 1. Clonar o Repositório
```bash
git clone https://github.com/icarocarvalho15/sgi-v2.git
cd sgi-v2
```

### 2. Instalar Dependências do Backend e Frontend
```bash
composer install
npm install
```

### 3. Configurar Variáveis de Ambiente
Copie o arquivo de exemplo e configure sua conexão com o PostgreSQL:
```bash
cp .env.example .env
php artisan key:generate
```

Certifique-se de configurar as credenciais do seu banco de dados no arquivo `.env`:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgi_v2
DB_USERNAME=seu_usuario_postgres
DB_PASSWORD=sua_senha_postgres

QUEUE_CONNECTION=sync
CACHE_STORE=database
SESSION_DRIVER=database
```

### 4. Executar Migrations e Seeder Completo
Execute as migrations (que criam as tabelas com chaves estrangeiras compostas e ativam o PostgreSQL RLS nativo) e o seeder de diagnóstico completo:
```bash
php artisan migrate:fresh --seed --seeder=FullSystemDemoSeeder
```

### 5. Compilar os Assets Frontend
Para ambiente de produção ou testes:
```bash
npm run build
```
Para desenvolvimento ativo com Hot Reload:
```bash
npm run dev
```

### 6. Iniciar o Servidor Laravel
```bash
php artisan serve
```
Acesse a aplicação no seu navegador em: **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Credenciais Pré-configuradas para Testes

O seeder `FullSystemDemoSeeder` cria contas demonstrativas prontas para inspeção:

| Perfil | E-mail | Senha | Rota de Entrada | Finalidade |
|---|---|---|---|---|
| **SuperAdmin Landlord** | `admin@sgi-erp.com` | `Admin1234!` | `/admin/tenants` | Gestão de empresas, assinaturas, planos SaaS, métricas MRR/ARR e auditoria. |
| **Operador da Gráfica** | `operador@express.com` | `Operador123!` | `/crm/partners` | Operação do ERP da gráfica (Orçamentos, Fichas BOM, OPs, Estoque, Financeiro). |

> **Dica de Usabilidade:** Na tela de Login (`http://localhost:8000/login`), existem botões de **1-Clique** para preencher automaticamente as credenciais de teste tanto do Operador quanto do SuperAdmin.

---

## 🧪 Suíte de Testes Automatizados

O projeto conta com testes de ponta a ponta garantindo o isolamento multi-tenant, a integridade da máquina de estados e os cálculos financeiros:

```bash
php artisan test
```

### Resultado Atual:
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\MultiTenantFoundationTest
   PASS  Tests\Feature\CrmPartnersTest
   PASS  Tests\Feature\QuotesTest
   PASS  Tests\Feature\ProductionEngineTest
   PASS  Tests\Feature\InventoryKardexTest
   PASS  Tests\Feature\FinanceAndFiscalTest
   PASS  Tests\Feature\AdminAndStatusManagementTest
   PASS  Tests\Feature\FullSystemDiagnosticTest

  Tests:    59 passed (265 assertions)
  Duration: 14.71s (100% green)
```

---

## 📜 Licença

Propriedade intelectual de **SGI v2.0**. Todos os direitos reservados.
Desenvolvido sob padrões rigorosos de engenharia de software e segurança corporativa.
