(function () {
    'use strict';

    let categoriasExibidas = [];
    let paginaAtual = 1;
    let temporizadorBusca = null;

    const elementos = {};

    document.addEventListener('DOMContentLoaded', inicializar);

    async function inicializar() {
        elementos.lista = document.getElementById('listaCategorias');
        elementos.listaMobile = document.getElementById('listaCategoriasMobile');
        elementos.busca = document.getElementById('buscaCategoria');
        elementos.filtroStatus = document.getElementById('filtroStatus');
        elementos.paginacao = document.getElementById('paginacaoCategorias');
        elementos.resumo = document.getElementById('resumoCategorias');
        elementos.mensagem = document.getElementById('mensagemCategorias');

        if (!window.SIGEPAPI) {
            mostrarMensagem(
                'Não foi possível inicializar a comunicação com a API.',
                'danger'
            );
            return;
        }

        configurarEventos();

        await carregarCategorias();
    }

    function configurarEventos() {
        // Pesquisa de categorias
        elementos.busca.addEventListener('input', () => {
            clearTimeout(temporizadorBusca);

            temporizadorBusca = setTimeout(() => {
                paginaAtual = 1;
                carregarCategorias();
            }, 400);
        });

        // Filtro por situação
        elementos.filtroStatus.addEventListener('change', () => {
            paginaAtual = 1;
            carregarCategorias();
        });

        // Paginação desktop
        elementos.paginacao.addEventListener('click', evento => {
            const botao = evento.target.closest('[data-pagina]');

            if (!botao || botao.disabled) return;

            paginaAtual = Number(botao.dataset.pagina);
            carregarCategorias();
        });

        // Ações de editar e inativar
        async function tratarAcaoCategoria(evento) {
            const botao = evento.target.closest('[data-acao]');

            if (!botao) return;

            const categoriaId = botao.dataset.id;
            const acao = botao.dataset.acao;

            if (acao === 'editar-categoria') {
                await abrirEdicaoCategoria(categoriaId);
                return;
            }

            if (acao === 'inativar-categoria') {
                const confirmar = confirm(
                    'Tem certeza que deseja inativar esta categoria?'
                );

                if (!confirmar) return;

                try {
                    await SIGEPAPI.requisicao(
                        `/categorias/${categoriaId}`,
                        { method: 'DELETE' }
                    );

                    await carregarCategorias();

                    mostrarMensagem(
                        'Categoria inativada com sucesso!',
                        'success'
                    );
                } catch (erro) {
                    console.error('Erro ao inativar categoria:', erro);

                    mostrarMensagem(
                        erro.dados?.message ||
                        'Não foi possível inativar a categoria.',
                        'danger'
                    );
                }
            }
        }

        // Eventos para desktop e mobile
        elementos.lista.addEventListener(
            'click',
            tratarAcaoCategoria
        );

        elementos.listaMobile.addEventListener(
            'click',
            tratarAcaoCategoria
        );

        // Formulário de cadastro e edição
        document.getElementById('formCategoria')
            .addEventListener('submit', prepararCadastroCategoria);

        // Botão de nova categoria
        document.getElementById('btnNovaCategoria')
            .addEventListener('click', abrirModalNovaCategoria);
    }

    function abrirModalNovaCategoria() {
        const formulario = document.getElementById('formCategoria');
        const titulo = document.getElementById('tituloModalCategoria');
        const grupoStatus = document.getElementById('grupoStatusCategoria');
        const mensagemFormulario = document.getElementById('mensagemFormularioCategoria');

        // Limpa os campos
        formulario.reset();

        // Limpa o identificador de edição
        document.getElementById('categoriaId').value = '';

        // Configura o modal para novo cadastro
        titulo.textContent = 'Nova Categoria';
        grupoStatus.classList.add('d-none');

        // Limpa mensagens anteriores
        mensagemFormulario.innerHTML = '';

        // Abre o modal
        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalCategoria')
        );

        modal.show();
    }

    async function abrirEdicaoCategoria(id) {
        const mensagem = document.getElementById('mensagemFormularioCategoria');
        const titulo = document.getElementById('tituloModalCategoria');
        const grupoStatus = document.getElementById('grupoStatusCategoria');

        mensagem.innerHTML = '';

        try {
            const resposta = await SIGEPAPI.requisicao(`/categorias/${id}`);

            // A resposta pode vir diretamente ou dentro de "data"
            const categoria = resposta.data || resposta;

            // Preenche os campos do formulário
            document.getElementById('categoriaId').value = categoria.id;
            document.getElementById('nomeCategoria').value = categoria.nome || '';
            document.getElementById('descricaoCategoria').value = categoria.descricao || '';
            document.getElementById('tipoCategoria').value = categoria.tipo || '';
            document.getElementById('statusCategoria').value = categoria.status || 'ativo';

            // Configura o modal para edição
            titulo.textContent = 'Editar Categoria';
            grupoStatus.classList.remove('d-none');

            // Abre o modal
            const modal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalCategoria')
            );

            modal.show();

        } catch (erro) {
            console.error('Erro ao buscar categoria:', erro);

            const mensagemErro = erro.dados?.message ||
                erro.message ||
                'Não foi possível carregar os dados da categoria.';

            mensagem.innerHTML = `
                <div class="alert alert-danger small" role="alert">
                    ${escaparHTML(mensagemErro)}
                </div>`;
        }
    }

    async function prepararCadastroCategoria(evento) {
        evento.preventDefault();

        const formulario = evento.currentTarget;
        const mensagem = document.getElementById('mensagemFormularioCategoria');
        const botaoSalvar = document.getElementById('btnSalvarCategoria');

        mensagem.innerHTML = '';

        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        const dadosCategoria = {
            nome: document.getElementById('nomeCategoria').value.trim(),
            descricao: document.getElementById('descricaoCategoria').value.trim() || null,
            tipo: document.getElementById('tipoCategoria').value,
            status: document.getElementById('statusCategoria').value
        };

        if (!dadosCategoria.nome || !dadosCategoria.tipo) {
            mensagem.innerHTML = `
                <div class="alert alert-warning small">
                    Preencha todos os campos obrigatórios.
                </div>`;
            return;
        }

        const textoOriginal = botaoSalvar.innerHTML;

        try {
            botaoSalvar.disabled = true;
            botaoSalvar.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Salvando...`;

            const categoriaId = document.getElementById('categoriaId').value;
            const estavaEditando = Boolean(categoriaId);

            await SIGEPAPI.requisicao(
                categoriaId ? `/categorias/${categoriaId}` : '/categorias',
                {
                    method: categoriaId ? 'PUT' : 'POST',
                    body: dadosCategoria
                }
            );

            const modal = bootstrap.Modal.getInstance(
                document.getElementById('modalCategoria')
            );

            if (modal) modal.hide();

            formulario.reset();
            paginaAtual = 1;

            elementos.busca.value = '';
            elementos.filtroStatus.value = '';

            await carregarCategorias();

            mostrarMensagem(
                estavaEditando
                    ? 'Categoria atualizada com sucesso!'
                    : 'Categoria cadastrada com sucesso!',
                'success'
            );

        } catch (erro) {
            console.error('Erro ao cadastrar categoria:', erro);

            let mensagemErro = erro.dados?.message || erro.message ||
                'Não foi possível cadastrar a categoria.';

            if (erro.status === 422 && erro.dados?.errors) {
                mensagemErro = Object.values(erro.dados.errors)
                    .flat()
                    .join('<br>');
            } else if (erro.status === 403) {
                mensagemErro = 'Você não tem permissão para cadastrar categorias.';
            } else if (erro.status === 401) {
                mensagemErro = 'Sua sessão expirou. Entre novamente no sistema.';
            }

            mensagem.innerHTML = `
                <div class="alert alert-danger small" role="alert">
                    ${escaparHTML(mensagemErro).replace(/&lt;br&gt;/g, '<br>')}
                </div>`;

        } finally {
            botaoSalvar.disabled = false;
            botaoSalvar.innerHTML = textoOriginal;
        }
    }



    async function carregarCategorias() {
        mostrarCarregamento();

        try {
            const parametros = new URLSearchParams({
                page: paginaAtual
            });

            const busca = elementos.busca.value.trim();
            const status = elementos.filtroStatus.value;

            if (busca) parametros.set('busca', busca);
            if (status) parametros.set('status', status);

            const resposta = await SIGEPAPI.requisicao(
                `/categorias?${parametros.toString()}`
            );

            categoriasExibidas = resposta.data || [];

            paginaAtual = resposta.meta?.current_page || 1;

            renderizarTabela();
            renderizarCardsMobile();
            renderizarPaginacao(resposta.meta);
            atualizarResumo(resposta.meta);

        } catch (erro) {
            console.error('Erro ao carregar categorias:', erro);

            elementos.lista.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        Não foi possível carregar as categorias.
                    </td>
                </tr>`;

            elementos.listaMobile.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center text-danger py-4">
                        Não foi possível carregar as categorias.
                    </div>
                </div>`;

            mostrarMensagem(interpretarErro(erro), 'danger');
        }
    }

    function renderizarTabela() {
        if (categoriasExibidas.length === 0) {
            elementos.lista.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Nenhuma categoria encontrada.
                    </td>
                </tr>`;
            return;
        }

        elementos.lista.innerHTML = categoriasExibidas.map(categoria => {
            const tipo = categoria.tipo === 'patrimonio'
                ? 'Patrimônio'
                : 'Consumo';

            const situacao = categoria.status === 'ativo'
                ? '<span class="badge bg-success-subtle text-success">Ativa</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Inativa</span>';

            return `
                <tr>
                    <td class="px-4 text-muted fw-semibold">
                        ${categoria.id}
                    </td>
                    <td class="px-3">
                        <strong>${escaparHTML(categoria.nome)}</strong>
                        <small class="d-block text-muted">
                            ${escaparHTML(categoria.descricao || 'Sem descrição')}
                        </small>
                    </td>
                    <td class="px-3">${tipo}</td>
                    <td class="px-3 text-center">
                        ${Number(categoria.materiais_count || 0)}
                    </td>
                    <td class="px-3 text-center">
                        ${Number(categoria.bens_patrimoniais_count || 0)}
                    </td>
                    <td class="px-3 text-center">${situacao}</td>
                    <td class="px-4 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-acao="editar-categoria"
                                data-id="${categoria.id}"
                                title="Editar categoria"
                                aria-label="Editar categoria ${escaparHTML(categoria.nome)}">
                                <i class="bi bi-pencil"></i>
                            </button>

                            ${categoria.status === 'ativo' ? `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-acao="inativar-categoria"
                                    data-id="${categoria.id}"
                                    title="Inativar categoria"
                                    aria-label="Inativar categoria ${escaparHTML(categoria.nome)}">
                                    <i class="bi bi-ban"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    function renderizarCardsMobile() {
        if (categoriasExibidas.length === 0) {
            elementos.listaMobile.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Nenhuma categoria encontrada.
                    </div>
                </div>`;
            return;
        }

        elementos.listaMobile.innerHTML = categoriasExibidas.map(categoria => {
            const tipo = categoria.tipo === 'patrimonio'
                ? 'Patrimônio'
                : 'Consumo';

            const situacao = categoria.status === 'ativo'
                ? '<span class="badge bg-success-subtle text-success">Ativa</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Inativa</span>';

            return `
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <small class="text-muted">ID: ${categoria.id}</small>
                                <h6 class="fw-bold text-dark mb-1">
                                    ${escaparHTML(categoria.nome)}
                                </h6>
                            </div>
                            ${situacao}
                        </div>

                        <p class="text-muted small mb-3">
                            ${escaparHTML(categoria.descricao || 'Sem descrição')}
                        </p>

                        <div class="row g-2 border-top pt-3">
                            <div class="col-4">
                                <small class="text-muted d-block">Tipo</small>
                                <strong>${tipo}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Materiais</small>
                                <strong>${Number(categoria.materiais_count || 0)}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Bens</small>
                                <strong>${Number(categoria.bens_patrimoniais_count || 0)}</strong>
                            </div>
                        </div>
                                                <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-acao="editar-categoria"
                                                        data-id="${categoria.id}"
                                                        title="Editar categoria">
                                                        <i class="bi bi-pencil me-1"></i>
                                                        Editar
                                                    </button>

                                                    ${categoria.status === 'ativo' ? `
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            data-acao="inativar-categoria"
                                                            data-id="${categoria.id}"
                                                            title="Inativar categoria">
                                                        <i class="bi bi-ban me-1"></i>
                                                        Inativar
                                                        </button>
                                                    ` : ''}
                                                </div>
                    </div>
                </div>`;
        }).join('');
    }

    
    function renderizarPaginacao(meta) {
        const atual = meta?.current_page || 1;
        const ultima = meta?.last_page || 1;

        // Paginação desktop
        elementos.paginacao.innerHTML = `
            <li class="page-item ${atual <= 1 ? 'disabled' : ''}">
                <button class="page-link" data-pagina="${atual - 1}"
                    ${atual <= 1 ? 'disabled' : ''}>
                    Anterior
                </button>
            </li>
            <li class="page-item active">
                <span class="page-link">${atual} de ${ultima}</span>
            </li>
            <li class="page-item ${atual >= ultima ? 'disabled' : ''}">
                <button class="page-link" data-pagina="${atual + 1}"
                    ${atual >= ultima ? 'disabled' : ''}>
                    Próximo
                </button>
            </li>`;

        // Paginação mobile
        const botaoAnterior = document.getElementById('btnPaginaAnteriorMobile');
        const botaoProximo = document.getElementById('btnPaginaProximaMobile');
        const resumoMobile = document.getElementById('resumoPaginacaoMobile');

        if (botaoAnterior && botaoProximo && resumoMobile) {
            botaoAnterior.disabled = atual <= 1;
            botaoProximo.disabled = atual >= ultima;

            resumoMobile.textContent = `${atual} de ${ultima}`;

            botaoAnterior.onclick = () => {
                if (paginaAtual > 1) {
                    paginaAtual--;
                    carregarCategorias();
                }
            };

            botaoProximo.onclick = () => {
                if (paginaAtual < ultima) {
                    paginaAtual++;
                    carregarCategorias();
                }
            };
        }
    }


    function atualizarResumo(meta) {
        const total = meta?.total || 0;
        const porPagina = meta?.per_page || 10;
        const inicio = total === 0 ? 0 : ((paginaAtual - 1) * porPagina) + 1;
        const fim = Math.min(paginaAtual * porPagina, total);

        elementos.resumo.textContent =
            `Exibindo ${inicio} a ${fim} de ${total} categorias`;
    }

    function mostrarCarregamento() {
        elementos.lista.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm text-success me-2"></span>
                    Carregando categorias...
                </td>
            </tr>`;

        elementos.listaMobile.innerHTML = `
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center text-muted py-4">
                    Carregando categorias...
                </div>
            </div>`;
    }

    function mostrarMensagem(mensagem, tipo) {
        elementos.mensagem.innerHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show small"
                role="alert">
                ${escaparHTML(mensagem)}
                <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>`;
    }

    function interpretarErro(erro) {
        if (!erro.status) {
            return 'Não foi possível conectar ao servidor. Verifique sua conexão.';
        }

        if (erro.status === 401) return 'Sua sessão expirou. Entre novamente no sistema.';
        if (erro.status === 403) return 'Você não tem permissão para consultar as categorias.';
        if (erro.status >= 500) return 'Ocorreu um erro interno no servidor.';

        return erro.dados?.message || erro.message ||
            'Não foi possível carregar as categorias.';
    }

    function escaparHTML(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, caractere => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[caractere]);
    }
    
})();
