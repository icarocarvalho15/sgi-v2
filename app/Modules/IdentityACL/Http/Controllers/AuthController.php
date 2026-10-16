<?php

declare(strict_types=1);

namespace App\Modules\IdentityACL\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AuthController
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->is_super_admin) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('tenant.dashboard');
        }

        return Inertia::render('IdentityACL/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        // 1. Super Administrador vai para o Core Admin
        if ($user->is_super_admin) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // 2. Usuário com tenants
        $tenants = $user->tenants()->get();

        if ($tenants->count() === 1) {
            $tenant = $tenants->first();
            $request->session()->put('active_tenant_id', $tenant->id);

            return redirect()->intended(route('tenant.dashboard'));
        }

        if ($tenants->count() > 1) {
            return redirect()->route('identity.select-tenant');
        }

        Auth::logout();
        throw ValidationException::withMessages([
            'email' => 'Sua conta não possui nenhuma empresa associada no momento.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
