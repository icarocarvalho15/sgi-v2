<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Core\Security\Exceptions\VaultDecryptionException;
use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\TenantContext;
use JsonException;

final class VaultEncryption
{
    private const CIPHER_ALGO = 'aes-256-gcm';
    private const IV_LENGTH = 12; // 96-bit recommended for GCM
    private const TAG_LENGTH = 16; // 128-bit authentication tag
    private const HKDF_SALT = 'sgi-v2-vault-salt-strict';

    /**
     * Derives a unique 256-bit encryption key per Tenant and Context using HKDF-SHA256.
     */
    public static function deriveTenantKey(string $tenantId, string $context = 'credentials'): string
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $masterKey = base64_decode(substr($appKey, 7), true);
            if ($masterKey === false) {
                $masterKey = $appKey;
            }
        } else {
            $masterKey = $appKey;
        }

        $info = "tenant-vault:{$tenantId}:{$context}";

        return hash_hkdf('sha256', $masterKey, 32, $info, self::HKDF_SALT);
    }

    /**
     * Encrypts sensitive data (A1 certificates, API keys, passwords) using AES-256-GCM
     * with tenant-derived HKDF key and tenant_id as Additional Authenticated Data (AAD).
     */
    public static function encrypt(mixed $data, string $tenantId, string $context = 'credentials'): string
    {
        $plaintext = is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR);

        $derivedKey = self::deriveTenantKey($tenantId, $context);
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);

        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER_ALGO,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $tenantId, // AAD (Additional Authenticated Data binds ciphertext to tenant)
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new VaultDecryptionException('Falha interna ao criptografar dados no cofre Vault.');
        }

        // Binary packing: IV (12 bytes) + TAG (16 bytes) + CIPHERTEXT
        $packet = $iv . $tag . $ciphertext;

        return base64_encode($packet);
    }

    /**
     * Decrypts encrypted payload using the tenant-derived key and verifies authentication tag.
     * Throws VaultDecryptionException if tampered with or if decrypted with a different tenant key.
     */
    public static function decrypt(string $payload, string $tenantId, string $context = 'credentials'): mixed
    {
        $binary = base64_decode($payload, true);

        if ($binary === false || strlen($binary) <= (self::IV_LENGTH + self::TAG_LENGTH)) {
            throw new VaultDecryptionException('Payload cifrado inválido ou truncado.');
        }

        $iv = substr($binary, 0, self::IV_LENGTH);
        $tag = substr($binary, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($binary, self::IV_LENGTH + self::TAG_LENGTH);

        $derivedKey = self::deriveTenantKey($tenantId, $context);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER_ALGO,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $tenantId // AAD verification
        );

        if ($decrypted === false) {
            throw new VaultDecryptionException(
                "Falha na autenticação AES-256-GCM: dados violados ou chave incorreta para tenant [{$tenantId}]."
            );
        }

        try {
            return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $decrypted;
        }
    }

    /**
     * Convenience method: Encrypts data for the current active TenantContext.
     */
    public static function encryptForCurrentTenant(mixed $data, string $context = 'credentials'): string
    {
        $tenantId = TenantContext::id();

        if (empty($tenantId)) {
            throw new TenantContextMissingException('Impossível criptografar no cofre sem TenantContext ativo.');
        }

        return self::encrypt($data, $tenantId, $context);
    }

    /**
     * Convenience method: Decrypts data for the current active TenantContext.
     */
    public static function decryptForCurrentTenant(string $payload, string $context = 'credentials'): mixed
    {
        $tenantId = TenantContext::id();

        if (empty($tenantId)) {
            throw new TenantContextMissingException('Impossível descriptografar no cofre sem TenantContext ativo.');
        }

        return self::decrypt($payload, $tenantId, $context);
    }
}
