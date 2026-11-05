<?php

declare(strict_types=1);

namespace App\Core\Security\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CpfCnpjRule implements ValidationRule
{
    /**
     * Valida matematicamente CPF (11 dígitos) ou CNPJ (14 dígitos) usando algoritmo de dígitos verificadores (módulo 11).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('O documento fiscal informado é inválido.');
            return;
        }

        $clean = preg_replace('/\D/', '', $value);

        if (strlen($clean) === 11) {
            if (! $this->validateCpf($clean)) {
                $fail('O CPF informado é inválido.');
            }
            return;
        }

        if (strlen($clean) === 14) {
            if (! $this->validateCnpj($clean)) {
                $fail('O CNPJ informado é inválido.');
            }
            return;
        }

        $fail('O documento fiscal deve conter 11 dígitos (CPF) ou 14 dígitos (CNPJ).');
    }

    private function validateCpf(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += ((int) $cpf[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if (((int) $cpf[$c]) !== $d) {
                return false;
            }
        }

        return true;
    }

    private function validateCnpj(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $multipliers1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $multipliers2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum1 += ((int) $cnpj[$i]) * $multipliers1[$i];
        }
        $digit1 = $sum1 % 11 < 2 ? 0 : 11 - ($sum1 % 11);
        if (((int) $cnpj[12]) !== $digit1) {
            return false;
        }

        $sum2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum2 += ((int) $cnpj[$i]) * $multipliers2[$i];
        }
        $digit2 = $sum2 % 11 < 2 ? 0 : 11 - ($sum2 % 11);
        if (((int) $cnpj[13]) !== $digit2) {
            return false;
        }

        return true;
    }
}
