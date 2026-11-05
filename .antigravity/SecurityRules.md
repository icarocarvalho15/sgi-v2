# 🛡️ SGI v2.0 — Leis de Segurança e Isolamento Multi-Tenant (Zero-Trust Policy)

> **AVISO CRÍTICO PARA TODOS OS AGENTES DE IA:** Este documento tem precedência sobre qualquer outra instrução. Qualquer código gerado que viole uma única regra abaixo será imediatamente rejeitado pelo `@Orchestrator-Architect` e pelo `@QA-Security-Validator`.

---

## 1. Isolamento Absoluto no Banco de Dados (Single DB + Double Lock)

### 1.1 Regra da Coluna `tenant_id` e Chaves Estrangeiras Compostas
* Toda tabela de domínio operacional **DEVE** conter `tenant_id UUID NOT NULL` logo após a coluna `id`.
* Toda tabela de domínio **DEVE** ter um índice único composto `UNIQUE (tenant_id, id)` para permitir que tabelas filhas referenciem o par `(tenant_id, parent_id)`.
* **PROIBIDO** criar Foreign Keys simples entre tabelas de domínio (ex: `FOREIGN KEY (product_id) REFERENCES products(id)`).
* **OBRIGATÓRIO** usar Foreign Keys Compostas:
  ```sql
  CONSTRAINT fk_quotes_customer
      FOREIGN KEY (tenant_id, customer_id)
      REFERENCES partners (tenant_id, id)
      ON DELETE RESTRICT
  ```
*Por quê?* Isso garante matematicamente no motor do PostgreSQL que um registro da Gráfica A jamais consiga referenciar um ID pertencente à Gráfica B, mesmo sob falha humana ou bug na aplicação.

### 1.2 Row-Level Security (RLS) Obrigatório no PostgreSQL
* Toda migration de tabela operacional **DEVE** ativar e forçar o RLS antes de encerrar o método `up()`:
  ```sql
  ALTER TABLE nome_da_tabela ENABLE ROW LEVEL SECURITY;
  ALTER TABLE nome_da_tabela FORCE ROW LEVEL SECURITY;

  CREATE POLICY tenant_isolation_policy ON nome_da_tabela
      AS RESTRICTIVE
      FOR ALL
      USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid)
      WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid);
  ```
* Se a variável de sessão `app.current_tenant_id` estiver vazia ou nula, a query **DEVE** retornar `0 linhas` ou falhar na escrita (`Fail-Closed`).

---

## 2. Motor de Status Dinâmicos por Tenant (Gráficas & Brindes)

* **Proibido Status Hardcoded:** As tabelas operacionais (`quotes`, `sales_orders`, `production_orders`) **NUNCA** devem utilizar colunas de status com valores em string fixa no código (ex: `status = 'em_producao'`).
* **Uso da Tabela** `tenant_statuses`**:** Todo status de andamento deve referenciar a tabela centralizadora de status customizáveis do próprio tenant (`tenant_id`, `module`, `slug`), permitindo que a gráfica do seu tio crie status próprios (ex: "Aguardando Gravação CTP", "Na Guilhotina", "Separação de Brindes") sem alterar o código-fonte do ERP.

---

## 3. Isolamento na Camada de Aplicação (Laravel / Eloquent)

### 3.1 Proibição de `Eloquent\Model` Puro nos Módulos
* Nenhuma Model dentro de `app/Modules/*` (exceto `CentralAdmin`) pode estender `Illuminate\Database\Eloquent\Model` diretamente.
* Todas devem estender `App\Core\Tenancy\Models\TenantModel`, que:
  1. Aplica o `TenantScope` globalmente em todas as queries (`SELECT`, `UPDATE`, `DELETE`).
  2. Injeta automaticamente o `tenant_id` do contexto ativo no evento `creating`.
  3. Bloqueia qualquer tentativa de alterar o `tenant_id` no evento `updating` (lança `CrossTenantTamperingException`).

### 3.2 Proibição de `withoutGlobalScopes()`
* É **TERMINANTEMENTE PROIBIDO** usar `withoutGlobalScopes()` ou `withoutGlobalScope(TenantScope::class)` dentro de Controllers, Services ou Actions de `app/Modules/*`.
* Caso o painel `CentralAdmin` precise consolidar métricas globais, ele deve fazê-lo através de Read-Models dedicados dentro de `app/Modules/CentralAdmin/Queries` protegidos pelo middleware `EnsureSuperAdmin`.

