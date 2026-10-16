<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Actions;

use App\Core\Tenancy\Models\Plan;
use Illuminate\Support\Str;

final class SavePlanAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?string $planId = null): Plan
    {
        $slug = isset($data['slug']) && $data['slug'] !== ''
            ? Str::slug((string) $data['slug'])
            : Str::slug((string) $data['name']);

        $attributes = [
            'name' => (string) $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => (float) ($data['price'] ?? 0.0),
            'billing_interval' => (string) ($data['billing_interval'] ?? 'monthly'),
            'max_users' => (int) ($data['max_users'] ?? 5),
            'max_storage_mb' => (int) ($data['max_storage_mb'] ?? 1024),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'features' => (array) ($data['features'] ?? []),
        ];

        if ($planId !== null && $planId !== '') {
            /** @var Plan $plan */
            $plan = Plan::query()->findOrFail($planId);
            $plan->update($attributes);

            return $plan;
        }

        /** @var Plan $plan */
        $plan = Plan::query()->create($attributes);

        return $plan;
    }
}
