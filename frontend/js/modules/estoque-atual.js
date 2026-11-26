(function () {
    'use strict';

    const ITENS_POR_PAGINA = 10;

    let materiais = [];
    let materiaisFiltrados = [];
    let paginaAtual = 1;

    const elementos = {};

    document.addEventListener('DOMContentLoaded', inicializar);

    async function inicializar() {
        elementos.lista = document.getElementById('listaEstoque');
        elementos.listaMobile = document.getElementById('listaEstoqueMobile');
        elementos.busca = document.getElementById('buscaEstoque');
        elementos.filtroCategoria = document.getElementById('filtroCategoriaEstoque');
        elementos.paginacao = document.getElementById('paginacaoEstoque');
        elementos.resumo = document.getElementById('resumoEstoque');
        elementos.mensagem = document.getElementById('mensagemEstoque');
        elementos.btnAtualizar = document.getElementById('btnAtualizarEstoque');

        if (!window.SIGEPAPI) {
            mostrarMensagem(
                'Não foi possível inicializar a comunicação com a API.',
                'danger'
            );
            return;
        }

        configurarEventos();
        await carregarMateriais();
    }

    function configurarEventos() {
        let temporizadorBusca;

        elementos.busca.addEventListener('input', () => {
            clearTimeout(temporizadorBusca);

            temporizadorBusca = setTimeout(() => {
                paginaAtual = 1;
                aplicarFiltros();
            }, 300);
        });

        elementos.filtroCategoria.addEventListener('change', () => {
            paginaAtual = 1;
            aplicarFiltros();
        });

        elementos.btnAtualizar.addEventListener('click', carregarMateriais);

        elementos.paginacao.addEventListener('click', evento => {
            const botao = evento.target.closest('[data-pagina]');

            if (!botao || botao.disabled) return;

            paginaAtual = Number(botao.dataset.pagina);
            renderizarEstoque();
        });
    }

    async function carregarMateriais() {
        definirCarregamento(true);
        limparMensagem();

        try {
            const resposta = await buscarTodasPaginas(
                '/materiais?status=ativo&per_page=100'
            );

            materiais = resposta.filter(material => {
                return (
                    material.status === 'ativo' &&
                    material.categoria?.tipo === 'consumo'
                );
            });

            preencherCategorias();
            aplicarFiltros();

        } catch (erro) {
            console.error('Erro ao carregar estoque:', erro);

            mostrarMensagem(
                erro.dados?.message ||
                'Não foi possível carregar os materiais do estoque.',
                'danger'
            );

            elementos.lista.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Erro ao carregar materiais.
                    </td>
                </tr>
            `;

            elementos.listaMobile.innerHTML = `
                <div class="text-center text-muted py-4">
                    Não foi possível carregar os materiais.
                </div>
            `;

            if (elementos.lista) {
                elementos.lista.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Erro ao carregar materiais.
                        </td>
                    </tr>
                `;
            }

            if (elementos.listaMobile) {
                elementos.listaMobile.innerHTML = `
                    <div class="text-center text-muted py-4">
                        Não foi possível carregar os materiais.
                    </div>
                `;
            }

        } finally {
            definirCarregamento(false);
        }
    }

    async function buscarTodasPaginas(endpoint) {
        let pagina = 1;
        let ultimaPagina = 1;
        const resultado = [];

        do {
            const separador = endpoint.includes('?') ? '&' : '?';

            const resposta = await SIGEPAPI.requisicao(
                `${endpoint}${separador}page=${pagina}`
            );

            const dados = Array.isArray(resposta)
                ? resposta
                : resposta.data || [];

            resultado.push(...dados);

            ultimaPagina =
                resposta.meta?.last_page ??
                resposta.last_page ??
                1;

            pagina++;

        } while (pagina <= ultimaPagina);

        return resultado;
    }

    function preencherCategorias() {
        const categorias = new Map();

        materiais.forEach(material => {
            const categoria = material.categoria;

            if (categoria && categoria.id !== undefined) {
                categorias.set(
                    String(categoria.id),
                    categoria.nome || 'Sem categoria'
                );
            }
        });

        elementos.filtroCategoria.innerHTML =
            '<option value="">Todas as categorias</option>';

        [...categorias.entries()]
            .sort((a, b) => a[1].localeCompare(b[1], 'pt-BR'))
            .forEach(([id, nome]) => {
                const option = document.createElement('option');

                option.value = id;
                option.textContent = nome;

                elementos.filtroCategoria.appendChild(option);
            });
    }

    function aplicarFiltros() {
        const termo = elementos.busca.value
            .trim()
            .toLowerCase();

        const categoriaSelecionada =
            elementos.filtroCategoria.value;

        materiaisFiltrados = materiais.filter(material => {
            const nome = material.nome || '';
            const codigo =
                material.codigo ||
                material.tombo ||
                material.codigo_material ||
                '';

            const categoriaNome =
                material.categoria?.nome || '';

            const correspondeBusca = [
                nome,
                codigo,
                categoriaNome
            ].some(valor =>
                String(valor).toLowerCase().includes(termo)
            );

            const correspondeCategoria =
                !categoriaSelecionada ||
                String(material.categoria?.id) === categoriaSelecionada;

            return correspondeBusca && correspondeCategoria;
        });

        renderizarEstoque();
    }

    function renderizarEstoque() {
        const total = materiaisFiltrados.length;
        const totalPaginas = Math.max(
            1,
            Math.ceil(total / ITENS_POR_PAGINA)
        );

        if (paginaAtual > totalPaginas) {
            paginaAtual = totalPaginas;
        }

        const inicio = (paginaAtual - 1) * ITENS_POR_PAGINA;

        const materiaisPagina = materiaisFiltrados.slice(
            inicio,
            inicio + ITENS_POR_PAGINA
        );

        renderizarTabela(materiaisPagina);
        renderizarCardsMobile(materiaisPagina);
        renderizarPaginacao(totalPaginas);

        elementos.resumo.textContent = total === 0
            ? 'Nenhum material encontrado.'
            : `Exibindo ${inicio + 1} a ${Math.min(
                inicio + ITENS_POR_PAGINA,
                total
            )} de ${total} materiais.`;
    }

    function renderizarTabela(lista) {
        elementos.lista.innerHTML = '';

        if (lista.length === 0) {
            elementos.lista.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Nenhum material encontrado.
                    </td>
                </tr>
            `;
            return;
        }

        lista.forEach(material => {
            const quantidade = obterQuantidade(material);
            const unidade = material.unidade_medida || '-';
            const categoria = material.categoria?.nome || '-';
            const codigo = obterCodigo(material);
            const situacao = obterSituacao(material, quantidade);

            const linha = document.createElement('tr');

            linha.innerHTML = `
                <td>${escaparHTML(codigo)}</td>
                <td class="fw-semibold">${escaparHTML(material.nome || '-')}</td>
                <td>${escaparHTML(categoria)}</td>
                <td>${escaparHTML(unidade)}</td>
                <td class="text-end fw-semibold">
                    ${formatarQuantidade(quantidade)}
                </td>
                <td class="text-center">
                    <span class="badge ${situacao.classe}">
                        ${situacao.texto}
                    </span>
                </td>
            `;

            elementos.lista.appendChild(linha);
        });
    }

    function renderizarCardsMobile(lista) {
        elementos.listaMobile.innerHTML = '';

        if (lista.length === 0) {
            elementos.listaMobile.innerHTML = `
                <div class="text-center text-muted py-4">
                    Nenhum material encontrado.
                </div>
            `;
            return;
        }

        lista.forEach(material => {
            const quantidade = obterQuantidade(material);
            const situacao = obterSituacao(material, quantidade);

            const card = document.createElement('div');

            card.className = 'card border-0 shadow-sm p-3 rounded-3';

            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">
                        Código: ${escaparHTML(obterCodigo(material))}
                    </span>

                    <span class="badge ${situacao.classe}">
                        ${situacao.texto}
                    </span>
                </div>

                <h6 class="fw-bold text-dark mb-1">
                    ${escaparHTML(material.nome || '-')}
                </h6>

                <div class="text-muted small pt-2 border-top">
                    <div>
                        Categoria:
                        ${escaparHTML(material.categoria?.nome || '-')}
                    </div>

                    <div class="d-flex justify-content-between mt-1">
                        <span>
                            Unidade:
                            ${escaparHTML(material.unidade_medida || '-')}
                        </span>

                        <span class="fw-bold text-dark">
                            Qtd: ${formatarQuantidade(quantidade)}
                        </span>
                    </div>
                </div>
            `;

            elementos.listaMobile.appendChild(card);
        });
    }

    function renderizarPaginacao(totalPaginas) {
        elementos.paginacao.innerHTML = '';

        if (totalPaginas <= 1) return;

        const anterior = criarBotaoPagina(
            'Anterior',
            paginaAtual - 1,
            paginaAtual === 1
        );

        elementos.paginacao.appendChild(anterior);

        for (let pagina = 1; pagina <= totalPaginas; pagina++) {
            const botao = criarBotaoPagina(
                pagina,
                pagina,
                false
            );

            if (pagina === paginaAtual) {
                botao.classList.add('active');
            }

            elementos.paginacao.appendChild(botao);
        }

        const proximo = criarBotaoPagina(
            'Próxima',
            paginaAtual + 1,
            paginaAtual === totalPaginas
        );

        elementos.paginacao.appendChild(proximo);
    }

    function criarBotaoPagina(texto, pagina, desabilitado) {
        const botao = document.createElement('button');

        botao.type = 'button';
        botao.className = 'btn btn-sm btn-outline-success';
        botao.textContent = texto;
        botao.dataset.pagina = pagina;
        botao.disabled = desabilitado;

        return botao;
    }

    function obterCodigo(material) {
        return material.codigo ||
            material.tombo ||
            material.codigo_material ||
            material.id ||
            '-';
    }

    function obterQuantidade(material) {
        const estoque = material.estoque;

        if (typeof estoque === 'number') {
            return estoque;
        }

        return Number(
            estoque?.quantidade_atual ??
            estoque?.quantidade ??
            material.quantidade_estoque ??
            material.quantidade_atual ??
            material.estoque_atual ??
            0
        );
    }

    function obterSituacao(material, quantidade) {
        const estoque = material.estoque;

        const minimo = Number(
            estoque?.quantidade_minima ??
            estoque?.estoque_minimo ??
            material.quantidade_minima ??
            material.estoque_minimo ??
            0
        );

        if (minimo > 0 && quantidade <= minimo) {
            return {
                texto: 'Baixo',
                classe: 'bg-warning text-dark'
            };
        }

        return {
            texto: 'Normal',
            classe: 'bg-success text-white'
        };
    }

    function formatarQuantidade(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            maximumFractionDigits: 3
        });
    }

    function escaparHTML(valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function mostrarMensagem(mensagem, tipo = 'info') {
        elementos.mensagem.innerHTML = '';

        const alerta = document.createElement('div');

        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.setAttribute('role', 'alert');
        alerta.textContent = mensagem;

        const botaoFechar = document.createElement('button');

        botaoFechar.type = 'button';
        botaoFechar.className = 'btn-close';
        botaoFechar.setAttribute('data-bs-dismiss', 'alert');
        botaoFechar.setAttribute('aria-label', 'Fechar');

        alerta.appendChild(botaoFechar);
        elementos.mensagem.appendChild(alerta);
    }

    function limparMensagem() {
        elementos.mensagem.innerHTML = '';
    }

    function definirCarregamento(carregando) {
        elementos.btnAtualizar.disabled = carregando;

        elementos.btnAtualizar.innerHTML = carregando
            ? '<span class="spinner-border spinner-border-sm me-1"></span> Carregando'
            : '<i class="bi bi-arrow-clockwise me-1"></i> Atualizar';
    }

})();