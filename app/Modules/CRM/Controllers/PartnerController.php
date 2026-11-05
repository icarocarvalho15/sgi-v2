<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Requests\StorePartnerRequest;
use App\Modules\CRM\Requests\UpdatePartnerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $query = Partner::query()->with('addresses');

        if ($request->filled('type')) {
            $query->where(function ($q) use ($request) {
                $q->where('type', $request->query('type'))
                  ->orWhere('type', 'both');
            });
        }

        if ($request->filled('search')) {
            $search = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('trade_name', 'ilike', $search)
                  ->orWhere('document_number', 'like', $search);
            });
        }

        $partners = $query->orderBy('name')->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($partners);
        }

        return Inertia::render('CRM/Partners/Index', [
            'partners' => $partners,
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CRM/Partners/Form', [
            'partner' => null,
        ]);
    }

    public function store(StorePartnerRequest $request): RedirectResponse|JsonResponse
    {
        $partner = DB::transaction(function () use ($request): Partner {
            $partner = Partner::create($request->safe()->except('addresses'));

            if ($request->has('addresses')) {
                foreach ($request->input('addresses', []) as $addressData) {
                    $partner->addresses()->create($addressData);
                }
            }

            return $partner;
        });

        if ($request->wantsJson()) {
            return response()->json($partner->load('addresses'), 201);
        }

        return redirect()->route('crm.partners.index')
            ->with('success', 'Cliente/Fornecedor cadastrado com sucesso.');
    }

    public function show(Partner $partner): Response|JsonResponse
    {
        $partner->load(['addresses', 'quotes.status']);

        if (request()->wantsJson()) {
            return response()->json($partner);
        }

        return Inertia::render('CRM/Partners/Show', [
            'partner' => $partner,
        ]);
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('CRM/Partners/Form', [
            'partner' => $partner->load('addresses'),
        ]);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse|JsonResponse
    {
        $partner->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json($partner);
        }

        return redirect()->route('crm.partners.index')
            ->with('success', 'Cadastro atualizado com sucesso.');
    }

    public function destroy(Partner $partner): RedirectResponse|JsonResponse
    {
        $partner->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Parceiro excluído com sucesso.']);
        }

        return redirect()->route('crm.partners.index')
            ->with('success', 'Parceiro excluído com sucesso.');
    }
}
