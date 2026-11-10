/**
 * Autenticação demonstrativa do SIGEP.
 * ATENÇÃO: esta implementação é apenas para demonstração acadêmica.
 * A autenticação e a autorização reais deverão ser implementadas no Laravel.
 */
(function () {
    'use strict';

    const CHAVE_SESSAO = 'sigep_demo_user';

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
            const dados = sessionStorage.getItem(CHAVE_SESSAO);
            return dados ? JSON.parse(dados) : null;
        } catch (erro) {
            console.error('Não foi possível recuperar a sessão demonstrativa:', erro);
            sessionStorage.removeItem(CHAVE_SESSAO);
            return null;
        }
    }

    function encerrarSessao() {
        sessionStorage.removeItem(CHAVE_SESSAO);
        window.location.href = '../index.html';
    }

    function protegerPagina() {
        const usuario = obterUsuario();
        const paginaAtual = window.location.pathname.split('/').pop();

        if (!usuario || !paginasPorPerfil[usuario.perfil]) {
            window.location.replace('../index.html?motivo=login');
            return;
        }

        const paginasPermitidas = paginasPorPerfil[usuario.perfil];
        if (!paginasPermitidas.includes(paginaAtual)) {
            window.location.replace('../index.html?motivo=permissao');
            return;
        }

        document.documentElement.dataset.perfil = usuario.perfil;
        document.documentElement.dataset.usuario = usuario.nome;

        // Atualiza os elementos de identificação que já existirem no HTML.
        document.querySelectorAll('[data-usuario-nome]').forEach((elemento) => {
            elemento.textContent = usuario.nome;
        });
        document.querySelectorAll('[data-usuario-perfil]').forEach((elemento) => {
            elemento.textContent = usuario.nomePerfil;
        });

        // Os links de saída existentes apontam para index.html.
        document.querySelectorAll('a[href="../index.html"], a[href="index.html"]').forEach((link) => {
            const texto = link.textContent.toLowerCase();
            if (texto.includes('sair')) {
                link.addEventListener('click', (evento) => {
                    evento.preventDefault();
                    encerrarSessao();
                });
            }
        });
    }

    window.SIGEPAuth = {
        obterUsuario,
        encerrarSessao,
        protegerPagina,
        paginasPorPerfil
    };

    protegerPagina();
})();
