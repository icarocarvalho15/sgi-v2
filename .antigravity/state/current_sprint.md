# 🏃 Sprint Atual: FASE 3 — Engenharia & Produção (Chão de Fábrica)

> **Status:** Concluído com Sucesso ✅  
> **Orquestrador:** `@Orchestrator-Architect`  
> **Agentes Envolvidos:** `@Core-SecOps-Agent`, `@Backend-Domain-Agent`, `@Frontend-UX-Agent`, `@QA-Security-Validator`  
> **Objetivo:** Implementar Fichas Técnicas (BOMs) versionadas com rollup de custos e prevenção de ciclos, Centros de Trabalho e Roteiros Produtivos, Ordens de Produção (OPs) com reserva e baixa atômica de matérias-primas e entrada de produtos acabados no Kardex (`KardexLedgerService`), interface Desktop ERP e Mobile/Tablet PWA para Chão de Fábrica (`ShopFloorMobileLayout`) com Play/Pause e apontamento de produção/perda, e testes de segurança e isolamento Cross-Tenant.

---

## 📐 ERD e Modelo Relacional da Fase 3 (Double Lock: Tenant ID + RLS)

### 1. `work_centers` (Centros de Trabalho)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `code` (string, ex: CT-CORTE, CT-USIN, CT-PINT)
- `name` (string)
- `description` (text, nullable)
- `hourly_cost` (`NUMERIC(15, 4)`, default 0)
- `capacity_per_day_hours` (`NUMERIC(15, 2)`, default 8.00)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, code)`, `FK (tenant_id) -> tenants(id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('work_centers')`

### 2. `boms` (Fichas Técnicas / Bill of Materials)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `item_id` (UUID NOT NULL)
- `code` (string, ex: BOM-MESA-01)
- `version` (integer, default 1)
- `description` (text, nullable)
- `batch_size` (`NUMERIC(15, 4)`, default 1.0000)
- `total_estimated_cost` (`NUMERIC(15, 4)`, default 0)
- `is_active` (boolean, default true)
- `is_default` (boolean, default true)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, item_id, version)`, `FK (tenant_id) -> tenants(id)`
- **Composite FKs:** `(tenant_id, item_id) -> items(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('boms')`

### 3. `bom_items` (Insumos da Ficha Técnica)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `bom_id` (UUID NOT NULL)
- `item_id` (UUID NOT NULL)
- `unit_of_measure_id` (UUID NOT NULL)
- `quantity` (`NUMERIC(15, 4)`, NOT NULL)
- `scrap_rate_percentage` (`NUMERIC(5, 2)`, default 0.00)
- `unit_cost` (`NUMERIC(15, 4)`, default 0)
- `total_cost` (`NUMERIC(15, 4)`, default 0)
- `notes` (string, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`
- **Composite FKs:**
  - `(tenant_id, bom_id) -> boms(tenant_id, id) ON DELETE CASCADE`
  - `(tenant_id, item_id) -> items(tenant_id, id)`
  - `(tenant_id, unit_of_measure_id) -> units_of_measure(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('bom_items')`

### 4. `routing_steps` (Etapas do Roteiro Produtivo)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `bom_id` (UUID NOT NULL)
- `work_center_id` (UUID NOT NULL)
- `sequence` (integer, default 1)
- `name` (string)
- `description` (text, nullable)
- `setup_time_minutes` (`NUMERIC(10, 2)`, default 0)
- `run_time_minutes_per_unit` (`NUMERIC(10, 2)`, default 0)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, bom_id, sequence)`
- **Composite FKs:**
  - `(tenant_id, bom_id) -> boms(tenant_id, id) ON DELETE CASCADE`
  - `(tenant_id, work_center_id) -> work_centers(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('routing_steps')`

### 5. `production_orders` (Ordens de Produção)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `order_number` (string)
- `item_id` (UUID NOT NULL)
- `bom_id` (UUID NOT NULL)
- `warehouse_id` (UUID NOT NULL) - Depósito destino do produto acabado
- `materials_warehouse_id` (UUID NOT NULL) - Depósito de origem dos materiais
- `planned_quantity` (`NUMERIC(15, 4)`, NOT NULL)
- `produced_quantity` (`NUMERIC(15, 4)`, default 0)
- `scrapped_quantity` (`NUMERIC(15, 4)`, default 0)
- `status` (string: DRAFT, RELEASED, IN_PROGRESS, COMPLETED, CANCELLED)
- `priority` (string: LOW, MEDIUM, HIGH, URGENT)
- `start_date` (date, nullable)
- `due_date` (date, nullable)
- `actual_start_at` (timestamp, nullable)
- `actual_end_at` (timestamp, nullable)
- `notes` (text, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`, `UNIQUE (tenant_id, order_number)`
- **Composite FKs:**
  - `(tenant_id, item_id) -> items(tenant_id, id)`
  - `(tenant_id, bom_id) -> boms(tenant_id, id)`
  - `(tenant_id, warehouse_id) -> warehouses(tenant_id, id)`
  - `(tenant_id, materials_warehouse_id) -> warehouses(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('production_orders')`

### 6. `production_order_materials` (Empenhos de Insumos da OP)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `production_order_id` (UUID NOT NULL)
- `item_id` (UUID NOT NULL)
- `unit_of_measure_id` (UUID NOT NULL)
- `planned_quantity` (`NUMERIC(15, 4)`, NOT NULL)
- `consumed_quantity` (`NUMERIC(15, 4)`, default 0)
- `unit_cost` (`NUMERIC(15, 4)`, default 0)
- `total_cost` (`NUMERIC(15, 4)`, default 0)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`
- **Composite FKs:**
  - `(tenant_id, production_order_id) -> production_orders(tenant_id, id) ON DELETE CASCADE`
  - `(tenant_id, item_id) -> items(tenant_id, id)`
  - `(tenant_id, unit_of_measure_id) -> units_of_measure(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('production_order_materials')`

### 7. `production_order_operations` (Operações e Apontamentos Chão de Fábrica)
- `id` (UUID PK)
- `tenant_id` (UUID NOT NULL, Index)
- `production_order_id` (UUID NOT NULL)
- `routing_step_id` (UUID, nullable)
- `work_center_id` (UUID NOT NULL)
- `sequence` (integer, default 1)
- `status` (string: PENDING, IN_PROGRESS, PAUSED, COMPLETED)
- `started_at` (timestamp, nullable)
- `paused_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `executed_time_seconds` (integer, default 0)
- `operator_user_id` (UUID, nullable)
- `notes` (text, nullable)
- `created_at`, `updated_at`
- **Constraints:** `UNIQUE (tenant_id, id)`
- **Composite FKs:**
  - `(tenant_id, production_order_id) -> production_orders(tenant_id, id) ON DELETE CASCADE`
  - `(tenant_id, work_center_id) -> work_centers(tenant_id, id)`
- **RLS:** `PostgresRlsHelper::enableTenantRls('production_order_operations')`

---

## 📑 Contratos Técnicos de Backend & Frontend

### DTOs & Services
- `BomRollupService`: Cálculo de custo estimado com precisão `bcmath` e detecção de dependência circular (`CircularBomDependencyException`).
- `ProductionOrderService`:
  - `release(ProductionOrder $order)`: Altera status para `RELEASED`, calcula empenho de materiais (`production_order_materials`) baseado na BOM multiplicada pela quantidade planejada e gera operações (`production_order_operations`) a partir de `routing_steps`.
  - `startOperation(ProductionOrderOperation $op, ?string $userId)`: Inicia timer e status `IN_PROGRESS`.
  - `pauseOperation(ProductionOrderOperation $op)`: Pausa timer e acumula tempo executado.
  - `completeOperation(ProductionOrderOperation $op)`: Conclui etapa.
  - `recordProduction(ProductionOrder $order, string $producedQty, string $scrappedQty, ?string $notes)`:
    - Baixa proporcional de matérias-primas no Kardex (`KardexMovementType::OutProduction`).
    - Registro de sucata/perda no Kardex (`KardexMovementType::OutScrap`) se houver.
    - Entrada de produto acabado no estoque (`KardexMovementType::InProduction`) com custo unitário real calculado.
    - Atualiza saldo e status da OP para `COMPLETED` se `produced_quantity + scrapped_quantity >= planned_quantity`.

### Endpoints (Rotas Operacionais em `/app/production`)
- `GET /app/production/work-centers` -> `WorkCenterController@index`
- `POST /app/production/work-centers` -> `WorkCenterController@store`
- `GET /app/production/boms` -> `BomController@index`
- `POST /app/production/boms` -> `BomController@store`
- `GET /app/production/boms/{bom}` -> `BomController@show`
- `POST /app/production/boms/{bom}/rollup` -> `BomController@calculateRollup`
- `GET /app/production/orders` -> `ProductionOrderController@index`
- `POST /app/production/orders` -> `ProductionOrderController@store`
- `GET /app/production/orders/{order}` -> `ProductionOrderController@show`
- `POST /app/production/orders/{order}/release` -> `ProductionOrderController@release`
- `POST /app/production/orders/{order}/record-production` -> `ProductionOrderController@recordProduction`
- `GET /app/production/shop-floor` -> `ShopFloorController@index` (PWA)
- `POST /app/production/operations/{operation}/start` -> `ShopFloorController@start`
- `POST /app/production/operations/{operation}/pause` -> `ShopFloorController@pause`
- `POST /app/production/operations/{operation}/complete` -> `ShopFloorController@complete`

---

## 🗓️ Checklist de Execução por Agente
- [x] `@Core-SecOps-Agent`: 7 Migrations com RLS e FKs compostas.
- [x] `@Backend-Domain-Agent`: Models `TenantModel`, DTOs, Enums, `BomRollupService`, `ProductionOrderService`, FormRequests com `TenantExists` e Controllers.
- [x] `@Frontend-UX-Agent`: Páginas React/Inertia Desktop (BOMs, Centros de Trabalho, Ordens de Produção) e Mobile PWA (`ShopFloorMobileLayout`).
- [x] `@QA-Security-Validator`: Suíte `ProductionEngineeringTest.php` com cálculo de rollup, prevenção de loop circular, integração Kardex e isolamento cross-tenant.
