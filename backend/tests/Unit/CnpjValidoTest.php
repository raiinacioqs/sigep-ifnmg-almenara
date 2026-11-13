<?php

namespace Tests\Unit;

use App\Rules\CnpjValido;
use PHPUnit\Framework\TestCase;

class CnpjValidoTest extends TestCase
{
    private function validarCnpj(string $cnpj): bool
    {
        $regra = new CnpjValido();
        $erros = [];

        $regra->validate(
            'cnpj',
            $cnpj,
            function ($mensagem) use (&$erros) {
                $erros[] = $mensagem;
            }
        );

        return empty($erros);
    }

    public function test_aceita_cnpj_valido_sem_formatacao(): void
    {
        $this->assertTrue(
            $this->validarCnpj('11222333000181')
        );
    }

    public function test_aceita_cnpj_valido_com_formatacao(): void
    {
        $this->assertTrue(
            $this->validarCnpj('11.222.333/0001-81')
        );
    }

    public function test_rejeita_cnpj_com_quantidade_incorreta_de_digitos(): void
    {
        $this->assertFalse(
            $this->validarCnpj('1122233300018')
        );
    }

    public function test_rejeita_cnpj_com_digitos_repetidos(): void
    {
        $this->assertFalse(
            $this->validarCnpj('11111111111111')
        );
    }

    public function test_rejeita_cnpj_com_primeiro_digito_verificador_incorreto(): void
    {
        $this->assertFalse(
            $this->validarCnpj('11222333000191')
        );
    }

    public function test_rejeita_cnpj_com_segundo_digito_verificador_incorreto(): void
    {
        $this->assertFalse(
            $this->validarCnpj('11222333000182')
        );
    }
}
