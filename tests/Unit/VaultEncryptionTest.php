<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Security\Exceptions\VaultDecryptionException;
use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Str;
use Tests\TestCase;

class VaultEncryptionTest extends TestCase
{
    private string $tenantAId;
    private string $tenantBId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantAId = (string) Str::uuid();
        $this->tenantBId = (string) Str::uuid();
    }

    public function test_it_encrypts_and_decrypts_strings_for_a_tenant(): void
    {
        $sensitiveData = 'CertificadoA1Password#Secret2026';

        $encrypted = VaultEncryption::encrypt($sensitiveData, $this->tenantAId, 'cert_pass');

        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($sensitiveData, $encrypted);

        $decrypted = VaultEncryption::decrypt($encrypted, $this->tenantAId, 'cert_pass');
        $this->assertEquals($sensitiveData, $decrypted);
    }

    public function test_it_encrypts_and_decrypts_structured_json_payloads(): void
    {
        $payload = [
            'gateway' => 'asaas',
            'api_key' => '$aact_YTU5YTE0M2M6N2NmMC00N...',
            'wallet_id' => 'c74f5698-c118-472d-862d-0b3eeea7ad8b',
        ];

        $encrypted = VaultEncryption::encrypt($payload, $this->tenantAId, 'gateway_credentials');
        $decrypted = VaultEncryption::decrypt($encrypted, $this->tenantAId, 'gateway_credentials');

        $this->assertIsArray($decrypted);
        $this->assertEquals($payload, $decrypted);
    }

    public function test_cross_tenant_decryption_strictly_fails(): void
    {
        $tenantASecret = 'FiscalA1PrivateKeyForGraficaAlpha';

        $encrypted = VaultEncryption::encrypt($tenantASecret, $this->tenantAId, 'a1_cert');

        $this->expectException(VaultDecryptionException::class);

        // Tenant B attempting to decrypt Tenant A's secret
        VaultEncryption::decrypt($encrypted, $this->tenantBId, 'a1_cert');
    }

    public function test_tampered_ciphertext_strictly_fails_authentication(): void
    {
        $secret = 'SensitiveWebhookSecretSignature';

        $encrypted = VaultEncryption::encrypt($secret, $this->tenantAId, 'webhook');

        $binary = base64_decode($encrypted);
        // Tamper with the last byte of ciphertext
        $tamperedBinary = substr($binary, 0, -1) . chr(ord(substr($binary, -1)) ^ 0xFF);
        $tamperedPayload = base64_encode($tamperedBinary);

        $this->expectException(VaultDecryptionException::class);
        VaultEncryption::decrypt($tamperedPayload, $this->tenantAId, 'webhook');
    }

    public function test_different_contexts_have_different_derived_keys(): void
    {
        $secret = 'TokenSharedSecret';

        $encrypted = VaultEncryption::encrypt($secret, $this->tenantAId, 'context_one');

        $this->expectException(VaultDecryptionException::class);
        // Same tenant but different context
        VaultEncryption::decrypt($encrypted, $this->tenantAId, 'context_two');
    }

    public function test_it_encrypts_and_decrypts_using_active_tenant_context(): void
    {
        TenantContext::set($this->tenantAId);

        $secret = 'ActiveContextSecretValue';
        $encrypted = VaultEncryption::encryptForCurrentTenant($secret, 'active_ctx');
        $decrypted = VaultEncryption::decryptForCurrentTenant($encrypted, 'active_ctx');

        $this->assertEquals($secret, $decrypted);

        TenantContext::clear();
    }
}
