/**
 * Utilitário de Exportação de Dados para Planilhas (CSV compatível com Excel / Google Sheets).
 *
 * Configurado com:
 * - BOM UTF-8 (\uFEFF) para preservar acentos e caracteres em português (ç, ã, é, ó);
 * - Delimitador ponto e vírgula (;) padrão do Excel brasileiro;
 * - Escape automático de aspas duplas e quebras de linha.
 */

export function downloadCsv(
    filename: string,
    headers: string[],
    rows: (string | number | boolean | null | undefined)[][]
): void {
    const formatValue = (val: string | number | boolean | null | undefined): string => {
        if (val === null || val === undefined) return '';
        const str = String(val);
        // Se contiver ponto-e-vírgula, aspas ou quebra de linha, envolve em aspas e duplica aspas
        if (str.includes(';') || str.includes('"') || str.includes('\n') || str.includes('\r')) {
            return `"${str.replace(/"/g, '""')}"`;
        }
        return str;
    };

    const headerLine = headers.map(formatValue).join(';');
    const bodyLines = rows.map((row) => row.map(formatValue).join(';')).join('\r\n');
    const csvContent = `\uFEFF${headerLine}\r\n${bodyLines}`;

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename.endsWith('.csv') ? filename : `${filename}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// 1. Exportação de Títulos Financeiros (Contas a Pagar / Receber)
export function exportFinancialEntriesToCsv(entries: any[]): void {
    const headers = [
        'Número',
        'Tipo',
        'Descrição',
        'Cliente / Fornecedor',
        'Vencimento',
        'Data Pagamento',
        'Valor Original (R$)',
        'Desconto (R$)',
        'Juros/Multa (R$)',
        'Valor Líquido (R$)',
        'Valor Pago (R$)',
        'Saldo Devedor (R$)',
        'Status',
        'Parcela',
        'Conta Bancária'
    ];

    const rows = entries.map((e) => [
        e.entry_number || '',
        e.type === 'receivable' ? 'A Receber' : 'A Pagar',
        e.description || '',
        e.partner?.name || 'Não informado',
        e.due_date || '',
        e.payment_date || '',
        e.original_amount || '0.00',
        e.discount_amount || '0.00',
        e.interest_fine_amount || '0.00',
        e.net_amount || '0.00',
        e.paid_amount || '0.00',
        e.balance_due || '0.00',
        e.status === 'paid' ? 'Pago' : e.status === 'partial' ? 'Parcial' : e.status === 'cancelled' ? 'Cancelado' : 'Pendente',
        `${e.installment_number || 1}/${e.total_installments || 1}`,
        e.bankAccount?.name || 'Não vinculada'
    ]);

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`titulos_financeiros_${dateStr}.csv`, headers, rows);
}

// 2. Exportação de Fluxo de Caixa e DRE Gerencial
export function exportCashFlowAndDreToCsv(cashFlow: any, dre: any): void {
    const headers = ['Categoria / Indicador', 'Período Inicial', 'Período Final', 'Valor (R$)', 'Observações'];

    const rows = [
        ['FLUXO DE CAIXA: Entradas Realizadas', cashFlow?.period_start || '', cashFlow?.period_end || '', cashFlow?.realized_inflows || '0.00', 'Recebimentos confirmados'],
        ['FLUXO DE CAIXA: Saídas Realizadas', cashFlow?.period_start || '', cashFlow?.period_end || '', cashFlow?.realized_outflows || '0.00', 'Pagamentos efetuados'],
        ['FLUXO DE CAIXA: Saldo Líquido Realizado', cashFlow?.period_start || '', cashFlow?.period_end || '', cashFlow?.realized_net || '0.00', 'Resultado de caixa'],
        ['FLUXO DE CAIXA: Entradas Projetadas', cashFlow?.period_start || '', cashFlow?.period_end || '', cashFlow?.projected_inflows || '0.00', 'Títulos a receber previstos'],
        ['FLUXO DE CAIXA: Saídas Projetadas', cashFlow?.period_start || '', cashFlow?.period_end || '', cashFlow?.projected_outflows || '0.00', 'Títulos a pagar previstos'],
        ['FLUXO DE CAIXA: Saldo Projetado', cashFlow?.period_start || '', cashFlow?.period_end || '', cashFlow?.projected_net || '0.00', 'Posição financeira futura'],
        ['---', '---', '---', '---', '---'],
        ['DRE: Receita Bruta de Vendas', dre?.period_start || '', dre?.period_end || '', dre?.gross_revenue || '0.00', 'Faturamento por competência'],
        ['DRE: Custo das Mercadorias Vendidas (CMV)', dre?.period_start || '', dre?.period_end || '', dre?.cost_of_goods || '0.00', 'Substratos, chapas, tintas e insumos'],
        ['DRE: Lucro Bruto', dre?.period_start || '', dre?.period_end || '', dre?.gross_profit || '0.00', 'Receita Bruta - CMV'],
        ['DRE: Despesas Operacionais', dre?.period_start || '', dre?.period_end || '', dre?.operating_expenses || '0.00', 'Fixas, administrativas e comerciais'],
        ['DRE: Lucro Líquido do Exercício', dre?.period_start || '', dre?.period_end || '', dre?.net_profit || '0.00', 'Resultado final'],
        ['DRE: Margem Líquida (%)', dre?.period_start || '', dre?.period_end || '', `${dre?.margin_percentage || '0.0'}%`, 'Margem percentual de rentabilidade']
    ];

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`relatorio_dre_fluxo_caixa_${dateStr}.csv`, headers, rows);
}

// 3. Exportação do Razão Kardex Contábil
export function exportKardexToCsv(movements: any[]): void {
    const headers = [
        'Data / Hora',
        'Código Insumo',
        'Descrição do Insumo',
        'Unidade',
        'Almoxarifado',
        'Tipo Movimento',
        'Direção',
        'Quantidade Movimentada',
        'Custo Unitário Médio (R$)',
        'Valor Total da Operação (R$)',
        'Saldo Físico Resultante',
        'Custo Médio Resultante (R$)',
        'Valor Estoque Resultante (R$)',
        'Documento / Referência',
        'Observações'
    ];

    const rows = movements.map((m) => [
        m.created_at || '',
        m.item?.code || '',
        m.item?.name || '',
        m.item?.unit_of_measure || '',
        m.warehouse?.name || '',
        m.movement_type === 'purchase_in' ? 'Entrada por Compra' :
        m.movement_type === 'production_out' ? 'Consumo em Produção' :
        m.movement_type === 'adjustment_in' ? 'Ajuste Manual (+)' :
        m.movement_type === 'adjustment_out' ? 'Ajuste Manual (-)' : m.movement_type,
        m.direction === 'in' ? 'Entrada' : 'Saída',
        m.quantity || '0.00',
        m.unit_cost || '0.00',
        m.total_cost || '0.00',
        m.balance_quantity_after || '0.00',
        m.balance_average_cost_after || '0.00',
        m.balance_total_value_after || '0.00',
        m.reference_id ? `${m.reference_type || 'REF'}: ${m.reference_id}` : '',
        m.notes || ''
    ]);

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`razao_kardex_contabil_${dateStr}.csv`, headers, rows);
}

// 4. Exportação de Clientes e Fornecedores (CRM)
export function exportPartnersToCsv(partners: any[]): void {
    const headers = [
        'Razão Social / Nome',
        'Nome Fantasia',
        'Tipo',
        'Pessoa',
        'CPF / CNPJ',
        'Inscrição Estadual',
        'E-mail',
        'Telefone',
        'Status'
    ];

    const rows = partners.map((p) => [
        p.name || '',
        p.trade_name || '',
        p.type === 'customer' ? 'Cliente' : p.type === 'supplier' ? 'Fornecedor' : 'Ambos (Cliente & Fornecedor)',
        p.person_type === 'company' ? 'Jurídica' : 'Física',
        p.document_number || '',
        p.state_registration || '',
        p.email || '',
        p.phone || '',
        p.is_active ? 'Ativo' : 'Inativo'
    ]);

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`clientes_fornecedores_${dateStr}.csv`, headers, rows);
}

// 5. Exportação de Orçamentos Técnicos
export function exportQuotesToCsv(quotes: any[]): void {
    const headers = [
        'Número',
        'Título / Descrição',
        'Cliente',
        'Status',
        'Prazo de Entrega',
        'Valor Total (R$)',
        'Data Criação'
    ];

    const rows = quotes.map((q) => [
        q.quote_number || '',
        q.title || '',
        q.partner?.name || 'Cliente Geral',
        q.status_name || q.status || '',
        q.lead_time_days ? `${q.lead_time_days} dias úteis` : 'A combinar',
        q.total_amount || '0.00',
        q.created_at || ''
    ]);

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`orcamentos_tecnicos_${dateStr}.csv`, headers, rows);
}

// 6. Exportação de Estoque e Saldos
export function exportInventoryBalancesToCsv(balances: any[]): void {
    const headers = [
        'Código',
        'Insumo / Substrato',
        'Unidade',
        'Almoxarifado',
        'Saldo Físico',
        'Saldo Reservado',
        'Saldo Disponível',
        'Custo Médio Unitário (R$)',
        'Valor Total em Estoque (R$)'
    ];

    const rows = balances.map((b) => [
        b.item?.code || '',
        b.item?.name || '',
        b.item?.unit_of_measure || '',
        b.warehouse?.name || '',
        b.quantity_on_hand || '0.00',
        b.quantity_reserved || '0.00',
        b.quantity_available || '0.00',
        b.average_unit_cost || '0.00',
        b.total_value || '0.00'
    ]);

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`estoque_saldos_insumos_${dateStr}.csv`, headers, rows);
}

// 7. Exportação de Ordens de Produção (PCP)
export function exportProductionOrdersToCsv(orders: any[]): void {
    const headers = [
        'Código OP',
        'Produto / Ficha Técnica (BOM)',
        'Quantidade Planejada',
        'Quantidade Produzida',
        'Status Operacional',
        'Prioridade',
        'Previsão de Entrega',
        'Data Abertura'
    ];

    const rows = orders.map((o) => [
        o.order_number || '',
        o.bom?.name || o.product_name || 'Item Gráfico',
        o.quantity || '0',
        o.produced_quantity || '0',
        o.status_name || o.status || '',
        o.priority === 'urgent' ? 'Urgente' : o.priority === 'high' ? 'Alta' : 'Normal',
        o.target_completion_date || '',
        o.created_at || ''
    ]);

    const dateStr = new Date().toISOString().split('T')[0];
    downloadCsv(`ordens_de_producao_${dateStr}.csv`, headers, rows);
}