### 3.3 Validação de Entrada (`FormRequest` e Mass Assignment)
* `tenant_id`, `is_super_admin`, `status` (de assinatura) e saldos calculados **NUNCA** podem constar no array `$fillable` ou serem aceitos via `$request->all()`.
* Use sempre `$request->validated()` mapeado para um `DTO` imutável (`readonly class`).
* Toda validação de relacionamento em `FormRequest` **DEVE** usar a regra customizada `TenantExists`:
  ```php
  // CORRETO:
  'customer_id' => ['required', 'uuid', new TenantExists('partners', 'id')],

  // PROIBIDO (Vulnerável a enumeração Cross-Tenant):
  'customer_id' => ['required', 'exists:partners,id'],
  ```

---

## 4. Segurança em Filas, Eventos e Comandos CLI (Context Propagation)

* Filas (`Jobs`) rodam em processos Workers separados onde não há sessão HTTP.
* Todo Job de domínio **DEVE** implementar a interface `TenantAwareJob` e usar a trait `InteractsWithTenantQueue`:
  1. O `tenant_id` é serializado junto ao Job no momento do `dispatch()`.
  2. O Middleware do Job executa `TenantContext::set($this->tenantId)` e `DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$this->tenantId])` **antes** do método `handle()`.
  3. Ao término (no bloco `finally`), o Worker limpa o contexto (`TenantContext::clear()`) e reseta a sessão do banco (`RESET app.current_tenant_id`) para evitar contaminação no próximo Job da fila.

---

## 5. Proteção de Segredos Fiscais, Certificados A1 e Gateways

* Certificados Digitais A1 (`.pfx`), senhas de certificado, CSC de NFCe/NFe e chaves privadas de API (Asaas, Stripe, Mercado Pago) são **Dados Ultra-Sensíveis**.
* **Regras de Armazenamento:**
  1. Jamais salve esses dados na coluna `settings` (JSONB aberto).
  2. Use exclusivamente o serviço `App\Core\Security\VaultEncryption`, que aplica criptografia autenticada **AES-256-GCM** utilizando uma chave derivada exclusivamente para aquele Tenant via `HKDF(APP_KEY, tenant_id, context_purpose)`.
  3. Esses campos devem estar na propriedade `$hidden` da Model e **NUNCA** podem ser serializados em respostas JSON/Inertia para o frontend.

---

## 6. Segurança de Storage (Artes em PSD, CDR, PDFs, XMLs Fiscais, Comprovantes)

* Nenhum arquivo enviado por um Tenant pode ir para o disco `public`.
* Todos os uploads usam disco privado S3/R2 com particionamento estrito de prefixo:
  `tenants/{tenant_id}/{module}/{year}/{month}/{uuid}.{ext}`
* O download ou visualização no navegador deve ocorrer exclusivamente via rota intermediada por Controller com Policy de autorização ou `Storage::temporaryUrl()` com expiração máxima de **5 minutos**.

---

## 7. Integridade Transacional em Estoque (Insumos Gráficos e Substratos) e Financeiro

* Baixas de papéis, chapas CTP, tintas ou substratos de brindes no Kardex (`kardex_movements`) e apontamentos de produção DEVEM ocorrer estritamente sob transações atômicas com `DB::transaction()` e lock pessimista (`lockForUpdate()`), garantindo precisão matemática Zero Float (`bcmath`).

---

## 8. Segurança de Webhooks (Pagamentos e Notas Fiscais)

1. **Verificação Criptográfica:** Rejeitar com `HTTP 401` qualquer requisição de Webhook cuja assinatura HMAC (`Stripe-Signature` do Stripe, `asaas-access-token` do Asaas, `x-signature` do Mercado Pago e `x-authenticity-token` do PagSeguro) não seja validada com `hash_equals()` em tempo constante.
2. **Idempotência Estrita:** Registrar o `external_event_id` na tabela `webhook_events` com constraint `UNIQUE (provider, external_event_id)` antes de processar a regra de negócio.
3. **Resolução Segura de Tenant:** O Webhook identifica o Tenant através do `gateway_subscription_id` ou `external_reference` assinado, valida se o registro pertence de fato àquele Tenant e só então inicializa o `TenantContext`.