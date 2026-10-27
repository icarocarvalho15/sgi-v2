<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Enums\AddressType;
use App\Modules\CRM\Http\Requests\StorePartnerRequest;
use App\Modules\CRM\Http\Requests\UpdatePartnerRequest;
use App\Modules\CRM\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();

        $query = Partner::with('addresses')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type !== '') {
            $query->where('partner_type', $type);
        }

        $partners = $query->paginate(20)->withQueryString();

        return Inertia::render('CRM/Partners/Index', [
            'partners' => $partners,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $addressData = $data['address'] ?? null;
        unset($data['address']);

        $partner = Partner::create($data);

        if ($addressData && ! empty($addressData['street'])) {
            $partner->addresses()->create([
                'address_type' => AddressType::Main,
                'postal_code' => $addressData['postal_code'] ?? '00000-000',
                'street' => $addressData['street'],
                'number' => $addressData['number'] ?? 'S/N',
                'complement' => $addressData['complement'] ?? null,
                'district' => $addressData['district'] ?? 'Centro',
                'city' => $addressData['city'] ?? 'São Paulo',
                'state' => strtoupper($addressData['state'] ?? 'SP'),
                'is_default' => true,
            ]);
        }

        return redirect()->route('tenant.crm.partners.index')
            ->with('success', 'Parceiro comercial cadastrado com sucesso!');
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $partner->update($request->validated());

        return redirect()->route('tenant.crm.partners.index')
            ->with('success', 'Parceiro comercial atualizado com sucesso!');
    }
}
