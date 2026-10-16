export interface User {
    id: string;
    name: string;
    email: string;
    is_super_admin: boolean;
}

export interface PlanFeatures {
    crm?: boolean;
    inventory?: boolean;
    engineering?: boolean;
    production?: boolean;
    finance?: boolean;
    nfe_enabled?: boolean;
    pwa_shopfloor?: boolean;
    custom_reports?: boolean;
    priority_queue?: boolean;
    [key: string]: boolean | undefined;
}

export interface Plan {
    id: string;
    name: string;
    slug: string;
    description?: string | null;
    price: number | string;
    billing_interval: 'monthly' | 'yearly' | 'quarterly';
    max_users: number;
    max_storage_mb: number;
    is_active: boolean;
    features?: PlanFeatures | null;
    active_subscribers_count?: number;
}

export interface Tenant {
    id: string;
    name: string;
    trade_name?: string | null;
    document_number?: string | null;
    subdomain: string;
    domain?: string | null;
    status: 'active' | 'trial' | 'suspended' | 'canceled';
    plan_id?: string | null;
    plan?: Plan | null;
    users_count?: number;
    created_at?: string;
}

export interface ImpersonationLog {
    id: string;
    impersonator?: { name: string; email: string };
    tenant?: { name: string; subdomain: string };
    reason: string;
    ip_address?: string | null;
    started_at: string;
    ended_at?: string | null;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = {
    auth: {
        user: User | null;
        tenant: {
            id: string;
            name: string;
            trade_name?: string | null;
            subdomain: string;
            plan?: {
                name: string;
                slug: string;
                features?: PlanFeatures;
            } | null;
        } | null;
        is_impersonating: boolean;
        impersonated_tenant_name?: string | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        info?: string | null;
    };
} & T;

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

export interface UnitOfMeasure {
    id: string;
    tenant_id: string;
    name: string;
    code: string;
    symbol: string;
    type: 'unit' | 'weight' | 'volume' | 'length' | 'area';
    is_active: boolean;
}

export interface ItemCategory {
    id: string;
    tenant_id: string;
    name: string;
    code?: string | null;
    parent_id?: string | null;
}

export interface Warehouse {
    id: string;
    tenant_id: string;
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
    is_default: boolean;
    balances_count?: number;
}

export interface Item {
    id: string;
    tenant_id: string;
    sku: string;
    name: string;
    description?: string | null;
    unit_of_measure_id: string;
    unit_of_measure?: UnitOfMeasure;
    category_id?: string | null;
    category?: ItemCategory | null;
    default_warehouse_id?: string | null;
    default_warehouse?: Warehouse | null;
    is_raw_material: boolean;
    is_manufactured: boolean;
    is_sellable: boolean;
    is_service: boolean;
    allow_negative_stock: boolean;
    min_stock: string;
    max_stock?: string | null;
    average_cost: string;
    last_cost: string;
    sale_price: string;
    is_active: boolean;
    balances?: InventoryBalance[];
    total_stock_on_hand?: string;
}

export interface InventoryBalance {
    id: string;
    tenant_id: string;
    warehouse_id: string;
    warehouse?: Warehouse;
    item_id: string;
    item?: Item;
    quantity_on_hand: string;
    quantity_reserved: string;
    average_cost: string;
    last_movement_at?: string | null;
}

export interface KardexMovement {
    id: string;
    tenant_id: string;
    movement_number: string;
    warehouse_id: string;
    warehouse?: Warehouse;
    item_id: string;
    item?: Item;
    movement_type: string;
    quantity: string;
    unit_cost: string;
    total_cost: string;
    previous_balance: string;
    resulting_balance: string;
    previous_average_cost: string;
    resulting_average_cost: string;
    reference_type?: string | null;
    reference_id?: string | null;
    document_number?: string | null;
    justification?: string | null;
    user_id?: string | null;
    user?: User | null;
    created_at: string;
}

