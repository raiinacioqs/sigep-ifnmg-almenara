(function () {
    'use strict';

    let materiaisExibidos = [];
    let categorias = [];
    let paginaAtual = 1;
    let ultimaPagina = 1;
    let temporizadorBusca = null;
    let modalMaterial = null;

    const elementos = {};

    document.addEventListener('DOMContentLoaded', inicializar);

    async function inicializar() {
        elementos.lista = document.getElementById('listaMateriais');
        elementos.busca = document.getElementById('buscaMaterial');
        elementos.filtroCategoria = document.getElementById('filtroCategoria');
        elementos.filtroStatus = document.getElementById('filtroStatus');
        elementos.paginacao = document.getElementById('paginacaoMateriais');
        elementos.resumo = document.getElementById('resumoMateriais');
        elementos.mensagem = document.getElementById('mensagemMateriais');

        elementos.form = document.getElementById('formMaterial');
        elementos.modal = document.getElementById('modalMaterial');
        elementos.btnNovo = document.getElementById('btnNovoMaterial');
        elementos.btnSalvar = document.getElementById('btnSalvarMaterial');

        elementos.total = document.getElementById('totalMateriais');
        elementos.ativos = document.getElementById('materiaisAtivos');
        elementos.inativos = document.getElementById('materiaisInativos');

        elementos.id = document.getElementById('materialId');
        elementos.nome = document.getElementById('nomeMaterial');
        elementos.descricao = document.getElementById('descricaoMaterial');
        elementos.categoria = document.getElementById('categoriaMaterial');
        elementos.unidade = document.getElementById('unidadeMaterial');
        elementos.quantidadeMinima = document.getElementById('quantidadeMinima');
        elementos.status = document.getElementById('statusMaterial');
        elementos.grupoStatus = document.getElementById('grupoStatusMaterial');
        elementos.mensagemFormulario = document.getElementById('mensagemFormularioMaterial');

        if (!window.SIGEPAPI) {
            mostrarMensagem('Não foi possível inicializar a comunicação com a API.', 'danger');
            return;
        }

        modalMaterial = bootstrap.Modal.getOrCreateInstance(elementos.modal);

        configurarEventos();

        await carregarDadosIniciais();
    }

    function configurarEventos() {
        elementos.btnNovo.addEventListener('click', abrirNovoMaterial);

        elementos.form.addEventListener('submit', salvarMaterial);

        elementos.busca.addEventListener('input', () => {
            clearTimeout(temporizadorBusca);

            temporizadorBusca = setTimeout(() => {
                paginaAtual = 1;
                carregarMateriais();
            }, 400);
        });

        elementos.filtroCategoria.addEventListener('change', () => {
            paginaAtual = 1;
            carregarMateriais();
        });

        elementos.filtroStatus.addEventListener('change', () => {
            paginaAtual = 1;
            carregarMateriais();
        });

        elementos.lista.addEventListener('click', tratarAcaoMaterial);

        document.getElementById('listaMateriaisMobile')
            .addEventListener('click', tratarAcaoMaterial);

        elementos.paginacao.addEventListener('click', (evento) => {
            const botao = evento.target.closest('[data-pagina]');

            if (!botao || botao.disabled) return;

            paginaAtual = Number(botao.dataset.pagina);
            carregarMateriais();
        });
    }

    async function carregarDadosIniciais() {
        mostrarCarregamento();

        try {
            await carregarCategorias();
            await Promise.all([
                carregarMateriais(),
                atualizarIndicadores()
            ]);
        } catch (erro) {
            console.error('Erro ao inicializar materiais:', erro);
            mostrarMensagem('Não foi possível carregar os dados. Verifique a conexão com o servidor.', 'danger');
        }
    }

    async function obterTodasPaginas(endpoint) {
        let pagina = 1;
        let registros = [];
        let totalPaginas = 1;

        do {
            const separador = endpoint.includes('?') ? '&' : '?';
            const resposta = await SIGEPAPI.requisicao(
                `${endpoint}${separador}page=${pagina}`
            );

            registros = registros.concat(resposta.data || []);
            totalPaginas = resposta.meta?.last_page || 1;
            pagina++;
        } while (pagina <= totalPaginas);

        return registros;
    }

    async function carregarCategorias() {
        const dados = await obterTodasPaginas('/categorias?status=ativo');

        categorias = dados.filter(categoria =>
            categoria.tipo === 'consumo' &&
            categoria.status === 'ativo'
        );

        const opcaoInicialFiltro = '<option value="">Todas as categorias</option>';
        const opcaoInicialFormulario = '<option value="">Selecione uma categoria</option>';

        elementos.filtroCategoria.innerHTML =
            opcaoInicialFiltro +
            categorias.map(categoria =>
                `<option value="${categoria.id}">${escaparHTML(categoria.nome)}</option>`
            ).join('');

        elementos.categoria.innerHTML =
            opcaoInicialFormulario +
            categorias.map(categoria =>
                `<option value="${categoria.id}">${escaparHTML(categoria.nome)}</option>`
            ).join('');
    }

    async function carregarMateriais() {
        mostrarCarregamento();

        try {
            const parametros = new URLSearchParams({
                page: paginaAtual
            });

            const busca = elementos.busca.value.trim();
            const categoriaId = elementos.filtroCategoria.value;
            const status = elementos.filtroStatus.value;

            if (busca) parametros.set('busca', busca);
            if (categoriaId) parametros.set('categoria_id', categoriaId);
            if (status) parametros.set('status', status);

            const resposta = await SIGEPAPI.requisicao(
                `/materiais?${parametros.toString()}`
            );

            materiaisExibidos = resposta.data || [];
            paginaAtual = resposta.meta?.current_page || 1;
            ultimaPagina = resposta.meta?.last_page || 1;

            renderizarTabela();
            renderizarPaginacao(resposta.meta);
            atualizarResumo(resposta.meta);
        } catch (erro) {
            console.error('Erro ao carregar materiais:', erro);

            elementos.lista.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        Não foi possível carregar os materiais.
                    </td>
                </tr>`;

            mostrarMensagem(interpretarErro(erro), 'danger');
        }
    }

    async function atualizarIndicadores() {
        try {
            const todos = await obterTodasPaginas('/materiais');

            elementos.total.textContent = todos.length;
            elementos.ativos.textContent =
                todos.filter(item => item.status === 'ativo').length;
            elementos.inativos.textContent =
                todos.filter(item => item.status === 'inativo').length;
        } catch (erro) {
            console.error('Erro ao atualizar indicadores:', erro);
        }
    }


    function renderizarTabela() {
        const listaMobile = document.getElementById('listaMateriaisMobile');

        if (materiaisExibidos.length === 0) {
            elementos.lista.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Nenhum material encontrado.
                    </td>
                </tr>`;

            listaMobile.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Nenhum material encontrado.
                    </div>
                </div>`;

            return;
        }

        const linhasTabela = [];
        const cartoesMobile = [];

        materiaisExibidos.forEach(material => {
            const estoque = Number(material.estoque?.quantidade_atual || 0);
            const minimo = Number(material.quantidade_minima || 0);
            const ativo = material.status === 'ativo';

            const situacao = ativo
                ? '<span class="badge bg-success-subtle text-success">Ativo</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Inativo</span>';

            const alertaEstoque = ativo && estoque <= minimo
                ? '<i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Estoque no mínimo ou abaixo dele"></i>'
                : '';

            const acaoStatus = ativo
                ? `<button class="btn btn-sm btn-outline-danger"
                        data-acao="inativar" data-id="${material.id}"
                        title="Inativar material">
                        <i class="bi bi-pause-circle"></i>
                </button>`
                : `<button class="btn btn-sm btn-outline-success"
                        data-acao="ativar" data-id="${material.id}"
                        title="Ativar material">
                        <i class="bi bi-check-circle"></i>
                </button>`;

            // Tabela para desktop
            linhasTabela.push(`
                <tr>
                    <td class="px-3">
                        <strong>${escaparHTML(material.nome)}</strong>
                        <small class="d-block text-muted">
                            ${escaparHTML(material.descricao || 'Sem descrição')}
                        </small>
                    </td>
                    <td class="px-3">${escaparHTML(material.categoria?.nome || 'Não informada')}</td>
                    <td class="px-3">${escaparHTML(material.unidade_medida)}</td>
                    <td class="px-3 text-center">
                        ${formatarQuantidade(estoque)} ${alertaEstoque}
                    </td>
                    <td class="px-3 text-center">${formatarQuantidade(minimo)}</td>
                    <td class="px-3 text-center">${situacao}</td>
                    <td class="px-3 text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <button class="btn btn-sm btn-outline-primary"
                                data-acao="editar" data-id="${material.id}"
                                title="Editar material">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${acaoStatus}
                        </div>
                    </td>
                </tr>
            `);

            // Cartão para dispositivos móveis
            cartoesMobile.push(`
                <div class="card border-0 shadow-sm rounded-4 materiais-mobile-card mb-3">
                    <div class="card-body p-3">

                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div class="overflow-hidden">
                                <h6 class="fw-bold text-dark mb-1">
                                    ${escaparHTML(material.nome)}
                                </h6>
                                <small class="text-muted">
                                    ${escaparHTML(material.descricao || 'Sem descrição')}
                                </small>
                            </div>
                            ${situacao}
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Categoria</small>
                            <span class="fw-semibold">
                                ${escaparHTML(material.categoria?.nome || 'Não informada')}
                            </span>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="materiais-mobile-info">
                                    <small class="text-muted d-block">Unidade</small>
                                    <strong>${escaparHTML(material.unidade_medida)}</strong>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="materiais-mobile-info">
                                    <small class="text-muted d-block">Estoque</small>
                                    <strong>
                                        ${formatarQuantidade(estoque)} ${alertaEstoque}
                                    </strong>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="materiais-mobile-info">
                                    <small class="text-muted d-block">Mínimo</small>
                                    <strong>${formatarQuantidade(minimo)}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <button class="btn btn-sm btn-outline-primary"
                                data-acao="editar" data-id="${material.id}">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>

                            ${acaoStatus}
                        </div>

                    </div>
                </div>
            `);
        });

        elementos.lista.innerHTML = linhasTabela.join('');
        listaMobile.innerHTML = cartoesMobile.join('');
    }


    function renderizarPaginacao(meta) {
        const atual = meta?.current_page || 1;
        const ultima = meta?.last_page || 1;

        elementos.paginacao.innerHTML = `
            <li class="page-item ${atual <= 1 ? 'disabled' : ''}">
                <button class="page-link" data-pagina="${atual - 1}"
                    ${atual <= 1 ? 'disabled' : ''} aria-label="Página anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </li>
            <li class="page-item active">
                <span class="page-link">${atual} de ${ultima}</span>
            </li>
            <li class="page-item ${atual >= ultima ? 'disabled' : ''}">
                <button class="page-link" data-pagina="${atual + 1}"
                    ${atual >= ultima ? 'disabled' : ''} aria-label="Próxima página">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </li>`;
    }

    function atualizarResumo(meta) {
        const total = meta?.total || 0;
        const porPagina = meta?.per_page || 10;
        const inicio = total === 0 ? 0 : ((paginaAtual - 1) * porPagina) + 1;
        const fim = Math.min(paginaAtual * porPagina, total);

        elementos.resumo.textContent =
            `Exibindo ${inicio} a ${fim} de ${total} materiais`;
    }

    function abrirNovoMaterial() {
        elementos.form.reset();
        elementos.id.value = '';
        elementos.quantidadeMinima.value = '0';
        elementos.status.value = 'ativo';
        elementos.grupoStatus.classList.add('d-none');
        elementos.mensagemFormulario.innerHTML = '';
        document.getElementById('tituloModalMaterial').textContent = 'Novo Material';
        modalMaterial.show();
    }

    function abrirEdicao(material) {
        elementos.form.reset();

        elementos.id.value = material.id;
        elementos.nome.value = material.nome;
        elementos.descricao.value = material.descricao || '';
        elementos.categoria.value = material.categoria_id;
        elementos.unidade.value = material.unidade_medida;
        elementos.quantidadeMinima.value = Number(material.quantidade_minima);
        elementos.status.value = material.status;

        elementos.grupoStatus.classList.remove('d-none');
        elementos.mensagemFormulario.innerHTML = '';

        document.getElementById('tituloModalMaterial').textContent = 'Editar Material';

        modalMaterial.show();
    }

    async function salvarMaterial(evento) {
        evento.preventDefault();

        if (!elementos.form.reportValidity()) return;

        const id = elementos.id.value;

        const dados = {
            nome: elementos.nome.value.trim(),
            descricao: elementos.descricao.value.trim() || null,
            categoria_id: Number(elementos.categoria.value),
            unidade_medida: elementos.unidade.value,
            quantidade_minima: Number(elementos.quantidadeMinima.value),
            status: elementos.status.value
        };

        const metodo = id ? 'PUT' : 'POST';
        const endpoint = id ? `/materiais/${id}` : '/materiais';

        elementos.btnSalvar.disabled = true;
        elementos.btnSalvar.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        try {
            await SIGEPAPI.requisicao(endpoint, {
                method: metodo,
                body: dados
            });

            modalMaterial.hide();

            mostrarMensagem(
                id ? 'Material atualizado com sucesso.' : 'Material cadastrado com sucesso.',
                'success'
            );

            await Promise.all([
                carregarMateriais(),
                atualizarIndicadores()
            ]);
        } catch (erro) {
            console.error('Erro ao salvar material:', erro);
            elementos.mensagemFormulario.innerHTML = `
                <div class="alert alert-danger small mb-0">
                    ${escaparHTML(interpretarErro(erro))}
                </div>`;
        } finally {
            elementos.btnSalvar.disabled = false;
            elementos.btnSalvar.innerHTML =
                '<i class="bi bi-check-lg me-1"></i>Salvar Material';
        }
    }

    async function tratarAcaoMaterial(evento) {
        const botao = evento.target.closest('[data-acao]');
        if (!botao) return;

        const material = materiaisExibidos.find(
            item => String(item.id) === botao.dataset.id
        );

        if (!material) return;

        const acao = botao.dataset.acao;

        if (acao === 'editar') {
            abrirEdicao(material);
            return;
        }

        if (acao === 'inativar') {
            if (!confirm(`Deseja realmente inativar o material "${material.nome}"?`)) {
                return;
            }

            await alterarStatus(material, 'inativo');
            return;
        }

        if (acao === 'ativar') {
            if (!confirm(`Deseja reativar o material "${material.nome}"?`)) {
                return;
            }

            await alterarStatus(material, 'ativo');
        }
    }

    async function alterarStatus(material, status) {
        try {
            if (status === 'inativo') {
                await SIGEPAPI.requisicao(`/materiais/${material.id}`, {
                    method: 'DELETE'
                });
            } else {
                await SIGEPAPI.requisicao(`/materiais/${material.id}`, {
                    method: 'PUT',
                    body: {
                        nome: material.nome,
                        descricao: material.descricao || null,
                        categoria_id: material.categoria_id,
                        unidade_medida: material.unidade_medida,
                        quantidade_minima: Number(material.quantidade_minima),
                        status: 'ativo'
                    }
                });
            }

            mostrarMensagem(
                status === 'ativo'
                    ? 'Material reativado com sucesso.'
                    : 'Material inativado com sucesso.',
                'success'
            );

            await Promise.all([
                carregarMateriais(),
                atualizarIndicadores()
            ]);
        } catch (erro) {
            console.error('Erro ao alterar situação:', erro);
            mostrarMensagem(interpretarErro(erro), 'danger');
        }
    }

    function mostrarCarregamento() {
        elementos.lista.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm text-success me-2"></span>
                    Carregando materiais...
                </td>
            </tr>`;
    }

    function mostrarMensagem(mensagem, tipo) {
        elementos.mensagem.innerHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show small" role="alert">
                ${escaparHTML(mensagem)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>`;
    }

    function interpretarErro(erro) {
        if (!erro.status) {
            return 'Não foi possível conectar ao servidor. Verifique sua conexão.';
        }

        if (erro.status === 401) return 'Sua sessão expirou. Entre novamente no sistema.';
        if (erro.status === 403) return 'Você não tem permissão para realizar esta operação.';
        if (erro.status === 404) return 'O material solicitado não foi encontrado.';
        if (erro.status === 422) {
            const erros = erro.dados?.errors;

            if (erros) {
                return Object.values(erros).flat().join(' ');
            }

            return erro.dados?.message || 'Verifique os campos informados.';
        }

        if (erro.status >= 500) {
            return 'Ocorreu um erro interno no servidor.';
        }

        return erro.dados?.message || erro.message || 'Não foi possível concluir a operação.';
    }

    function formatarQuantidade(valor) {
        return Number(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
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
