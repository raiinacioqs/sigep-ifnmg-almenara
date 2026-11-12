document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const campoUsuario = document.getElementById('email');
    const campoSenha = document.getElementById('password');
    const botaoEntrar = form?.querySelector('button[type="submit"]');

    if (!form || !campoUsuario || !campoSenha) return;

    function exibirMensagem(mensagem, tipo) {
        let alerta = document.getElementById('loginFeedback');

        if (!alerta) {
            alerta = document.createElement('div');
            alerta.id = 'loginFeedback';
            alerta.className = 'alert mt-3 mb-0';
            form.appendChild(alerta);
        }

        alerta.className = `alert alert-${tipo} mt-3 mb-0`;
        alerta.textContent = mensagem;
    }

    function obterPaginaInicial(perfil) {
        const paginas = {
            'Administrador': 'pages/admin-usuarios.html',
            'Gestor de Almoxarifado': 'pages/dashboard.html',
            'Servidor': 'pages/servidor-estoque.html'
        };

        return paginas[perfil] || null;
    }

    const parametros = new URLSearchParams(window.location.search);

    if (parametros.get('motivo') === 'permissao') {
        exibirMensagem(
            'Seu perfil não possui acesso à página solicitada.',
            'warning'
        );
    } else if (parametros.get('motivo') === 'login') {
        exibirMensagem(
            'Faça login para acessar o SIGEP.',
            'info'
        );
    }

    form.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        const email = campoUsuario.value.trim();
        const senha = campoSenha.value;

        if (!email || !senha) {
            exibirMensagem('Informe seu e-mail e sua senha.', 'warning');
            return;
        }

        if (botaoEntrar) {
            botaoEntrar.disabled = true;
            botaoEntrar.textContent = 'Autenticando...';
        }

        try {
            const resposta = await window.SIGEPAPI.requisicao('/login', {
                method: 'POST',
                body: {
                    email: email,
                    password: senha
                }
            });

            const usuario = resposta.usuario;
            const paginaInicial = obterPaginaInicial(usuario?.perfil);

            if (!resposta.token || !usuario || !paginaInicial) {
                throw new Error(
                    'Não foi possível identificar o perfil do usuário.'
                );
            }

            window.SIGEPAPI.salvarToken(resposta.token);

            sessionStorage.setItem(
                'sigep_usuario',
                JSON.stringify(usuario)
            );

            window.location.href = paginaInicial;

        } catch (erro) {
            console.error('Erro no login:', erro);

            if (erro.status === 422) {
                exibirMensagem(
                    'E-mail ou senha inválidos. Verifique suas credenciais.',
                    'danger'
                );
            } else if (erro.status === 403) {
                exibirMensagem(erro.message, 'warning');
            } else if (!erro.status) {
                exibirMensagem(
                    'Não foi possível conectar ao servidor. Verifique se o Laravel está em execução.',
                    'danger'
                );
            } else {
                exibirMensagem(
                    erro.message || 'Erro ao realizar login.',
                    'danger'
                );
            }

            campoSenha.value = '';
            campoSenha.focus();

        } finally {
            if (botaoEntrar) {
                botaoEntrar.disabled = false;
                botaoEntrar.textContent = 'Entrar';
            }
        }
    });
});
