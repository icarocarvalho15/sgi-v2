# 🏃 Sprint Atual: FASE 6 — Integrações Fiscais (NFe/NFSe) & Gateways de Pagamento

> **Status:** Em Execução 🚀  
> **Orquestrador:** `@Orchestrator-Architect`  
> **Agentes Envolvidos:** `@Core-SecOps-Agent`, `@Integrations-Fiscal-Agent`, `@Backend-Domain-Agent`, `@Frontend-UX-Agent`, `@QA-Security-Validator`  
> **Objetivo:** Implementar o Cofre de Certificados Digitais A1 (`.pfx`) e Parâmetros Fiscais (`tenant_tax_settings`); Configuração e Adapter Pattern de Gateways de Pagamento (`payment_gateway_settings`, `AsaasAdapter`, `StripeAdapter`, `MercadoPagoAdapter`) para Pix Dinâmico, Boletos com QR Code e Cartão de Crédito; Receptor de Webhooks blindado com validação HMAC e tabela de idempotência (`webhook_events`) com baixa automática no Contas a Receber; Motor de Emissão Fiscal Assíncrona (`fiscal_invoices`, `fiscal_invoice_events`) com Jobs de Fila multi-tenant (`EmitFiscalInvoiceJob` via `TenantAwareJob`) e salvamento de XML e DANFE em Storage seguro; Interfaces Desktop ERP de Monitoramento Fiscal e Gestão de Gateways; Suíte de Testes com validação de idempotência, assinatura HMAC e isolamento multi-tenant.

---

## 📐 ERD e Modelo Relacional da Fase 6 (Double Lock: Tenant ID + RLS)

