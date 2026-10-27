<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Enums\DealPriority;
use App\Modules\CRM\Enums\DealStatus;
use App\Modules\CRM\Http\Requests\StoreDealRequest;
use App\Modules\CRM\Http\Requests\UpdateDealStageRequest;
use App\Modules\CRM\Models\CrmDeal;
use App\Modules\CRM\Models\CrmDealStage;
use App\Modules\CRM\Models\CrmPipeline;
use App\Modules\CRM\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CrmDealController extends Controller
{
    public function index(Request $request): Response
    {
        $pipeline = CrmPipeline::with(['stages' => function ($query) {
            $query->orderBy('sequence');
        }, 'stages.deals' => function ($query) {
            $query->with(['partner', 'assignedUser'])->latest();
        }])->first();

        // Se o Tenant ainda não tem um Funil inicial, cria o padrão automaticamente
        if (! $pipeline) {
            $pipeline = $this->createDefaultPipeline();
            $pipeline->load(['stages.deals.partner']);
        }

        $partners = Partner::where('is_active', true)
            ->whereIn('partner_type', ['CUSTOMER', 'BOTH'])
            ->orderBy('name')
            ->select('id', 'name', 'trade_name', 'document_number')
            ->get();

        return Inertia::render('CRM/Deals/Index', [
            'pipeline' => $pipeline,
            'partners' => $partners,
        ]);
    }

    public function store(StoreDealRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['status'])) {
            $data['status'] = DealStatus::Open;
        }
        if (empty($data['priority'])) {
            $data['priority'] = DealPriority::Medium;
        }

        CrmDeal::create($data);

        return redirect()->route('tenant.crm.deals.index')
            ->with('success', 'Oportunidade cadastrada com sucesso!');
    }

    public function updateStage(UpdateDealStageRequest $request, CrmDeal $deal): RedirectResponse
    {
        $stageId = $request->string('stage_id')->toString();
        $stage = CrmDealStage::findOrFail($stageId);

        $status = DealStatus::Open;
        if ($stage->is_won) {
            $status = DealStatus::Won;
        } elseif ($stage->is_lost) {
            $status = DealStatus::Lost;
        }

        $deal->update([
            'stage_id' => $stage->id,
            'status' => $status,
            'lost_reason' => $request->input('lost_reason'),
        ]);

        return redirect()->route('tenant.crm.deals.index')
            ->with('success', "Oportunidade movida para a etapa {$stage->name}!");
    }

    private function createDefaultPipeline(): CrmPipeline
    {
        $pipeline = CrmPipeline::create([
            'name' => 'Funil Industrial Padrão',
            'is_default' => true,
            'is_active' => true,
        ]);

        $defaultStages = [
            ['name' => 'Prospecção', 'sequence' => 1, 'probability_percentage' => '10.00', 'color' => '#64748b', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Qualificação & Diagnóstico', 'sequence' => 2, 'probability_percentage' => '30.00', 'color' => '#3b82f6', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Orçamento / Engenharia', 'sequence' => 3, 'probability_percentage' => '60.00', 'color' => '#8b5cf6', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Negociação & Fechamento', 'sequence' => 4, 'probability_percentage' => '80.00', 'color' => '#f59e0b', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Ganha / Convertida', 'sequence' => 5, 'probability_percentage' => '100.00', 'color' => '#10b981', 'is_won' => true, 'is_lost' => false],
            ['name' => 'Perdida', 'sequence' => 6, 'probability_percentage' => '0.00', 'color' => '#ef4444', 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($defaultStages as $stage) {
            $pipeline->stages()->create($stage);
        }

        return $pipeline;
    }
}
