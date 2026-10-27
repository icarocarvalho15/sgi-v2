# 🚀 SGI v2.0 (Sistema de Gestão Integrada) — Master Plan & Architecture Blueprint

> **Documento Vivo de Orquestração:** Este arquivo é a Fonte Única de Verdade (SSOT) do projeto. O agente `@Orchestrator-Architect` deve ler e atualizar o status deste documento a cada entrega de tarefa.

---

## 1. Visão do Produto & Contexto de Negócio
O **SGI v2.0** é um ERP/CRM SaaS Multi-Tenant projetado especificamente para **indústrias e manufatura sob encomenda (Make-to-Order / Engineer-to-Order)**.
O sistema possui dois ambientes estritamente separados na mesma base de código:
1. **Core Admin (Landlord):** Painel central onde a operadora do SaaS gerencia empresas clientes (`Tenants`), planos de assinatura, limites de uso, faturamento do SaaS, monitoramento de filas e suporte via Impersonation auditado.
2. **Tenant App ( Ambiente Operacional):** O ERP/CRM utilizado diariamente pelas indústrias (no computador do escritório ou via PWA Mobile/Tablet no chão de fábrica).

---

## 2. Stack Tecnológica Obrigatória
* **Backend:** PHP 8.4 + Laravel 11/12 (Monólito Modular com DDD Pragmático)
* **Frontend Bridge:** Inertia.js v2 + Wayfinder/Ziggy (Tipagem estrita de rotas)
* **Frontend UI:** React 19 + TypeScript + Tailwind CSS v4 + shadcn/ui + TanStack Table + dnd-kit
* **Mobile / Chão de Fábrica:** PWA (Progressive Web App) responsivo com layouts dedicados de toque rápido (`ShopFloorMobileLayout`)
* **Banco de Dados:** PostgreSQL 16+ com **Row-Level Security (RLS)** nativo e obrigatório em todas as tabelas de domínio
* **Cache, Locks & Filas:** Redis 7+ (Laravel Horizon para Jobs Tenant-Aware e `Cache::lock` para concorrência de Kardex)
* **Segurança & ACL:** Spatie Laravel Permission (com flag `teams = true` mapeada para `tenant_id`) + Spatie Activitylog
* **Testes Automatizados:** PestPHP v3 (Backend/Isolamento Cross-Tenant) + Vitest/Playwright (Frontend)

---

## 3. Convenções Inegociáveis de Engenharia
1. **Chaves Primárias:** Todas as tabelas usam `UUID` (preferencialmente UUIDv7 ordenado temporalmente via `HasUuids` do Laravel).
2. **Precisão Numérica:**
   * Valores Monetários (Preços, Contas, Saldos Financeiros): `NUMERIC(15, 2)` ou `NUMERIC(15, 4)` para custos unitários de insumos.
   * Quantidades de Engenharia/Estoque (Kardex, Ficha Técnica): `NUMERIC(15, 4)` para suportar frações industriais (kg, metros, litros, mililitros). **PROIBIDO usar `FLOAT` ou `DOUBLE`.**
3. **Imutabilidade (Append-Only Ledgers):**
   * O saldo de estoque (`inventory_balances`) **nunca** é editado diretamente por um CRUD. Ele só pode ser alterado como consequência de uma inserção na tabela `kardex_movements` dentro de uma transação com `lockForUpdate()`.
   * Movimentações financeiras liquidadas não sofrem `DELETE` físico; exigem lançamento de estorno.
4. **Isolamento Multi-Tenant Duplo:**
   * Nível Aplicação: Toda Model de domínio estende `App\Core\Tenancy\Models\TenantModel`.
   * Nível Banco de Dados: Toda migration de domínio executa a macro de ativação do PostgreSQL RLS (`enableTenantRls('nome_da_tabela')`).

---

## 4. Roadmap de Execução & Fases (Status Tracker)

### FASE 0: Fundação Multi-Tenant & Segurança Base (`[x] Concluída`)
- [x] **0.1** Configuração de diretórios `app/Core` e `app/Modules`.
- [x] **0.2** Criação do `TenantContext`, `TenantScope`, Trait `BelongsToTenant` e `TenantModel`.
- [x] **0.3** Helper de Migration PostgreSQL RLS (`Blueprint::enableTenantRls()`).
- [x] **0.4** Middleware `SetTenantContext` (injeta `app.current_tenant_id` na sessão do Postgres e contexto do Laravel).
- [x] **0.5** Cofre Criptográfico `VaultEncryption` (AES-256-GCM + HKDF por `tenant_id`).
- [x] **0.6** Suíte base de testes de intrusão `tests/Security/CrossTenantIsolationTest.php`.

### FASE 1: Core Admin (Landlord), Planos & Identidade ACL (`[x] Concluída`)
- [x] **1.1** Migrations centrais: `plans`, `tenants`, `subscriptions`, `users`, `tenant_user`.
- [x] **1.2** Configuração do Spatie Permission com `teams_foreign_key = 'tenant_id'`.
- [x] **1.3** Autenticação global com seletor de Tenant e bloqueio de Tenants inadimplentes/suspensos.
- [x] **1.4** Painel `CentralAdmin`: CRUD de Planos, Provisionamento de Tenants (Onboarding) e Impersonation com Audit Log obrigatório.
- [x] **1.5** Painel de Configurações do Tenant: Gestão de Usuários da Empresa, Papéis (Admin, Engenheiro, Vendedor, Operador de Fábrica, Financeiro).

