# SGI v2.0 — Sistema de Gestão Integrada & CRM Industrial Multi-Tenant

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![React](https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16%2B_RLS-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7%2B-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Tests](https://img.shields.io/badge/Tests-48_Passed_|_298_Assertions-22C55E?style=for-the-badge&logo=checkmarx&logoColor=white)](#-testes-automatizados--diagnóstico)

O **SGI v2.0** é um ERP e CRM industrial de altíssima performance, densidade de dados e arquitetura militar de segurança, projetado sob medida para **indústrias de manufatura sob encomenda** (*Make-to-Order* / *Engineer-to-Order*), usinagens de precisão CNC, caldeirarias pesadas, estamparias e montagens eletromecânicas.

Construído como um **SaaS Multi-Tenant de Banco Único com Duplo Isolamento Lógico e Físico (Double Lock)**, o sistema integra de ponta a ponta desde o funil comercial e orçamentação técnica até o PCP no chão de fábrica, controle de estoque pelo razão Kardex imutável, apuração gerencial de DRE/Fluxo de Caixa e emissão fiscal assíncrona de NF-e/NFS-e com conciliação automática de gateways de pagamento.

---

## 🏛️ Arquitetura e Pilares de Engenharia

### 1. Duplo Isolamento Multi-Tenant (*Double Lock Strategy*)
Para garantir confidencialidade absoluta entre indústrias concorrentes hospedadas na mesma infraestrutura SaaS, o SGI v2.0 implementa duas barreiras inegociáveis de isolamento:

* **Camada 1 — Aplicação (Global Scopes & TenantContext):** Todos os modelos operacionais herdam de `TenantModel`, que intercepta queries via `TenantScope` aplicando o filtro obrigatório `tenant_id` e injeta automaticamente a chave primária da empresa em cada `create`.
* **Camada 2 — Banco de Dados (PostgreSQL Row-Level Security):** Todas as tabelas do ecossistema do tenant possuem RLS nativo ativado via `PostgresRlsHelper::enableTenantRls()`. Uma política em nível de engine (`USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid)`) rejeita consultas e mutações cross-tenant mesmo em caso de falha de código no backend ou execução de queries SQL diretas.
* **Chaves Estrangeiras Compostas:** Todas as relações filhas utilizam chaves compostas `(tenant_id, foreign_id) REFERENCES parent_table(tenant_id, id)` com `ON DELETE CASCADE`, impossibilitando a vinculação acidental de registros entre tenants distintos.

### 2. Cofre Criptográfico de Credenciais (*Vault Encryption*)
Certificados Digitais A1 (`.pfx`) e chaves privadas de Gateways de Pagamento (Asaas, Stripe, Mercado Pago) são armazenados através do serviço `VaultEncryption`:
* **Cifra:** Criptografia autenticada **AES-256-GCM**.
* **Derivação de Chaves (HKDF):** Salt e chave de sessão derivados dinamicamente combinando a `APP_KEY` do Laravel com o UUID do Tenant.
* **Dados Adicionais Autenticados (AAD):** O `tenant_id` atua como AAD durante a encriptação. Caso um registro seja corrompido ou copiado entre tenants, a descriptografia falha imediatamente por violação de autenticidade criptográfica.

### 3. Política de Zero Ponto Flutuante (*Zero Float Policy*)
Operações industriais de custeio, saldos de matéria-prima e lançamentos contábeis **jamais utilizam `float` ou `double`**:
* **Banco de Dados:** Colunas monetárias definidas como `NUMERIC(15, 2)` e quantidades/custos unitários como `NUMERIC(15, 4)`.
* **Backend:** Cálculos aritméticos executados exclusivamente via funções da extensão nativa `bcmath` (`bcadd`, `bcsub`, `bcmul`, `bcdiv`, `bccomp`) com arredondamento controlado.

### 4. Razão de Estoque Imutável (*Append-Only Kardex Ledger*)
* As movimentações de estoque são registradas na tabela `kardex_movements`, blindada contra `UPDATE` e `DELETE`.
* O cálculo do **Custo Médio Ponderado (CMP)** e do saldo instantâneo (`inventory_balances`) ocorre de forma atômica utilizando **Distributed Cache Locks** no Redis (`Cache::lock`) combinados com locks pessimistas no banco (`lockForUpdate`), prevenindo concorrência e condições de corrida entre múltiplos operadores da fábrica.

---

## 🚀 Stack Tecnológica

| Componente | Tecnologia | Detalhes & Finalidade |
| :--- | :--- | :--- |
| **Linguagem Backend** | **PHP 8.4+** | Tipagem estrita (`declare(strict_types=1)`), construtores promovidos, enums de primeira classe e `bcmath`. |
| **Framework Backend** | **Laravel 12.x** | Arquitetura modular domain-driven, rotas escopadas, policies, middleware e jobs assíncronos. |
| **Bridge Full-Stack** | **Inertia.js v2** | Comunicação SPA reativa com SSR sem overhead de APIs REST/GraphQL desnecessárias. |
| **Framework Frontend** | **React 19** | Componentização modular, controle estrito de estado, performance e responsividade. |
| **Tipagem Frontend** | **TypeScript 5.x** | Tipagem estrita em todas as páginas, componentes, DTOs e contratos de API. |
| **Estilização & UI** | **Tailwind CSS v4** | Design system industrial responsivo, dark mode nativo, glassmorphism e microinterações. |
| **Banco de Dados** | **PostgreSQL 16+** | Suporte avançado a Row-Level Security (RLS), índices compostos e locks transacionais. |
| **Cache & Mensageria** | **Redis 7+** | Cache de alta velocidade, sessões, distributed locks e filas assíncronas do Horizon. |
| **Segurança & ACL** | **Sanctum + Spatie** | Autenticação SPA segura e controle de permissões granular com escopo por Tenant (*Teams*). |
| **Criptografia** | **OpenSSL AES-256-GCM** | Cofre seguro HKDF com autenticação de dados associados (AAD). |

---

## 🧩 Os 6 Módulos Core do Sistema

```
SGI v2.0 Industrial Architecture
├── 1. Core Landlord & Multi-Tenancy  ──> Assinaturas SaaS, Gestão de Planos & Impersonation
├── 2. CRM & Orçamentos Sob Encomenda ──> Kanban B2B, Orçamento com Ficha Técnica & Conversão 1-Clique
├── 3. Engenharia de Produto & PCP    ──> BOMs, Roteiros, Centros de Trabalho & Ordens de Produção
├── 4. Suprimentos & Kardex Imutável  ──> Razão Append-Only, Custo Médio Ponderado (CMP) & Múltiplos Depósitos
├── 5. Módulo Financeiro Industrial   ──> Contas a Pagar/Receber, Baixa Parcial, DRE & Fluxo de Caixa
└── 6. Fiscal & Gateways de Pagamento ──> Emissão NF-e (XML/DANFE), Certificados A1 & Webhooks Idempotentes
```

### 1. Core Landlord & Gestão Multi-Tenancy
* **Planos SaaS:** Gestão de planos com limitação de usuários, armazenamento e módulos liberados (*Starter Industrial*, *Industrial Pro*, *Enterprise Custom*).
* **Painel Landlord:** Visão gerencial dos tenants, controle de assinaturas, status de faturamento e métricas de uso de disco/banco.
* **Impersonation com Auditoria:** Acesso de suporte técnico aos tenants com registro imutável em `impersonation_logs`.
* **RBAC Granular por Tenant:** Integração Spatie Permission isolada via `setPermissionsTeamId($tenant->id)` com perfis pré-configurados (*Admin da Indústria*, *Engenharia*, *PCP*, *Operador de Fábrica*, *Financeiro*, *Vendas*).

### 2. CRM & Vendas sob Encomenda
* **Gestão B2B:** Cadastro robusto de clientes e fornecedores (`partners`) com validação de CNPJ/CPF, Inscrição Estadual, múltiplos endereços (cobrança/entrega) e contatos.
* **Pipeline Kanban Visual:** Funil customizável com estágios parametrizados, probabilidades de fechamento e previsão de faturamento.
* **Orçamento Técnico com Engenharia Reversa:** Elaboração de orçamentos vinculando itens da Ficha Técnica (BOM). O sistema explode o custo estimado de materiais e operações por hora-máquina, aplicando markup e margem líquida.
* **Conversão 1-Clique em Pedido e OP:** A aprovação do orçamento (`QuoteStatus::Converted`) aciona a action `ConvertQuoteToSalesOrderAction`, gerando o Pedido de Venda (`sales_orders`), marcando o Deal como `WON` e criando e liberando automaticamente a Ordem de Produção (`production_orders`) com empenhos de materiais e operações.

### 3. Engenharia de Produto & PCP
* **Catálogo de Itens:** Insumos de matéria-prima, produtos semiacabados e produtos acabados com unidades de medida personalizadas (UN, KG, M, M2, L, CX).
* **Centros de Trabalho (`work_centers`):** Cadastro de máquinas, tornos CNC, células de solda e bancadas de teste com taxa horária de custo (R$/h) e capacidade diária em horas.
* **Fichas Técnicas (BOM):** Estrutura de materiais com suporte a versionamento, lote base e taxa de perda técnica (*scrap rate %*) para compensação de cavaco/retalhos.
* **Roteiros Operacionais (`routing_steps`):** Sequenciamento de operações com tempos de preparação (*setup*) e tempos de corrida (*run time* por unidade).
* **Ordens de Produção (OP):** Máquina de estados finita (`DRAFT` ➔ `RELEASED` ➔ `IN_PROGRESS` ➔ `COMPLETED` / `CANCELLED`), com empenho de materiais e apontamento de tempos por centro de trabalho.

### 4. Suprimentos & Kardex de Estoque
* **Multi-Depósito:** Almoxarifado Central de Insumos (`ALM-01`), Depósito de Produtos Acabados (`ALM-PA`), depósitos de quarentena e sucatas.
* **Kardex Imutável:** Registro atômico de movimentações de compra (`IN_PURCHASE`), consumo em produção (`OUT_PRODUCTION`), devoluções e entradas de produto acabado (`IN_PRODUCTION`).
* **Apontamento com Baixa Automática:** Ao apontar a produção parcial ou total de uma OP, o `ProductionOrderService` realiza a baixa atômica das matérias-primas na proporção produzida (ao Custo Médio atual) e dá entrada no produto acabado no almoxarifado destino.
* **Saldos Instantâneos:** Tabela derivada `inventory_balances` com travamento transacional distribuído.

### 5. Módulo Financeiro Industrial
* **Contas Financeiras:** Gerenciamento de contas correntes bancárias e caixas físicos de fábrica com saldo em tempo real.
* **Plano de Contas & Centros de Custo:** Classificação gerencial multinível (Receitas Operacionais de Venda, Custos de Matéria-Prima, Despesas Operacionais, Custos Fixos de Fábrica).
* **Contas a Pagar & Receber:** Geração automática a partir de compras e pedidos de venda, com desdobramento flexível em parcelas, cálculo de centavos e controle de vencimentos.
* **Baixas e Estornos Atômicos:** `SettleFinancialPaymentAction` e `ReverseFinancialPaymentAction` com suporte a baixas parciais, juros, multas e descontos, atualizando o saldo bancário em transação atômica.
* **Relatórios Gerenciais em Tempo Real:**
  * **DRE (Demonstração do Resultado do Exercício):** Apuração por competência com Receita Bruta, Deduções, Custo dos Produtos Vendidos (CPV/CMV), Lucro Bruto, Despesas Operacionais e Resultado Líquido.
  * **Fluxo de Caixa:** Visão diária, semanal e mensal por regime de caixa com conciliação bancária.

### 6. Integrações Fiscais (NF-e/NFS-e) & Gateways de Pagamento
* **Cofre de Certificados A1:** Armazenamento seguro de arquivos `.pfx` com senha criptografada via Vault Encryption, validade e controle de ambiente (Homologação / Produção).
* **Emissão Fiscal Assíncrona:** Fila dedicada de emissão (`EmitFiscalInvoiceJob` via `TenantAwareJob`) gerando XML no padrão MOC 4.00, cálculo de chave de acesso de 44 dígitos com dígito verificador ponderado Módulo 11 e protocolo de autorização SEFAZ.
* **Gateways de Pagamento Multiprovedor (Adapter Pattern):**
  * `AsaasAdapter`: Suporte nativo a Pix Dinâmico com Copia-e-Cola e QR Code, Boletos com código de barras e token de webhook.
  * `StripeAdapter`: Suporte a PaymentIntents internacionais, boletos e validação de assinatura HMAC SHA-256 (`Stripe-Signature`).
  * `MercadoPagoAdapter`: Suporte a Pix Dinâmico e validação HMAC (`x-signature`).
* **Receptor de Webhooks Blindado & Idempotente:**
  * Validação criptográfica da assinatura HMAC antes de qualquer processamento.
  * Tabela de controle de idempotência `webhook_events` (`UNIQUE (tenant_id, gateway, external_event_id)`).
  * Baixa automática imediata do título pendente no Contas a Receber e crédito no saldo bancário.

---

## 🛠️ Instalação e Configuração Local

### Pré-requisitos
* **PHP 8.4 ou superior** com extensões: `pdo_pgsql`, `pgsql`, `bcmath`, `openssl`, `mbstring`, `redis`, `curl`.
* **Composer 2.x**
* **Node.js 20+** e **npm**
* **PostgreSQL 16+** com suporte a RLS habilitado.
* **Redis 7+**

### Passo 1: Clonar o Repositório e Instalar Dependências
```bash
git clone https://github.com/icarocarvalho15/sgi-v2.git
cd sgi-v2

# Dependências PHP
composer install

# Dependências JavaScript / Frontend
npm install
```

### Passo 2: Configurar o Ambiente (.env)
Copie o arquivo de exemplo e configure suas credenciais de banco e serviços:
```bash
copy .env.example .env
php artisan key:generate
```

Certifique-se de configurar a conexão com o PostgreSQL e Redis:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgi_v2
DB_USERNAME=postgres
DB_PASSWORD=postgres

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

### Passo 3: Executar Migrações e População de Dados (Seeder Completo)
Execute as migrações com criação das tabelas, ativação das políticas RLS e povoamento da massa encadeada de teste:
```bash
php artisan migrate:fresh --seed
```

> **O que o Seeder cria automaticamente?**
> * 3 Planos SaaS + Super Administrador do Landlord (`admin@sgi.com.br` / `password`).
> * Tenant **Metalúrgica Alfa Ltda** (`alfa`): Usuário administrador (`alfa.admin@metalurgicaalfa.com.br` / `password`), clientes B2B, fornecedores (Gerdau), funil de vendas, insumos de aço/inox/parafusos com compras lançadas no Kardex, centros de trabalho, BOM do redutor RV-50, orçamento técnico aprovado, pedido de venda faturado com NF-e autorizada, OP apontada em fábrica e contas financeiras.
> * Tenant **Indústria Beta S/A** (`beta`): Baseline isolada para validação estrita anti-vazamento de dados.

### Passo 4: Executar os Servidores de Desenvolvimento
Para inicializar os daemons de desenvolvimento (Laravel, Vite, Worker de Filas e serviços):
```bash
# Terminal 1 - Backend Laravel
php artisan serve

# Terminal 2 - Frontend Vite
npm run dev

# Terminal 3 - Queue Worker (Redis)
php artisan queue:work redis
```

> **Dica Windows:** O repositório disponibiliza os scripts utilitários `start-dev.ps1` e `stop-dev.ps1` na raiz para inicialização e desligamento automatizado dos daemons locais.

---

## 🧪 Testes Automatizados & Diagnóstico

O SGI v2.0 conta com uma robusta suíte de testes de integração e fumaça construída sobre o PHPUnit, com cobertura completa dos 6 módulos e validação rigorosa de segurança multi-tenant.

### Executar a Suíte Completa
```bash
php artisan test
```

### Principais Suítes de Teste
| Suíte de Testes | Arquivo | Escopo & Validações |
| :--- | :--- | :--- |
| **Diagnóstico de Ciclo Completo** | [`FullSystemDiagnosticTest.php`](tests/Feature/FullSystemDiagnosticTest.php) | Executa o fluxo encadeado de ponta a ponta: Oportunidade ➔ Orçamento ➔ Conversão ➔ OP com explosão de BOM e perda técnica ➔ Kardex ➔ Contas a Receber ➔ Webhook com Idempotência ➔ NF-e com 44 dígitos; valida vazamento zero de dados entre Alfa e Beta em todas as tabelas. |
| **Integração Fiscal & Gateways** | [`FiscalIntegrationTest.php`](tests/Feature/FiscalIntegrationTest.php) | Cofre de senhas A1, criptografia de chaves de gateway, geração de QR Code Pix, cálculo de DV de NF-e, jobs de fila assíncronos e idempotência de webhooks. |
| **Fluxo Financeiro** | [`FinanceWorkflowTest.php`](tests/Feature/FinanceWorkflowTest.php) | Desdobramento de parcelas com centavos, baixas parciais/totais, estorno, relatórios de DRE e Fluxo de Caixa. |
| **CRM & Vendas Industriais** | [`CrmSalesWorkflowTest.php`](tests/Feature/CrmSalesWorkflowTest.php) | Validação de transição de status no Kanban, precificação com markup e conversão 1-clique para produção. |

---

## 🛡️ Padrões de Código e Qualidade

O projeto adota padrões rigorosos de engenharia e formatação automática:

```bash
# Executa a verificação e padronização com Laravel Pint
vendor/bin/pint --format agent
```

* **PSR-12 & Pint:** Padronização estrita do estilo de código PHP.
* **Strict Types:** `declare(strict_types=1);` em todos os arquivos PHP do sistema.
* **Controle de Transações:** Operações de múltiplos passos encapsuladas em transações atômicas com locks pessimistas (`lockForUpdate`) e locks distribuídos no Redis.

---

## 📄 Licença

Este software é um projeto proprietário de alto valor tecnológico desenvolvido sob as diretrizes de engenharia do ecossistema **SGI v2.0**. Todos os direitos reservados.