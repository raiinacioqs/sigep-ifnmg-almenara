(function () {
    'use strict';

    let bens = [];
    let bensFiltrados = [];
    let categorias = [];
    let setores = [];
    let responsaveis = [];
    let paginaAtual = 1;
    const porPagina = 10;
    let temporizadorBusca = null;
    let modalBem = null;
    let modalDetalhesBem = null;

    const el = {};

    document.addEventListener('DOMContentLoaded', inicializar);

    async function inicializar() {
        el.lista = document.getElementById('listaBens');
        el.listaMobile = document.getElementById('listaBensMobile');
        el.busca = document.getElementById('buscaBem');
        el.filtroCategoria = document.getElementById('filtroCategoriaBem');
        el.filtroStatus = document.getElementById('filtroStatusBem');
        el.paginacao = document.getElementById('paginacaoBens');
        el.resumo = document.getElementById('resumoBens');
        el.mensagem = document.getElementById('mensagemBens');

        el.form = document.getElementById('formBem');
        el.modal = document.getElementById('modalBem');
        el.btnNovo = document.getElementById('btnNovoBem');
        el.btnSalvar = document.getElementById('btnSalvarBem');
        el.idBem = document.getElementById('idBem');
        el.textoBotaoSalvar = document.getElementById('textoBotaoSalvarBem');
        el.mensagemFormulario = document.getElementById('mensagemFormularioBem');

        el.total = document.getElementById('totalBens');
        el.ativos = document.getElementById('bensAtivos');
        el.inativos = document.getElementById('bensInativos');

        el.numeroTombo = document.getElementById('numeroTombo');
        el.descricao = document.getElementById('descricaoBem');
        el.categoria = document.getElementById('categoriaBem');
        el.setor = document.getElementById('setorBem');
        el.responsavel = document.getElementById('responsavelBem');
        el.localizacao = document.getElementById('localizacaoBem');
        el.notaFiscal = document.getElementById('notaFiscalBem');
        el.dataAquisicao = document.getElementById('dataAquisicaoBem');
        el.valorAquisicao = document.getElementById('valorAquisicaoBem');
        el.estadoConservacao = document.getElementById('estadoConservacaoBem');

        if (!window.SIGEPAPI) {
            mostrarMensagem(
                'Não foi possível inicializar a comunicação com a API.',
                'danger'
            );
            return;
        }

        modalBem = bootstrap.Modal.getOrCreateInstance(el.modal);

        el.modalDetalhes = document.getElementById('modalDetalhesBem');

        modalDetalhesBem = bootstrap.Modal.getOrCreateInstance(
            el.modalDetalhes
        );

        configurarEventos();

        await carregarDadosIniciais();
    }

    function configurarEventos() {
        el.btnNovo.addEventListener('click', abrirNovoBem);

        // Clique no botão Editar da tabela desktop
        el.lista.addEventListener('click', evento => {
            const botao = evento.target.closest('[data-editar-bem]');

            if (!botao) return;

            const id = Number(botao.dataset.editarBem);
            abrirEdicaoBem(id);
        });

        el.lista.addEventListener('click', (evento) => {
            const botao = evento.target.closest('[data-detalhar-bem]');

            if (!botao) return;

            const id = Number(botao.dataset.detalharBem);
            abrirDetalhesBem(id);
        });

        el.listaMobile.addEventListener('click', (evento) => {
            const botao = evento.target.closest('[data-detalhar-bem]');

            if (!botao) return;

            const id = Number(botao.dataset.detalharBem);
            abrirDetalhesBem(id);
        });

        // Clique no botão Editar da lista mobile
        el.listaMobile.addEventListener('click', evento => {
            const botao = evento.target.closest('[data-editar-bem]');

            if (!botao) return;

            const id = Number(botao.dataset.editarBem);
            abrirEdicaoBem(id);
        });

        el.form.addEventListener('submit', salvarBem);

        el.busca.addEventListener('input', () => {
            clearTimeout(temporizadorBusca);

            temporizadorBusca = setTimeout(() => {
                paginaAtual = 1;
                aplicarFiltros();
            }, 300);
        });

        el.filtroCategoria.addEventListener('change', () => {
            paginaAtual = 1;
            aplicarFiltros();
        });

        el.filtroStatus.addEventListener('change', () => {
            paginaAtual = 1;
            aplicarFiltros();
        });

        el.paginacao.addEventListener('click', evento => {
            const botao = evento.target.closest('[data-pagina]');

            if (!botao || botao.disabled) return;

            paginaAtual = Number(botao.dataset.pagina);

            renderizar();
        });
    }

    async function carregarDadosIniciais() {
        mostrarCarregamento();

        try {
            const [
                categoriasApi,
                setoresApi,
                responsaveisApi,
                bensApi
            ] = await Promise.all([
                obterTodasPaginas('/categorias?status=ativo'),
                obterTodasPaginas('/setores?status=ativo'),
                obterTodasPaginas('/responsaveis?status=ativo'),
                obterTodasPaginas('/gestao/bens-patrimoniais')
            ]);

            categorias = categoriasApi.filter(categoria =>
                categoria.tipo === 'patrimonio' &&
                categoria.status === 'ativo'
            );

            setores = setoresApi.filter(setor =>
                setor.status === 'ativo'
            );

            responsaveis = responsaveisApi.filter(responsavel =>
                responsavel.status === 'ativo'
            );

            bens = bensApi;

            preencherSelect(
                el.filtroCategoria,
                categorias,
                'Todas as categorias'
            );

            preencherSelect(
                el.categoria,
                categorias,
                'Selecione uma categoria'
            );

            preencherSelect(
                el.setor,
                setores,
                'Selecione um setor'
            );

            preencherSelect(
                el.responsavel,
                responsaveis,
                'Sem responsável definido'
            );

            atualizarIndicadores();
            aplicarFiltros();

        } catch (erro) {
            console.error(
                'Erro ao carregar dados dos bens patrimoniais:',
                erro
            );

            el.lista.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        Não foi possível carregar os bens patrimoniais.
                    </td>
                </tr>`;

            el.listaMobile.innerHTML = `
                <div class="alert alert-danger">
                    Não foi possível carregar os bens patrimoniais.
                </div>`;

            mostrarMensagem(interpretarErro(erro), 'danger');
        }
    }

    async function obterTodasPaginas(endpoint) {
        let pagina = 1;
        let registros = [];
        let ultimaPagina = 1;

        do {
            const separador = endpoint.includes('?') ? '&' : '?';

            const resposta = await SIGEPAPI.requisicao(
                `${endpoint}${separador}page=${pagina}`
            );

            registros = registros.concat(resposta.data || []);

            ultimaPagina = resposta.meta?.last_page || 1;

            pagina++;

        } while (pagina <= ultimaPagina);

        return registros;
    }

    function preencherSelect(select, itens, textoInicial) {
        select.innerHTML = '';

        const inicial = document.createElement('option');
        inicial.value = '';
        inicial.textContent = textoInicial;

        select.appendChild(inicial);

        itens.forEach(item => {
            const option = document.createElement('option');

            option.value = item.id;

            option.textContent =
                item.nome ||
                item.nome_completo ||
                item.siape ||
                `Registro ${item.id}`;

            select.appendChild(option);
        });
    }

    function atualizarIndicadores() {
        el.total.textContent = bens.length;

        el.ativos.textContent = bens.filter(
            bem => bem.status === 'ativo'
        ).length;

        el.inativos.textContent = bens.filter(
            bem => bem.status === 'inativo'
        ).length;
    }

    function aplicarFiltros() {
        const termo = el.busca.value
            .trim()
            .toLocaleLowerCase('pt-BR');

        const categoriaId = el.filtroCategoria.value;
        const status = el.filtroStatus.value;

        bensFiltrados = bens.filter(bem => {
            const textoBusca = [
                bem.numero_tombo,
                bem.descricao,
                bem.localizacao,
                bem.categoria?.nome,
                bem.setor?.nome,
                bem.responsavel?.nome
            ]
                .filter(Boolean)
                .join(' ')
                .toLocaleLowerCase('pt-BR');

            return (
                (!termo || textoBusca.includes(termo)) &&
                (!categoriaId ||
                    String(bem.categoria_id) === categoriaId) &&
                (!status || bem.status === status)
            );
        });

        renderizar();
    }

    function renderizar() {
        const totalPaginas = Math.max(
            1,
            Math.ceil(bensFiltrados.length / porPagina)
        );

        if (paginaAtual > totalPaginas) {
            paginaAtual = totalPaginas;
        }

        const inicio = (paginaAtual - 1) * porPagina;

        const pagina = bensFiltrados.slice(
            inicio,
            inicio + porPagina
        );

        if (!pagina.length) {
            el.lista.innerHTML = `
                <tr>
                    <td colspan="6"
                        class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Nenhum bem patrimonial encontrado.
                    </td>
                </tr>`;

            el.listaMobile.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Nenhum bem patrimonial encontrado.
                    </div>
                </div>`;
        } else {
            el.lista.innerHTML = pagina.map(bem => `
                <tr>
                    <td class="px-3 fw-semibold">
                        ${escaparHTML(bem.numero_tombo || '—')}
                    </td>

                    <td class="px-3">
                        ${escaparHTML(bem.descricao || '—')}
                    </td>

                    <td class="px-3">
                        ${escaparHTML(bem.categoria?.nome || '—')}
                    </td>

                    <td class="px-3">
                        ${escaparHTML(
                            bem.setor?.sigla ||
                            bem.setor?.nome ||
                            '—'
                        )}
                    </td>

                    <td class="px-3">
                        ${escaparHTML(bem.responsavel?.nome || '—')}
                    </td>

                    <td class="px-3 text-center">
                        ${badgeStatus(bem.status)}
                    </td>

                    <td class="px-3 text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-editar-bem="${bem.id}"
                            title="Editar bem patrimonial">
                            <i class="bi bi-pencil-square"></i>
                            Editar
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary ms-1"
                            data-detalhar-bem="${bem.id}"
                            title="Visualizar detalhes">
                            <i class="bi bi-eye"></i>
                            Visualizar
                        </button>
                    </td>
                </tr>
            `).join('');

            el.listaMobile.innerHTML = pagina.map(bem => `
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between
                            align-items-start gap-2 mb-2">

                            <strong>
                                ${escaparHTML(bem.numero_tombo || '—')}
                            </strong>

                            ${badgeStatus(bem.status)}
                        </div>

                        <div class="fw-semibold mb-2">
                            ${escaparHTML(bem.descricao || '—')}
                        </div>

                        <div class="small text-muted">
                            Categoria:
                            ${escaparHTML(bem.categoria?.nome || '—')}
                        </div>

                        <div class="small text-muted">
                            Setor:
                            ${escaparHTML(
                                bem.setor?.sigla ||
                                bem.setor?.nome ||
                                '—'
                            )}
                        </div>

                        <div class="small text-muted">
                            Responsável:
                            ${escaparHTML(
                                bem.responsavel?.nome ||
                                'Não definido'
                            )}
                        </div>
                        <div class="d-grid mt-3">
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm"
                                data-editar-bem="${bem.id}">
                                <i class="bi bi-pencil-square me-1"></i>
                                Editar bem
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary ms-1"
                                data-detalhar-bem="${bem.id}"
                                title="Visualizar detalhes">
                                <i class="bi bi-eye"></i>
                                Visualizar
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        renderizarPaginacao(totalPaginas);

        const total = bensFiltrados.length;
        const de = total ? inicio + 1 : 0;
        const ate = Math.min(inicio + porPagina, total);

        el.resumo.textContent =
            `Exibindo ${de} a ${ate} de ${total} bens patrimoniais`;
    }

    function renderizarPaginacao(totalPaginas) {
        let html = `
            <li class="page-item ${paginaAtual === 1 ? 'disabled' : ''}">
                <button class="page-link"
                    data-pagina="${paginaAtual - 1}"
                    ${paginaAtual === 1 ? 'disabled' : ''}
                    aria-label="Página anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </li>`;

        for (let pagina = 1; pagina <= totalPaginas; pagina++) {
            html += `
                <li class="page-item ${
                    pagina === paginaAtual ? 'active' : ''
                }">
                    <button class="page-link"
                        data-pagina="${pagina}">
                        ${pagina}
                    </button>
                </li>`;
        }

        html += `
            <li class="page-item ${
                paginaAtual === totalPaginas ? 'disabled' : ''
            }">
                <button class="page-link"
                    data-pagina="${paginaAtual + 1}"
                    ${paginaAtual === totalPaginas ? 'disabled' : ''}
                    aria-label="Próxima página">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </li>`;

        el.paginacao.innerHTML = html;
    }

    function abrirNovoBem() {
        el.form.reset();
        el.idBem.value = '';
        el.mensagemFormulario.innerHTML = '';

        document.getElementById('tituloModalBem').textContent =
            'Novo Bem Patrimonial';

        el.textoBotaoSalvar.textContent =
            'Salvar Bem Patrimonial';

        modalBem.show();
    }

    async function abrirEdicaoBem(id) {
        el.mensagemFormulario.innerHTML = '';

        try {
            const resposta = await SIGEPAPI.requisicao(
                `/gestao/bens-patrimoniais/${id}`
            );

            const bem = resposta.data;

            el.idBem.value = bem.id;
            el.numeroTombo.value = bem.numero_tombo || '';
            el.descricao.value = bem.descricao || '';
            el.categoria.value = bem.categoria_id || '';
            el.setor.value = bem.setor_id || '';
            el.responsavel.value = bem.responsavel_id || '';
            el.localizacao.value = bem.localizacao || '';
            el.notaFiscal.value = bem.nota_fiscal || '';
            el.dataAquisicao.value = bem.data_aquisicao
                ? bem.data_aquisicao.substring(0, 10)
                : '';
            el.valorAquisicao.value = bem.valor_aquisicao ?? '';
            el.estadoConservacao.value = bem.estado_conservacao || '';

            document.getElementById('tituloModalBem').textContent =
                'Editar Bem Patrimonial';

            el.textoBotaoSalvar.textContent =
                'Atualizar Bem Patrimonial';

            modalBem.show();

        } catch (erro) {
            console.error('Erro ao carregar bem para edição:', erro);

            mostrarMensagem(
                interpretarErro(erro),
                'danger'
            );
        }
    }

    async function abrirDetalhesBem(id) {
        try {
            const resposta = await SIGEPAPI.requisicao(
                `/gestao/bens-patrimoniais/${id}`
            );

            const bem = resposta.data;

            const preencher = (campo, valor) => {
                const elemento = document.getElementById(campo);

                if (elemento) {
                    elemento.textContent = valor ?? '—';
                }
            };

            preencher('detalheTombo', bem.numero_tombo);
            preencher('detalheDescricao', bem.descricao);
            preencher('detalheCategoria', bem.categoria?.nome);
            preencher(
                'detalheSetor',
                bem.setor?.sigla || bem.setor?.nome
            );
            preencher('detalheResponsavel', bem.responsavel?.nome);
            preencher('detalheLocalizacao', bem.localizacao);
            preencher('detalheNotaFiscal', bem.nota_fiscal);

            preencher(
                'detalheDataAquisicao',
                bem.data_aquisicao
                    ? (() => {
                        const data = String(bem.data_aquisicao)
                            .split('T')[0]
                            .split(' ')[0];

                        const partes = data.split('-');

                        if (partes.length === 3) {
                            return `${partes[2]}/${partes[1]}/${partes[0]}`;
                        }

                        return '—';
                    })()
                    : '—'
            );

            preencher(
                'detalheValor',
                bem.valor_aquisicao !== null &&
                bem.valor_aquisicao !== undefined
                    ? Number(bem.valor_aquisicao).toLocaleString(
                        'pt-BR',
                        {
                            style: 'currency',
                            currency: 'BRL'
                        }
                    )
                    : '—'
            );

            preencher(
                'detalheConservacao',
                bem.estado_conservacao
            );

            preencher(
                'detalheStatus',
                bem.status
            );

            modalDetalhesBem.show();

        } catch (erro) {
            console.error('Erro ao carregar detalhes do bem:', erro);

            mostrarMensagem(
                interpretarErro(erro),
                'danger'
            );
        }
    }

    async function salvarBem(evento) {
        evento.preventDefault();

        if (!el.form.reportValidity()) return;

        const dados = {
            numero_tombo: el.numeroTombo.value.trim(),
            descricao: el.descricao.value.trim(),
            categoria_id: Number(el.categoria.value),
            setor_id: Number(el.setor.value),

            responsavel_id: el.responsavel.value
                ? Number(el.responsavel.value)
                : null,

            localizacao: el.localizacao.value.trim() || null,
            nota_fiscal: el.notaFiscal.value.trim() || null,
            data_aquisicao: el.dataAquisicao.value || null,

            valor_aquisicao: el.valorAquisicao.value === ''
                ? null
                : Number(el.valorAquisicao.value),

            estado_conservacao:
                el.estadoConservacao.value || null
        };

        el.btnSalvar.disabled = true;

        el.btnSalvar.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Salvando...
        `;

        try {
            const id = el.idBem.value;

            const endpoint = id
                ? `/gestao/bens-patrimoniais/${id}`
                : '/gestao/bens-patrimoniais';

            const metodo = id ? 'PUT' : 'POST';

            await SIGEPAPI.requisicao(endpoint, {
                method: metodo,
                body: dados
            });

            modalBem.hide();

            mostrarMensagem(
                id
                    ? 'Bem patrimonial atualizado com sucesso.'
                    : 'Bem patrimonial cadastrado com sucesso.',
                'success'
            );

            await carregarDadosIniciais();

        } catch (erro) {
            console.error(
                'Erro ao cadastrar bem patrimonial:',
                erro
            );

            el.mensagemFormulario.innerHTML = `
                <div class="alert alert-danger small mb-0">
                    ${escaparHTML(interpretarErro(erro))}
                </div>`;

        } finally {
            el.btnSalvar.disabled = false;

            const texto = el.idBem.value
                ? 'Atualizar Bem Patrimonial'
                : 'Salvar Bem Patrimonial';

            el.btnSalvar.innerHTML = `
                <i class="bi bi-check-lg me-1"></i>
                <span id="textoBotaoSalvarBem">${texto}</span>
            `;
        }
    }

    function badgeStatus(status) {
        return status === 'ativo'
            ? '<span class="badge bg-success-subtle text-success">Ativo</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">Inativo</span>';
    }

    function mostrarCarregamento() {
        el.lista.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm
                        text-success me-2"></span>
                    Carregando bens patrimoniais...
                </td>
            </tr>`;

        el.listaMobile.innerHTML = `
            <div class="text-center text-muted py-4">
                Carregando bens patrimoniais...
            </div>`;
    }

    function mostrarMensagem(mensagem, tipo) {
        el.mensagem.innerHTML = `
            <div class="alert alert-${tipo} alert-dismissible
                fade show small" role="alert">

                ${escaparHTML(mensagem)}

                <button type="button" class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Fechar">
                </button>
            </div>`;
    }

    function interpretarErro(erro) {
        if (!erro.status) {
            return 'Não foi possível conectar ao servidor. Verifique sua conexão.';
        }

        if (erro.status === 401) {
            return 'Sua sessão expirou. Entre novamente no sistema.';
        }

        if (erro.status === 403) {
            return 'Você não tem permissão para realizar esta operação.';
        }

        if (erro.status === 404) {
            return 'A rota ou o registro solicitado não foi encontrado.';
        }

        if (erro.status === 422) {
            const erros = erro.dados?.errors;

            return erros
                ? Object.values(erros).flat().join(' ')
                : (
                    erro.dados?.message ||
                    'Verifique os campos informados.'
                );
        }

        if (erro.status >= 500) {
            return 'Ocorreu um erro interno no servidor. Consulte o terminal do Laravel.';
        }

        return erro.dados?.message ||
            'Não foi possível concluir a operação.';
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
