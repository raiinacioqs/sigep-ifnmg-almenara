<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O CNPJ informado deve ser um texto válido.');
            return;
        }

        // Remove pontos, barras, hífens e outros caracteres não numéricos.
        $cnpj = preg_replace('/\D/', '', $value);

        // O CNPJ deve possuir exatamente 14 dígitos.
        if (strlen($cnpj) !== 14) {
            $fail('O CNPJ deve conter exatamente 14 dígitos.');
            return;
        }

        // Rejeita sequências com todos os dígitos iguais.
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('O CNPJ informado é inválido.');
            return;
        }

        // Calcula o primeiro dígito verificador.
        $pesosPrimeiroDigito = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;

        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesosPrimeiroDigito[$i];
        }

        $resto = $soma % 11;
        $primeiroDigito = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[12] !== $primeiroDigito) {
            $fail('O CNPJ informado é inválido.');
            return;
        }

        // Calcula o segundo dígito verificador.
        $pesosSegundoDigito = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;

        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesosSegundoDigito[$i];
        }

        $resto = $soma % 11;
        $segundoDigito = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[13] !== $segundoDigito) {
            $fail('O CNPJ informado é inválido.');
        }
    }
}