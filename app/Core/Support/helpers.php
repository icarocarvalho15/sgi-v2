<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;

if (! function_exists('tenant_context')) {
    /**
     * Retorna a instância Singleton do TenantContext.
     */
    function tenant_context(): TenantContext
    {
        return app(TenantContext::class);
    }
}

if (! function_exists('tenant')) {
    /**
     * Retorna o Tenant atualmente ativo ou nulo.
     */
    function tenant(): ?Tenant
    {
        return tenant_context()->get();
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Retorna o UUID do Tenant atualmente ativo ou nulo.
     */
    function tenant_id(): ?string
    {
        return tenant_context()->id();
    }
}

if (! function_exists('mb_strimwidth')) {
    /**
     * Polyfill para mb_strimwidth caso a extensão não o tenha compilado.
     */
    function mb_strimwidth(string $string, int $start, int $width, string $trimmarker = '', ?string $encoding = null): string
    {
        $encoding ??= mb_internal_encoding();
        $strLen = mb_strlen($string, $encoding);

        if ($strLen <= $width) {
            return mb_substr($string, $start, null, $encoding);
        }

        $markerLen = mb_strlen($trimmarker, $encoding);
        $targetLen = max(0, $width - $markerLen);

        return mb_substr($string, $start, $targetLen, $encoding).$trimmarker;
    }
}
