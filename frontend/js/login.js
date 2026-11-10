document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const campoUsuario = document.getElementById('email');
    const campoSenha = document.getElementById('password');

    if (!form || !campoUsuario || !campoSenha) return;

    // Contas fictícias destinadas exclusivamente à demonstração do frontend.
    const usuariosDemo = [
        {
            usuario: 'admin',
            email: 'admin@sigep.demo',
            senha: 'Admin@123',
            nome: 'Administrador do Sistema',
            nomePerfil: 'Administrador',
            perfil: 'administrador',
            paginaInicial: 'pages/admin-usuarios.html'
        },
        {
            usuario: 'gestor',
            email: 'gestor@sigep.demo',
            senha: 'Gestor@123',
            nome: 'Gestor de Almoxarifado',
            nomePerfil: 'Gestor de Almoxarifado',
            perfil: 'gestor_almoxarifado',
            paginaInicial: 'pages/dashboard.html'
        },
        {
            usuario: 'servidor',
            email: 'servidor@sigep.demo',
            senha: 'Servidor@123',
            nome: 'Servidor do IFNMG',
            nomePerfil: 'Servidor',
            perfil: 'servidor',
            paginaInicial: 'pages/servidor-estoque.html'
        }
    ];

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

    const parametros = new URLSearchParams(window.location.search);
    if (parametros.get('motivo') === 'permissao') {
        exibirMensagem('Seu perfil não possui acesso à página solicitada.', 'warning');
    } else if (parametros.get('motivo') === 'login') {
        exibirMensagem('Faça login para acessar o SIGEP.', 'info');
    }

    form.addEventListener('submit', function (evento) {
        evento.preventDefault();

        const identificador = campoUsuario.value.trim().toLowerCase();
        const senha = campoSenha.value;

        const usuarioEncontrado = usuariosDemo.find((usuario) =>
            (usuario.usuario === identificador || usuario.email === identificador) && usuario.senha === senha
        );

        if (!usuarioEncontrado) {
            exibirMensagem('Usuário ou senha inválidos. Confira as credenciais demonstrativas.', 'danger');
            campoSenha.value = '';
            campoSenha.focus();
            return;
        }

        const sessao = {
            nome: usuarioEncontrado.nome,
            nomePerfil: usuarioEncontrado.nomePerfil,
            perfil: usuarioEncontrado.perfil
        };

        sessionStorage.setItem('sigep_demo_user', JSON.stringify(sessao));
        window.location.href = usuarioEncontrado.paginaInicial;
    });
});