### FASE 2: Suprimentos, Produtos & Kardex Imutável (`[x] Concluída`)
- [x] **2.1** Cadastro de Unidades de Medida, Categorias, Depósitos/Almoxarifados (`warehouses`).
- [x] **2.2** Cadastro de Itens/Produtos (`items` com flags: `is_raw_material`, `is_manufactured`, `is_sellable`, `is_service`).
- [x] **2.3** Motor de Kardex (`kardex_movements` e `inventory_balances`) com Lock Atômico Redis/Postgres (`KardexLedgerService`).
- [x] **2.4** Telas de Consulta de Saldo, Extrato de Kardex auditável e Inventário/Ajuste com justificativa obrigatória.

### FASE 3: Engenharia & Produção (Chão de Fábrica) (`[x] Concluída`)
- [x] **3.1** Ficha Técnica / Bill of Materials (`boms` e `bom_items`) com versionamento, cálculo de custo estimado (`rollup`) e prevenção de loop circular.
- [x] **3.2** Roteiros Produtivos e Centros de Trabalho (`work_centers`, `routing_steps`).
- [x] **3.3** Ordens de Produção (`production_orders`, `production_order_materials`, `production_order_operations`).
- [x] **3.4** Integração Produção <-> Estoque: Reserva automática de insumos, baixa de matéria-prima e entrada de produto acabado no Kardex.
- [x] **3.5** Interface Mobile/Tablet PWA (`ShopFloorMobileLayout`): Kanban de Chão de Fábrica com Play/Pause/Apontamento de perda e leitura de código de barras/QR Code.

### FASE 4: CRM, Orçamentos Sob Encomenda & Vendas (`[x] Concluída`)
- [x] **4.1** Cadastro de Clientes/Fornecedores (`partners`) com validação de CNPJ/CPF e endereços múltiplos.
- [x] **4.2** Funil de Vendas e Oportunidades em Kanban (`crm_pipelines`, `crm_deals`) com drag-and-drop (`dnd-kit`).
- [x] **4.3** Orçamentos Técnicos (`quotes`, `quote_items`) integrados à Engenharia para simulação de margem, markup e prazo de entrega.
- [x] **4.4** Conversão 1-Clique: Aprovação de Orçamento -> Gera Pedido de Venda -> Dispara Ordem de Produção + Previsão Financeira.

### FASE 5: Módulo Financeiro (`[x] Concluída`)
- [x] **5.1** Contas Bancárias/Caixas Internos (`financial_accounts`), Plano de Contas (`chart_of_accounts`) e Centros de Custo (`cost_centers`).
- [x] **5.2** Contas a Pagar e Contas a Receber (`financial_entries`) com suporte a parcelamento, juros, multa, descontos e baixas parciais (`financial_payments`).
- [x] **5.3** Dashboard de Fluxo de Caixa (Realizado vs Projetado) e DRE Gerencial por Regime de Competência e Caixa.

### FASE 6: Integrações Fiscais (NFe/NFSe) & Gateways de Pagamento (`[x] Concluída`)
- [x] **6.1** Cofre de Certificado Digital A1 (`.pfx`) e parâmetros tributários por Tenant (NCM, CFOP, CSOSN/CST, ISS).
- [x] **6.2** Adapter Pattern de Gateways de Pagamento (`AsaasAdapter`, `StripeAdapter`, `MercadoPagoAdapter`) para emissão de Pix Dinâmico, Boletos com QR Code e Link de Cartão.
- [x] **6.3** Receptor de Webhooks blindado (Validação HMAC + Tabela `webhook_events` para idempotência + Baixa automática no Contas a Receber).
- [x] **6.4** Adapter de Emissão Fiscal Assíncrona (`NFe` / `NFSe`) via Filas (`TenantAwareJob`) com armazenamento seguro de XML/DANFE em Storage privado.

---

## 5. Protocolo de Passagem de Bastão (Handoff Protocol)
Nenhum agente deve trabalhar fora de ordem. Para cada funcionalidade solicitada pelo usuário:
1. `@Orchestrator-Architect` define o contrato técnico e lista de arquivos.
2. `@Core-SecOps-Agent` cria as migrations com `tenant_id` + FKs compostas + Políticas RLS.
3. `@Backend-Domain-Agent` implementa Models (`TenantModel`), Services, Actions, DTOs e Controllers.
4. `@Integrations-Fiscal-Agent` (se aplicável) constrói os Adapters externos, Jobs e Webhooks.
5. `@Frontend-UX-Agent` constrói as páginas React/Inertia (Desktop e/ou Mobile PWA).
6. `@QA-Security-Validator` executa os testes funcionais e de isolamento Cross-Tenant.