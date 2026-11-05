# MasterPlan Oficial: SGI v2.0 — ERP & CRM para Gráficas e Brindes Personalizados

## 🎯 Visão Geral e Nicho de Mercado
O **SGI v2.0** é um SaaS Multi-Tenant projetado especificamente para o setor gráfico (impressão offset/digital, birôs de fotolito, gravação de chapas CTP) e indústrias de brindes personalizados. O sistema resolve as dores reais da operação do dia a dia, permitindo que cada empresa gerencie desde o orçamento técnico detalhado de produtos personalizados até o chão de fábrica, controle de insumos e fluxo financeiro, com total autonomia para customizar seus próprios status operacionais.

---

## 🏛️ Arquitetura e Segurança de Dados
- **Stack:** Laravel 11/12 (PHP 8.4) + Inertia.js v2 + React 19 (TypeScript) + Tailwind CSS v4 + PostgreSQL 16.
- **Multi-Tenancy de Camada Dupla:** 
  1. *Global Scopes* automáticos no Eloquent (`tenant_id`).
  2. *Row-Level Security (RLS)* nativo no PostgreSQL via sessão (`app.current_tenant_id`), garantindo blindagem estrita entre os dados das diferentes gráficas e clientes.
- **Precisão Numérica (*Zero Float*):** Cálculos de custos, gramatura, consumo de chapas, fotolitos e insumos estritamente baseados em `bcmath` e colunas decimais de alta precisão.

---

## 📋 Roadmap de Fases e Módulos do Sistema

### FASE 0: Fundação, Infraestrutura Multi-Tenant & Cofre Vault
- [x] Configuração do Singleton `TenantContext` e sincronização com PostgreSQL RLS.
- [x] Model base abstrato `TenantModel` com trait `BelongsToTenant` (Fail-Closed).
- [x] Cofre de credenciais sensíveis e certificados (`VaultEncryption` com AES-256-GCM).

### FASE 1: Core Admin (Landlord), Planos SaaS & Customização de Status por Tenant
- [x] Gestão global de Tenants (Gráficas e Brindes assinantes) e planos de assinatura.
- [x] **Engine de Status Dinâmicos (`tenant_statuses`):** Módulo onde cada Tenant pode criar, editar, colorir e reordenar seus próprios status de andamento para Orçamentos, Ordens de Produção e Kanban de Vendas.
- [x] Painel de Impersonation auditado para suporte técnico.

### FASE 2: CRM, Clientes, Fornecedores & Funil de Vendas
- [x] Cadastro unificado de Clientes e Fornecedores (`partners`) com múltiplos endereços e validação de CNPJ/CPF.
- [x] Funil de Oportunidades (Kanban com `dnd-kit`) integrado aos status customizáveis do tenant.

### FASE 3: Orçamentos Técnicos (Gráficas & Brindes)
- [x] Motor de Orçamento especializado para o nicho: cálculo por quantidade, formatos de papel/substrato, quantidade de cores (ex: 4x0, 4x4), chapas CTP, fotolitos, clichês e personalizações de brindes (silk, laser, sublimação).
- [x] Simulação de margem, markup, impostos e prazo de entrega.
- [x] Conversão em 1-Clique: Orçamento Aprovado -> Gera Pedido de Venda -> Dispara Ordem de Produção + Reserva de Insumos + Lançamento Financeiro.

### FASE 4: Engenharia & Chão de Fábrica (Ordens de Produção)
- [x] Ficha Técnica / Composição do Produto Gráfico/Brinde (Insumos, substratos, tintas, chapas, embalagens).
- [x] Ordens de Produção (OPs) com acompanhamento pelas etapas produtivas parametrizadas pelos status da gráfica.

### FASE 5: Suprimentos & Controle de Estoque (Kardex Imutável)
- [x] Cadastro de Insumos e Produtos (Papéis, Chapas CTP, Tintas, Brindes em estoque bruto).
- [x] Razão de Kardex (*Append-Only Ledger*) para entradas de compras e baixas automáticas por consumo nas OPs, calculando o Custo Médio Ponderado (CMP) sem risco de saldo negativo.

### FASE 6: Módulo Financeiro, Pagamentos & Relatórios
- [ ] Plano de Contas, Centros de Custo e Contas Bancárias/Caixas.
- [ ] Contas a Pagar e Contas a Receber com parcelamento, juros, multas, descontos e baixas parciais (`financial_payments`).
- [ ] Dashboard Financeiro (Fluxo de Caixa Realizado vs Projetado) e DRE Gerencial.
- [ ] Relatórios gerenciais de vendas, produtividade da gráfica e ranking de clientes.
- [ ] Implementação de Gateways de Pagamento como Stripe, Asaas, Mercado Pago, PagSeguro.