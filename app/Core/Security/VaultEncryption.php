<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Core\Tenancy\Exceptions\CrossTenantTamperingException;
use Illuminate\Contracts\Encryption\DecryptException;
use InvalidArgumentException;
use JsonException;

final class VaultEncryption
{
    private const CIPHER = 'aes-256-gcm';

    private const IV_LENGTH = 12; // 96-bit recomendado para GCM

    private const TAG_LENGTH = 16; // 128-bit authentication tag

    private const SALT_LENGTH = 16;

    /**
     * Criptografa dados em repouso com AES-256-GCM, HKDF por tenant_id e AAD vinculado.
     *
     * @param  string  $plainText  Dado sensível a ser criptografado
     * @param  string  $tenantId  UUID do Tenant proprietário do dado
     * @param  string  $purpose  Finalidade (ex: 'fiscal_cert', 'api_gateway', 'webhook_secret')
     * @return string Payload serializado em base64 com todos os parâmetros criptográficos
     */
    public function encrypt(string $plainText, string $tenantId, string $purpose = 'general'): string
    {
        if ($tenantId === '') {
            throw new InvalidArgumentException('tenantId não pode ser vazio para criptografia de cofre.');
        }

        $masterKey = $this->resolveMasterKey();
        $salt = random_bytes(self::SALT_LENGTH);
        $iv = random_bytes(self::IV_LENGTH);

        // Derivação de chave exclusiva para o Tenant via HKDF (RFC 5869)
        $info = "tenant-vault:{$tenantId}:{$purpose}";
        $derivedKey = hash_hkdf('sha256', $masterKey, 32, $info, $salt);

        // AAD (Additional Authenticated Data) amarra criptograficamente o tenant_id e purpose ao ciphertext
        $aad = "tenant_id:{$tenantId}|purpose:{$purpose}";

        $tag = '';
        $ciphertext = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new DecryptException('Falha interna ao criptografar dado no VaultEncryption.');
        }

        $payload = [
            'v' => 1,
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ciphertext' => base64_encode($ciphertext),
            'purpose' => $purpose,
        ];

        return base64_encode((string) json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Descriptografa dados do cofre garantindo que pertencem ao tenantId correto.
     *
     * @param  string  $encryptedPayload  Payload retornado pelo método encrypt
     * @param  string  $tenantId  UUID do Tenant que está solicitando a descriptografia
     * @param  string|null  $expectedPurpose  Finalidade esperada (opcional para verificação adicional)
     * @return string Dado em texto plano
     *
     * @throws DecryptException|CrossTenantTamperingException
     */
    public function decrypt(string $encryptedPayload, string $tenantId, ?string $expectedPurpose = null): string
    {
        if ($tenantId === '') {
            throw new InvalidArgumentException('tenantId não pode ser vazio para descriptografia de cofre.');
        }

        try {
            $rawJson = base64_decode($encryptedPayload, true);
            if ($rawJson === false) {
                throw new DecryptException('Payload do cofre não é um base64 válido.');
            }

            /** @var array{v: int, salt: string, iv: string, tag: string, ciphertext: string, purpose: string} $payload */
            $payload = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new DecryptException('Payload do cofre corrompido ou inválido.', 0, $e);
        }

        $salt = base64_decode($payload['salt'], true);
        $iv = base64_decode($payload['iv'], true);
        $tag = base64_decode($payload['tag'], true);
        $ciphertext = base64_decode($payload['ciphertext'], true);
        $purpose = $payload['purpose'] ?? 'general';

        if ($salt === false || $iv === false || $tag === false || $ciphertext === false) {
            throw new DecryptException('Componentes criptográficos do payload corrompidos.');
        }

        if ($expectedPurpose !== null && $purpose !== $expectedPurpose) {
            throw new DecryptException("Propósito criptográfico incompatível: esperado '{$expectedPurpose}', recebido '{$purpose}'.");
        }

        $masterKey = $this->resolveMasterKey();

        // Deriva a chave usando o tenantId fornecido
        $info = "tenant-vault:{$tenantId}:{$purpose}";
        $derivedKey = hash_hkdf('sha256', $masterKey, 32, $info, $salt);
        $aad = "tenant_id:{$tenantId}|purpose:{$purpose}";

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($decrypted === false) {
            throw new CrossTenantTamperingException(
                'Falha na descriptografia do Vault: a chave do Tenant solicitante não corresponde ou os dados foram adulterados.'
            );
        }

        return $decrypted;
    }

    /**
     * Resolve e valida a master APP_KEY do arquivo de configuração do Laravel.
     */
    private function resolveMasterKey(): string
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded !== false && strlen($decoded) === 32) {
                return $decoded;
            }
        }

        if (strlen($appKey) === 32) {
            return $appKey;
        }

        throw new DecryptException('APP_KEY inválida ou não configurada para AES-256 no cofre criptográfico.');
    }
}
