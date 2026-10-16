# 🏃 Sprint Atual: FASE 2 — Suprimentos, Produtos & Kardex Imutável

> **Status:** Concluído com Sucesso ✅  
> **Orquestrador:** `@Orchestrator-Architect`  
> **Agentes Envolvidos:** `@Core-SecOps-Agent`, `@Backend-Domain-Agent`, `@Frontend-UX-Agent`, `@QA-Security-Validator`  
> **Objetivo:** Implementar o catálogo de produtos/itens industriais, depósitos/almoxarifados, unidades de medida e o motor de Kardex atômico e imutável (`NUMERIC(15,4)`, Custo Médio Ponderado CMP, trava por `Cache::lock` e `lockForUpdate`), telas React/Inertia de saldos e extrato com filtros, modal de ajuste com justificativa obrigatória e isolamento cross-tenant comprovado por testes.

---

## 📐 ERD e Modelo Relacional da Fase 2 (Double Lock: Tenant ID + RLS)

### 1. `units_of_measure`
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `name` (string)
- `code` (string, ex: UN, KG, M, L, M2, CX)
- `symbol` (string)
- `type` (string: unit, weight, volume, length, area)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, code)`, `FK (tenant_id) -> tenants(id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('units_of_measure')`

### 2. `item_categories`
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `name` (string)
- `code` (string, nullable)
- `parent_id` (UUID, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, name)`, `FK (tenant_id) -> tenants(id)`, `FK (tenant_id, parent_id) -> item_categories(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('item_categories')`

### 3. `warehouses`
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `code` (string, ex: ALM-01, EXP-01, PROD-01)
- `name` (string)
- `description` (text, nullable)
- `is_active` (boolean, default true)
- `is_default` (boolean, default false)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, code)`, `FK (tenant_id) -> tenants(id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('warehouses')`

### 4. `items`
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `sku` (string)
- `name` (string)
- `description` (text, nullable)
- `unit_of_measure_id` (UUID NOT NULL)
- `category_id` (UUID, nullable)
- `default_warehouse_id` (UUID, nullable)
- `is_raw_material` (boolean, default false)
- `is_manufactured` (boolean, default false)
- `is_sellable` (boolean, default true)
- `is_service` (boolean, default false)
- `allow_negative_stock` (boolean, default false)
- `min_stock` (`NUMERIC(15, 4)`, default 0)
- `max_stock` (`NUMERIC(15, 4)`, nullable)
- `average_cost` (`NUMERIC(15, 4)`, default 0)
- `last_cost` (`NUMERIC(15, 4)`, default 0)
- `sale_price` (`NUMERIC(15, 2)`, default 0)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, sku)`, `FK (tenant_id) -> tenants(id)`
- **Composite FKs:**
  - `(tenant_id, unit_of_measure_id) -> units_of_measure(tenant_id, id)`
  - `(tenant_id, category_id) -> item_categories(tenant_id, id)`
  - `(tenant_id, default_warehouse_id) -> warehouses(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('items')`

### 5. `inventory_balances` (Snapshot Derivado sob Lock)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `warehouse_id` (UUID NOT NULL)
- `item_id` (UUID NOT NULL)
- `quantity_on_hand` (`NUMERIC(15, 4)`, default 0)
- `quantity_reserved` (`NUMERIC(15, 4)`, default 0)
- `average_cost` (`NUMERIC(15, 4)`, default 0)
- `last_movement_at` (timestamp, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, warehouse_id, item_id)`
- **Composite FKs:**
  - `(tenant_id, warehouse_id) -> warehouses(tenant_id, id)`
  - `(tenant_id, item_id) -> items(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('inventory_balances')`

### 6. `kardex_movements` (Razão Imutável Append-Only)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `movement_number` (string)
- `warehouse_id` (UUID NOT NULL)
- `item_id` (UUID NOT NULL)
- `movement_type` (string: IN_PURCHASE, IN_PRODUCTION, IN_ADJUSTMENT, IN_TRANSFER, OUT_PRODUCTION, OUT_SALE, OUT_ADJUSTMENT, OUT_TRANSFER, OUT_SCRAP)
- `quantity` (`NUMERIC(15, 4)`, > 0)
- `unit_cost` (`NUMERIC(15, 4)`)
- `total_cost` (`NUMERIC(15, 4)`)
- `previous_balance` (`NUMERIC(15, 4)`)
- `resulting_balance` (`NUMERIC(15, 4)`)
- `previous_average_cost` (`NUMERIC(15, 4)`)
- `resulting_average_cost` (`NUMERIC(15, 4)`)
- `reference_type` (string, nullable)
- `reference_id` (UUID, nullable)
- `document_number` (string, nullable)
- `justification` (text, nullable)
- `user_id` (UUID, nullable)
- `created_at` (timestamp NOT NULL)
- **Constraints:** `UNIQUE (tenant_id, id)`
- **Composite FKs:**
  - `(tenant_id, warehouse_id) -> warehouses(tenant_id, id)`
  - `(tenant_id, item_id) -> items(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('kardex_movements')`

---

## 📑 Contratos Técnicos de Backend & Frontend

### DTOs & Services
- `StockMovementDTO`: Objeto imutável contendo `warehouseId`, `itemId`, `movementType`, `quantity`, `unitCost`, `referenceType`, `referenceId`, `documentNumber`, `justification`, `userId`.
- `KardexLedgerService`:
  1. Adquire `Cache::lock("tenant:{$tenantId}:item_stock:{$itemId}:warehouse:{$warehouseId}", 10)->block(5)`.
  2. Executa `DB::transaction(...)`.
  3. Busca e aplica `lockForUpdate()` no `InventoryBalance`.
  4. Calcula Custo Médio Ponderado (CMP) estritamente em `bcmath` com precisão de 4 casas decimais.
  5. Valida regra de saldo negativo (`allow_negative_stock`).
  6. Insere linha imutável em `kardex_movements`.
  7. Atualiza snapshot derivado em `inventory_balances` e custos em `items`.

### Endpoints
- `GET /app/inventory/items` -> `ItemController@index`
- `POST /app/inventory/items` -> `ItemController@store`
- `PUT /app/inventory/items/{item}` -> `ItemController@update`
- `GET /app/inventory/warehouses` -> `WarehouseController@index`
- `POST /app/inventory/warehouses` -> `WarehouseController@store`
- `GET /app/inventory/balances` -> `InventoryBalanceController@index`
- `GET /app/inventory/kardex` -> `KardexMovementController@index`
- `POST /app/inventory/adjustments` -> `StockAdjustmentController@store`

---

## 🗓️ Checklist de Execução por Agente
- [x] `@Core-SecOps-Agent`: 6 Migrations com RLS e FKs compostas.
- [x] `@Backend-Domain-Agent`: Models `TenantModel`, DTOs, `KardexLedgerService`, FormRequests com `TenantExists` e Controllers.
- [x] `@Frontend-UX-Agent`: Páginas React/Inertia em `resources/js/Pages/Inventory/` (Itens, Saldos, Extrato Kardex, Almoxarifados, Ajuste de Estoque).
- [x] `@QA-Security-Validator`: Suíte `InventoryKardexTest.php` com isolamento cross-tenant e concorrência de estoque (100% dos testes passando).

