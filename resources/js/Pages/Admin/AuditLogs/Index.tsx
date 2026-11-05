import React from 'react';
import { Head, Link } from '@inertiajs/react';
import LandlordLayout from '@/Layouts/LandlordLayout';
import { 
    ShieldAlert, 
    Clock, 
    Globe, 
    User, 
    Building2,
    Calendar,
    CheckCircle2
} from 'lucide-react';

interface ImpersonationLog {
    id: string;
    reason: string;
    ip_address: string | null;
    started_at: string;
    ended_at: string | null;
    user: {
        id: string;
        name: string;
        email: string;
    };
    tenant: {
        id: string;
        name: string;
        subdomain: string;
    };
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface Props {
    logs: Pagination<ImpersonationLog>;
}

export default function AuditLogsIndex({ logs }: Props) {
    return (
        <LandlordLayout title="Auditoria & Compliance de Suporte">
            <Head title="Landlord - Logs de Auditoria" />

            <div className="space-y-8">
                {/* Header */}
                <div className="bg-slate-900 p-6 rounded-2xl border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                <ShieldAlert className="w-3.5 h-3.5" /> Rastreabilidade & Compliance
                            </span>
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Logs de Personificação Técnica</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Registro imutável de todas as sessões de suporte prestadas a empresas assinantes.
                        </p>
                    </div>

                    <div className="text-right">
                        <span className="text-xs text-slate-400 block font-medium">Total de Registros</span>
                        <span className="text-2xl font-bold text-amber-400">{logs.total}</span>
                    </div>
                </div>

                {/* Tabela de Logs */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-300">
                            <thead className="bg-slate-950/60 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                                <tr>
                                    <th className="px-6 py-4">SuperAdmin Responsável</th>
                                    <th className="px-6 py-4">Empresa (Tenant)</th>
                                    <th className="px-6 py-4">Justificativa do Chamado</th>
                                    <th className="px-6 py-4">Endereço IP</th>
                                    <th className="px-6 py-4">Início da Sessão</th>
                                    <th className="px-6 py-4">Fim da Sessão</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 font-mono">
                                {logs.data.map((log) => (
                                    <tr key={log.id} className="hover:bg-slate-800/30 transition-colors">
                                        <td className="px-6 py-4 font-sans font-semibold text-white">
                                            <div>{log.user?.name}</div>
                                            <div className="text-[11px] text-slate-400 font-mono font-normal">{log.user?.email}</div>
                                        </td>
                                        <td className="px-6 py-4 font-sans">
                                            <span className="text-amber-400 font-semibold">{log.tenant?.name}</span>
                                            <span className="text-[10px] text-slate-500 block font-mono">{log.tenant?.subdomain}.sgi-erp.com</span>
                                        </td>
                                        <td className="px-6 py-4 font-sans text-slate-300 max-w-sm">
                                            {log.reason}
                                        </td>
                                        <td className="px-6 py-4 text-slate-400">
                                            {log.ip_address || '127.0.0.1'}
                                        </td>
                                        <td className="px-6 py-4 text-slate-400">
                                            {new Date(log.started_at).toLocaleString('pt-BR')}
                                        </td>
                                        <td className="px-6 py-4 text-slate-400">
                                            {log.ended_at ? new Date(log.ended_at).toLocaleString('pt-BR') : '—'}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            {log.ended_at ? (
                                                <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                                    Concluída
                                                </span>
                                            ) : (
                                                <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 animate-pulse">
                                                    Em Andamento
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {logs.data.length === 0 && (
                        <div className="p-8 text-center text-sm text-slate-500">
                            Nenhum log de personificação registrado.
                        </div>
                    )}

                    {/* Paginação */}
                    {logs.last_page > 1 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between text-xs">
                            <span className="text-slate-400">
                                Página {logs.current_page} de {logs.last_page}
                            </span>
                            <div className="flex gap-2">
                                {logs.prev_page_url && (
                                    <Link
                                        href={logs.prev_page_url}
                                        className="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-medium"
                                    >
                                        Anterior
                                    </Link>
                                )}
                                {logs.next_page_url && (
                                    <Link
                                        href={logs.next_page_url}
                                        className="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-medium"
                                    >
                                        Próxima
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </LandlordLayout>
    );
}
