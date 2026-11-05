<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Core\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        $tenants = Tenant::where('status', 'active')->orderBy('name')->get(['id', 'name', 'subdomain']);

        return Inertia::render('Auth/Login', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $credentials['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'email' => __('As credenciais fornecidas não conferem com nossos registros.'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->tenant_id) {
            $request->session()->put('current_tenant_id', $user->tenant_id);
        }

        if ($user->is_super_admin && ! $user->tenant_id) {
            return redirect()->intended('/admin/tenants');
        }

        return redirect()->intended('/crm/partners');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
