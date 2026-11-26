document.addEventListener('DOMContentLoaded', () => {
    const tabelaUsuarios = document.getElementById('listaUsuarios');
    const formEditar = document.getElementById('formEditarUsuario');
    const modalElemento = document.getElementById('modalEditarUsuario');

    if (!tabelaUsuarios) {
        console.error('Elemento #listaUsuarios não encontrado.');
        return;
    }

    if (!formEditar || !modalElemento) {
        console.error('Formulário ou modal de edição não encontrado.');
        return;
    }

    const modalEditar = bootstrap.Modal.getOrCreateInstance(modalElemento);

    const campoId = document.getElementById('usuarioId');
    const campoNome = document.getElementById('usuarioNome');
    const campoEmail = document.getElementById('usuarioEmail');
    const campoSiape = document.getElementById('usuarioSiape');
    const campoPerfil = document.getElementById('usuarioPerfil');
    const campoSetor = document.getElementById('usuarioSetor');
    const mensagemErro = document.getElementById('erroEditarUsuario');
    const botaoSalvar = document.getElementById('btnSalvarUsuario');

    let usuarios = [];
    let setores = [];

    carregarUsuarios();

    async function carregarUsuarios() {
        mostrarCarregamento();

        try {
            const [respostaUsuarios, respostaSetores] = await Promise.all([
                SIGEPAPI.requisicao('/admin/usuarios'),
                SIGEPAPI.requisicao('/setores')
            ]);

            usuarios = extrairLista(respostaUsuarios);
            setores = extrairLista(respostaSetores);

            renderizarUsuarios();
        } catch (erro) {
            console.error('Erro ao carregar usuários:', erro);

            tabelaUsuarios.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        Não foi possível carregar os usuários.
                    </td>
                </tr>
            `;
        }
    }

    function extrairLista(resposta) {
        if (Array.isArray(resposta)) {
            return resposta;
        }

        if (Array.isArray(resposta?.data)) {
            return resposta.data;
        }

        if (Array.isArray(resposta?.data?.data)) {
            return resposta.data.data;
        }

        return [];
    }

    function mostrarCarregamento() {
        tabelaUsuarios.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    Carregando usuários...
                </td>
            </tr>
        `;
    }

    function renderizarUsuarios() {
        if (!usuarios.length) {
            tabelaUsuarios.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Nenhum usuário encontrado.
                    </td>
                </tr>
            `;
            return;
        }

        tabelaUsuarios.innerHTML = usuarios.map(usuario => {
            const nome = escaparHTML(usuario.name ?? '—');
            const email = escaparHTML(usuario.email ?? '—');
            const perfil = escaparHTML(usuario.role?.nome ?? 'Não definido');
            const setor = escaparHTML(usuario.setor?.nome ?? 'Não vinculado');

            const ativo = String(usuario.status).toLowerCase() === 'ativo';

            const badgeStatus = ativo
                ? '<span class="badge bg-success-subtle text-success">Ativo</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Inativo</span>';

            return `
                <tr>
                    <td class="fw-semibold text-dark">${nome}</td>
                    <td class="text-secondary">${email}</td>
                    <td class="text-secondary">${perfil}</td>
                    <td class="text-secondary">${setor}</td>
                    <td>${badgeStatus}</td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-acao="editar"
                                data-id="${usuario.id}">
                                <i class="bi bi-pencil"></i>
                                Editar
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm ${ativo ? 'btn-outline-danger' : 'btn-outline-success'}"
                                data-acao="status"
                                data-id="${usuario.id}"
                                data-status="${ativo ? 'inativo' : 'ativo'}">
                                <i class="bi ${ativo ? 'bi-person-x' : 'bi-person-check'}"></i>
                                ${ativo ? 'Desativar' : 'Ativar'}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Ações dos botões da tabela
    tabelaUsuarios.addEventListener('click', async evento => {
        const botao = evento.target.closest('button[data-acao]');

        if (!botao) {
            return;
        }

        const id = Number(botao.dataset.id);
        const acao = botao.dataset.acao;

        const usuario = usuarios.find(item => item.id === id);

        if (!usuario) {
            return;
        }

        if (acao === 'editar') {
            abrirModalEdicao(usuario);
        }

        if (acao === 'status') {
            await alterarStatus(usuario, botao.dataset.status);
        }
    });

    function abrirModalEdicao(usuario) {
        limparErro();

        campoId.value = usuario.id;
        campoNome.value = usuario.name ?? '';
        campoEmail.value = usuario.email ?? '';
        campoSiape.value = usuario.siape ?? '';

        carregarPerfis(usuario);
        carregarSetores(usuario);

        modalEditar.show();
    }

    function carregarPerfis(usuario) {
        const perfis = new Map();

        // Reúne os perfis existentes nos usuários retornados pela API
        usuarios.forEach(item => {
            if (item.role) {
                perfis.set(item.role.id, item.role.nome);
            }
        });

        // Garante que o perfil atual apareça no seletor
        if (usuario.role) {
            perfis.set(usuario.role.id, usuario.role.nome);
        }

        campoPerfil.innerHTML = `
            <option value="">Selecione um perfil</option>
            ${Array.from(perfis.entries()).map(([id, nome]) => `
                <option value="${id}">${escaparHTML(nome)}</option>
            `).join('')}
        `;

        campoPerfil.value = usuario.role_id ?? '';
    }

    function carregarSetores(usuario) {
        campoSetor.innerHTML = `
            <option value="">Não vinculado</option>
        `;

        setores.forEach(setor => {
            const inativo = String(setor.status).toLowerCase() === 'inativo';

            const option = document.createElement('option');
            option.value = setor.id;
            option.textContent = `${setor.nome}${inativo ? ' (Inativo)' : ''}`;

            // Não permite atribuir setor inativo, exceto se já for o setor atual
            if (inativo && Number(setor.id) !== Number(usuario.setor_id)) {
                option.disabled = true;
            }

            campoSetor.appendChild(option);
        });

        // Caso o setor atual não esteja na lista retornada
        if (
            usuario.setor &&
            !setores.some(setor => Number(setor.id) === Number(usuario.setor_id))
        ) {
            const option = document.createElement('option');
            option.value = usuario.setor_id;
            option.textContent = `${usuario.setor.nome} (Atual)`;
            campoSetor.appendChild(option);
        }

        campoSetor.value = usuario.setor_id ?? '';
    }

    // Salvar alterações do usuário
    formEditar.addEventListener('submit', async evento => {
        evento.preventDefault();
        limparErro();

        const id = campoId.value;

        const dados = {
            name: campoNome.value.trim(),
            email: campoEmail.value.trim(),
            siape: campoSiape.value.trim() || null,
            role_id: Number(campoPerfil.value),
            setor_id: campoSetor.value
                ? Number(campoSetor.value)
                : null
        };

        botaoSalvar.disabled = true;
        botaoSalvar.textContent = 'Salvando...';

        try {
            await SIGEPAPI.requisicao(`/admin/usuarios/${id}`, {
                method: 'PUT',
                body: dados
            });

            modalEditar.hide();
            await carregarUsuarios();

        } catch (erro) {
            console.error('Erro ao atualizar usuário:', erro);
            mostrarErro(erro);
        } finally {
            botaoSalvar.disabled = false;
            botaoSalvar.textContent = 'Salvar alterações';
        }
    });

    // Ativar ou desativar usuário
    async function alterarStatus(usuario, novoStatus) {
        const acao = novoStatus === 'ativo' ? 'ativar' : 'desativar';

        const confirmado = confirm(
            `Deseja realmente ${acao} o usuário "${usuario.name}"?`
        );

        if (!confirmado) {
            return;
        }

        try {
            await SIGEPAPI.requisicao(
                `/admin/usuarios/${usuario.id}/status`,
                {
                    method: 'PATCH',
                    body: {
                        status: novoStatus
                    }
                }
            );

            await carregarUsuarios();

        } catch (erro) {
            console.error('Erro ao alterar status:', erro);
            alert(obterMensagemErro(erro));
        }
    }

    function mostrarErro(erro) {
        const dados = erro.dados;

        if (dados?.errors) {
            const mensagens = Object.values(dados.errors).flat();
            mensagemErro.textContent = mensagens.join(' ');
        } else {
            mensagemErro.textContent =
                dados?.message || erro.message || 'Erro ao salvar usuário.';
        }

        mensagemErro.classList.remove('d-none');
    }

    function limparErro() {
        mensagemErro.textContent = '';
        mensagemErro.classList.add('d-none');
    }

    function obterMensagemErro(erro) {
        const dados = erro.dados;

        if (dados?.errors) {
            return Object.values(dados.errors).flat().join(' ');
        }

        return dados?.message || erro.message || 'Ocorreu um erro.';
    }

    function escaparHTML(valor) {
        return String(valor).replace(/[&<>"']/g, caractere => {
            const entidades = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return entidades[caractere];
        });
    }
});
