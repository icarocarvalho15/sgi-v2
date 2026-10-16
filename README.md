# SGI v2.0 (Sistema de Gestão Integrada)

O **SGI v2.0** é um ERP e CRM de alta performance desenvolvido sob medida para indústrias e manufatura sob encomenda. Construído como um SaaS Multi-Tenant com isolamento rigoroso de dados, o sistema atende desde o escritório (gestão administrativa e financeira densa) até o chão de fábrica (através de PWA responsivo para dispositivos móveis).

---

## 🚀 Stack Tecnológica

* **Backend:** PHP 8.4 + Laravel 11/12
* **Bridge Full-Stack:** Inertia.js v2 + TypeScript
* **Frontend:** React 19 + Tailwind CSS v4 + shadcn/ui
* **Banco de Dados & Segurança:** PostgreSQL 16+ (com Row-Level Security - RLS)
* **Filas & Cache:** Redis 7+ (Horizon + Atomic Locks para Kardex)
* **Autenticação & ACL:** Laravel Sanctum + Spatie Permission (com suporte a *Teams/Tenants*)

---

## 🏛️ Arquitetura Multi-Tenant & Segurança

O SGI v2.0 adota uma estratégia de **Single Database com Isolamento Lógico Duplo**:
1. **Global Scopes do Eloquent:** Filtro automático por `tenant_id` em todas as queries operacionais.
2. **PostgreSQL Row-Level Security (RLS):** Blindagem nativa no motor do banco de dados ativada via sessão (`app.current_tenant_id`), impedindo vazamento de dados mesmo em falhas de código na camada de aplicação.
3. **Criptografia de Credenciais (Vault):** Proteção de certificados digitais A1 e chaves de gateways de pagamento utilizando criptografia AES-256-GCM com HKDF e AAD vinculados exclusivamente ao UUID do tenant.

---

## 🛠️ Módulos Core do Sistema

* **Core / Admin (Landlord):** Gestão global de empresas (Tenants), planos de assinatura, limites de recursos, feature flags e auditoria de *Impersonation*.
* **CRM & Vendas:** Cadastro unificado de clientes, propostas comerciais e pipeline em formato Kanban.
* **Engenharia & Produção:** Ficha Técnica (BOM - Composição de Produtos e Insumos) e Ordens de Produção para o chão de fábrica.
* **Suprimentos:** Controle de estoque e Kardex imutável baseado em transações atômicas.
* **Financeiro:** Contas a Pagar, Contas a Receber, centros de custo e fluxo de caixa.
* **Integrações:** Emissão de Notas Fiscais (NFe/NFSe) e Gateways de Pagamento (Asaas, Stripe, Mercado Pago) com tratamento de idempotência via Webhooks.

---

## ⚙️ Instalação e Execução Local

1. **Clone o repositório:**
    ```bash
    git clone https://github.com/icarocarvalho15/sgi-v2.git
    cd sgi-v2
    ```

2. **Instale as dependências PHP e Node.js:**
    ```bash
    composer install
    npm install
    ```


3. **Configure o ambiente:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
*(Configure suas credenciais do PostgreSQL e Redis no arquivo `.env`)*

4. **Execute as migrações e o seeder de demonstração:**
    ```bash
    php artisan migrate:fresh --seed
    ```

5. **Inicie os servidores de desenvolvimento:**
    ```bash
    # Terminal 1: Servidor Laravel
    php artisan serve

    # Terminal 2: Compilação de Assets Vite/React
    npm run dev
    ```
---

## 🧪 Testes Automatizados

Para rodar a suíte de testes de segurança e isolamento Multi-Tenant:

    ```bash
    php artisan test
    ```
---

## 📄 Licença

Este projeto é proprietário e desenvolvido para uso industrial restrito.

Desenvolvido através de uma orquestração Multi-Agente (Agentic Workflow).

SGI v2.0 - Alpha &copy; DravDev - 2026.