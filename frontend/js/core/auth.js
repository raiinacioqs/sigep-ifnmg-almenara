(function () {
    'use strict';

    const CHAVE_TOKEN = 'sigep_token';
    const CHAVE_USUARIO = 'sigep_usuario';

    const perfis = {
        'Administrador': 'administrador',
        'Gestor de Almoxarifado': 'gestor_almoxarifado',
        'Servidor': 'servidor'
    };

    const paginasPorPerfil = {
        administrador: [
            'admin-usuarios.html',
            'admin-solicitacoes-usuarios.html',
            'admin-logs.html',
            'admin-configuracoes.html'
        ],

        gestor_almoxarifado: [
            'dashboard.html',
            'cadastro_entrada.html',
            'estoque_atual.html',
            'historico.html',
            'manutencoes.html',
            'solicitacao_retirada.html',
            'relatorios.html',
            'categorias.html',
            'setores.html',
            'responsaveis.html'
        ],

        servidor: [
            'servidor-estoque.html',
            'servidor-retirada.html'
        ]
    };

    function obterUsuario() {
        try {
            const dados = sessionStorage.getItem(CHAVE_USUARIO);
            return dados ? JSON.parse(dados) : null;
        } catch (erro) {
            console.error('Erro ao recuperar usuário:', erro);
            sessionStorage.removeItem(CHAVE_USUARIO);
            return null;
        }
    }

    function limparSessao() {
        sessionStorage.removeItem(CHAVE_TOKEN);
        sessionStorage.removeItem(CHAVE_USUARIO);
    }

    function redirecionarLogin(motivo = 'login') {
        window.location.replace(`../index.html?motivo=${motivo}`);
    }

    async function encerrarSessao() {
        try {
            if (window.SIGEPAPI.obterToken()) {
                await window.SIGEPAPI.requisicao('/logout', {
                    method: 'POST'
                });
            }
        } catch (erro) {
            console.error('Erro ao encerrar sessão no servidor:', erro);
        } finally {
            limparSessao();
            window.location.replace('../index.html');
        }
    }

    function atualizarIdentificacao(usuario) {
        const perfilSlug = perfis[usuario.perfil];

        document.documentElement.dataset.perfil = perfilSlug;
        document.documentElement.dataset.usuario = usuario.nome;

        document.querySelectorAll('[data-usuario-nome]').forEach((elemento) => {
            elemento.textContent = usuario.nome;
        });

        document.querySelectorAll('[data-usuario-perfil]').forEach((elemento) => {
            elemento.textContent = usuario.perfil;
        });
    }

    function configurarLinksSaida() {
        document.querySelectorAll('a').forEach((link) => {
            const destino = link.getAttribute('href') || '';
            const texto = link.textContent.trim().toLowerCase();

            if (
                texto.includes('sair') &&
                (destino.includes('index.html') || destino === '#')
            ) {
                link.addEventListener('click', function (evento) {
                    evento.preventDefault();
                    encerrarSessao();
                });
            }
        });
    }

    async function protegerPagina() {
        document.documentElement.dataset.authStatus = 'checking';
        
        const token = sessionStorage.getItem(CHAVE_TOKEN);
        

        if (!token) {
            redirecionarLogin();
            return;
        }

        try {
            const resposta = await window.SIGEPAPI.requisicao('/user', {
                method: 'GET'
            });

            const usuario = resposta.usuario;

            if (!usuario || usuario.status !== 'ativo') {
                limparSessao();
                redirecionarLogin();
                return;
            }

            const perfilSlug = perfis[usuario.perfil];

            if (!perfilSlug || !paginasPorPerfil[perfilSlug]) {
                redirecionarLogin('permissao');
                return;
            }

            const paginaAtual = window.location.pathname
                .split('/')
                .pop();

            if (!paginasPorPerfil[perfilSlug].includes(paginaAtual)) {
                redirecionarLogin('permissao');
                return;
            }

            sessionStorage.setItem(
                CHAVE_USUARIO,
                JSON.stringify(usuario)
            );

            atualizarIdentificacao(usuario);
            configurarLinksSaida();

            document.documentElement.dataset.authStatus = 'authenticated';

        } catch (erro) {
            console.error('Erro ao validar autenticação:', erro);

            if (erro.status === 401 || erro.status === 403) {
                limparSessao();
                redirecionarLogin();
                return;
            }

            document.documentElement.dataset.authStatus = 'error';

            alert(
                'Não foi possível validar sua sessão. Verifique a conexão com o servidor e tente novamente.'
            );
        }
    }

    window.SIGEPAuth = {
        obterUsuario,
        encerrarSessao,
        protegerPagina,
        paginasPorPerfil
    };

    protegerPagina();
})();
