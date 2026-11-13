<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarComo(string $perfil): void
    {
        $role = Role::create([
            'nome' => $perfil,
            'descricao' => 'Perfil utilizado nos testes',
        ]);

        $usuario = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'ativo',
        ]);

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);
    }

    public function test_administrador_acessa_area_administrativa(): void
    {
        $this->autenticarComo('Administrador');

        $this->getJson('/api/admin/teste')
            ->assertOk();
    }

    public function test_administrador_acessa_area_de_gestao(): void
    {
        $this->autenticarComo('Administrador');

        $this->getJson('/api/gestao/teste')
            ->assertOk();
    }

    public function test_gestor_acessa_area_de_gestao(): void
    {
        $this->autenticarComo('Gestor de Almoxarifado');

        $this->getJson('/api/gestao/teste')
            ->assertOk();
    }

    public function test_gestor_nao_acessa_area_administrativa(): void
    {
        $this->autenticarComo('Gestor de Almoxarifado');

        $this->getJson('/api/admin/teste')
            ->assertForbidden();
    }

    public function test_servidor_acessa_sua_area(): void
    {
        $this->autenticarComo('Servidor');

        $this->getJson('/api/servidor/teste')
            ->assertOk();
    }

    public function test_servidor_nao_acessa_area_de_gestao(): void
    {
        $this->autenticarComo('Servidor');

        $this->getJson('/api/gestao/teste')
            ->assertForbidden();
    }

    public function test_usuario_sem_perfil_nao_acessa_area_de_gestao(): void
    {
        $usuario = User::factory()->create([
            'role_id' => null,
            'status' => 'ativo',
        ]);

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/gestao/teste')
            ->assertForbidden();
    }
}