### 1. `tenant_tax_settings` (Parâmetros Fiscais & Certificado A1)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `certificate_path` (string, nullable) - Caminho no Storage privado para o `.pfx`
- `certificate_password` (text, nullable) - Criptografado com `VaultEncryption` (AES-256-GCM + HKDF)
- `certificate_expires_at` (timestamp, nullable)
- `environment` (string: HOMOLOGATION, PRODUCTION - default HOMOLOGATION)
- `tax_regime` (string: SIMPLES_NACIONAL, LUCRO_PRESUMIDO, LUCRO_REAL - default SIMPLES_NACIONAL)
- `state_registration` (string, max 30) - Inscrição Estadual
- `municipal_registration` (string, nullable, max 30)
- `nfe_series` (integer, default 1)
- `next_nfe_number` (integer, default 1)
- `default_cfop` (string, max 10, default '5101')
- `default_csosn` (string, nullable, max 10, default '102')
- `default_cst` (string, nullable, max 10)
- `default_iss_rate` (`NUMERIC(5, 2)`, default 0.00)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id)`, `FK (tenant_id) -> tenants(id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('tenant_tax_settings')`

### 2. `payment_gateway_settings` (Gateways de Cobrança do Tenant)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `gateway` (string: ASAAS, STRIPE, MERCADOPAGO)
- `api_key` (text NOT NULL) - Criptografado via `VaultEncryption`
- `webhook_secret` (text, nullable) - Criptografado via `VaultEncryption`
- `is_sandbox` (boolean, default true)
- `is_active` (boolean, default true)
- `is_default` (boolean, default false)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, gateway)`, `FK (tenant_id) -> tenants(id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('payment_gateway_settings')`

### 3. `webhook_events` (Idempotência e Auditoria de Webhooks)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `gateway` (string: ASAAS, STRIPE, MERCADOPAGO)
- `external_event_id` (string NOT NULL) - ID único enviado pelo gateway
- `event_type` (string NOT NULL, ex: PAYMENT_RECEIVED, payment_intent.succeeded)
- `financial_entry_id` (UUID, nullable)
- `payload` (jsonb NOT NULL)
- `status` (string: PROCESSED, FAILED, IGNORED)
- `error_message` (text, nullable)
- `processed_at` (timestamp, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, gateway, external_event_id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('webhook_events')`

### 4. `fiscal_invoices` (Notas Fiscais Eletrônicas NFe / NFSe)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `sales_order_id` (UUID, nullable)
- `partner_id` (UUID NOT NULL)
- `invoice_type` (string: NFE, NFSE - default NFE)
- `invoice_number` (integer NOT NULL)
- `series` (integer NOT NULL)
- `environment` (string: HOMOLOGATION, PRODUCTION)
- `access_key` (string, max 44, nullable, index) - Chave de 44 dígitos da NFe
- `protocol_number` (string, max 50, nullable)
- `status` (string: DRAFT, PROCESSING, AUTHORIZED, REJECTED, CANCELLED)
- `total_amount` (`NUMERIC(15, 2)`)
- `tax_amount` (`NUMERIC(15, 2)`, default 0.00)
- `xml_path` (string, nullable)
- `pdf_path` (string, nullable)
- `rejection_reason` (text, nullable)
- `issued_at` (timestamp, nullable)
- `authorized_at` (timestamp, nullable)
- `cancelled_at` (timestamp, nullable)
- `payload` (jsonb, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, invoice_type, series, invoice_number)`
- **Composite FKs:**
  - `(tenant_id, partner_id) -> partners(tenant_id, id)`
  - `(tenant_id, sales_order_id) -> sales_orders(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('fiscal_invoices')`

---

## 🏗️ Padrões de Arquitetura & Adapters

### 1. Payment Gateway Adapter Pattern
- `PaymentGatewayInterface`:
  - `createPixCharge(CreateChargeDTO $dto): ChargeResponseDTO`
  - `createBoletoCharge(CreateChargeDTO $dto): ChargeResponseDTO`
  - `createCreditCardCharge(CreateChargeDTO $dto): ChargeResponseDTO`
  - `verifyWebhookSignature(string $rawPayload, string $signatureHeader, string $secret): bool`
  - `parseWebhookPayload(array $payload): ParsedWebhookEventDTO`
- Implementações:
  - `AsaasAdapter`: Suporte a QR Code Pix, Boleto com código de barras, Webhook token header.
  - `StripeAdapter`: Suporte a PaymentIntent, Boleto, HMAC SHA-256 (`Stripe-Signature`).
  - `MercadoPagoAdapter`: Suporte a Pix Dinâmico, HMAC SHA-256 (`x-signature`).
- `PaymentGatewayManager`: Resolução dinâmica do gateway ativo do Tenant.

### 2. Receptor de Webhooks Blindado
- Endpoint público: `POST /api/webhooks/gateway/{gateway}` com roteamento por subdomínio ou tenant header / payload.
- Validação HMAC criptográfica obrigatória antes do processamento.
- Transação atômica com trava `webhook_events`:
  - Se `(tenant_id, gateway, external_event_id)` já existir: retorna 200 idempotente imediatamente sem reprocessar.
  - Se novo: processa o evento de confirmação de pagamento, localiza o título em `financial_entries` e aciona `SettleFinancialPaymentAction`.

### 3. Emissão Fiscal Assíncrona & SEFAZ Mock/Real
- `EmitFiscalInvoiceJob` implementando `TenantAwareJob` via `InteractsWithTenantQueue`.
- `FiscalEmitterService`:
  - Geração de Chave de Acesso de 44 dígitos (UF + AAMM + CNPJ + Modelo 55 + Série + Número + Tipo de Emissão + Código Numérico + DV ponderado Módulo 11).
  - Assinatura digital do XML da NFe via Certificado A1 do cofre `VaultEncryption`.
  - Transmissão assíncrona com fallback e protocolo de autorização SEFAZ (mock padrão para homologação conforme MOC 4.00).
  - Armazenamento de XML assinado e PDF DANFE em Storage seguro.

---

## 🗓️ Checklist de Execução por Agente
- [x] `@Core-SecOps-Agent`: 4 Migrations com RLS e FKs compostas por `tenant_id` (`tenant_tax_settings`, `payment_gateway_settings`, `webhook_events`, `fiscal_invoices`).
- [x] `@Backend-Domain-Agent` & `@Integrations-Fiscal-Agent`: Models `TenantModel`, Adapters (`AsaasAdapter`, `StripeAdapter`, `MercadoPagoAdapter`), Gateway Manager, `WebhookService`, `FiscalEmitterService`, `EmitFiscalInvoiceJob` (`TenantAwareJob`), FormRequests e Controllers.
- [x] `@Frontend-UX-Agent`: Páginas React/Inertia Desktop & Mobile (Configurações Fiscais & Certificado A1, Gestão de Gateways, Painel de Notas Fiscais NFe/NFSe com visualizador e download de XML/DANFE).
- [x] `@QA-Security-Validator`: Suíte `FiscalIntegrationTest.php` com testes de idempotência de webhook, validação HMAC, emissão assíncrona em fila e isolamento multi-tenant estrito (100% dos 46 testes da aplicação passando).
- [x] `@Backend-Domain-Agent` & `@QA-Security-Validator`: Seeder de Diagnóstico e Estresse de Sistema Completo (`FullSystemDemoSeeder.php`) e Teste de Fumaça Integrado de Ponta a Ponta (`FullSystemDiagnosticTest.php`) cobrindo todos os 6 módulos do SGI v2.0 com 48 testes e 298 asserções 100% aprovados.
