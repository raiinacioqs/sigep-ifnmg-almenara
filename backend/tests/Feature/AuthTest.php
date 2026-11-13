<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $status = 'ativo'): User
    {
        $role = Role::create([
            'nome' => 'Administrador',
            'descricao' => 'Perfil administrativo',
        ]);

        return User::factory()->create([
            'name' => 'Administrador de Teste',
            'email' => 'admin@teste.local',
            'password' => 'Senha@123456',
            'role_id' => $role->id,
            'status' => $status,
        ]);
    }

    public function test_usuario_consegue_realizar_login(): void
    {
        $this->criarUsuario();

        $response = $this->postJson('/api/login', [
            'email' => 'admin@teste.local',
            'password' => 'Senha@123456',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'usuario' => [
                    'id',
                    'nome',
                    'email',
                    'status',
                    'perfil',
                ],
            ]);
    }

    public function test_login_com_senha_incorreta_e_rejeitado(): void
    {
        $this->criarUsuario();

        $this->postJson('/api/login', [
            'email' => 'admin@teste.local',
            'password' => 'SenhaErrada',
        ])->assertUnprocessable();
    }

    public function test_usuario_inativo_nao_consegue_realizar_login(): void
    {
        $this->criarUsuario('inativo');

        $this->postJson('/api/login', [
            'email' => 'admin@teste.local',
            'password' => 'Senha@123456',
        ])->assertForbidden();
    }

    public function test_usuario_nao_autenticado_nao_acessa_seus_dados(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_usuario_autenticado_consegue_consultar_seus_dados(): void
    {
        $usuario = $this->criarUsuario();

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('usuario.email', 'admin@teste.local');
    }

    public function test_logout_revoga_o_token_atual(): void
    {
        $usuario = $this->criarUsuario();

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $usuario->id,
            'name' => 'teste',
        ]);
    }


    public function test_usuario_desativado_nao_consegue_utilizar_token_existente(): void
    {
        $usuario = $this->criarUsuario();

        $token = $usuario->createToken('teste')->plainTextToken;

        // Simula a desativação pelo administrador.
        $usuario->update([
            'status' => 'inativo',
        ]);

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertForbidden();
    }

}