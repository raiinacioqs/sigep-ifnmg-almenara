document.addEventListener('DOMContentLoaded', function () {
    const campoBusca = document.getElementById('filtroBuscaHistorico');
    const filtroTipo = document.getElementById('filtroTipoHistorico');
    const filtroSetor = document.getElementById('filtroSetorHistorico');
    const filtroPeriodo = document.getElementById('filtroPeriodoHistorico');

    let temporizadorBusca;

    // Busca enquanto o usuário digita, com pequeno intervalo
    if (campoBusca) {
        campoBusca.addEventListener('input', function () {
            clearTimeout(temporizadorBusca);

            temporizadorBusca = setTimeout(() => {
                carregarHistorico();
            }, 400);
        });
    }

    // Atualiza imediatamente ao mudar os selects
    [filtroTipo, filtroSetor, filtroPeriodo].forEach(filtro => {
        if (filtro) {
            filtro.addEventListener('change', carregarHistorico);
        }
    });

    carregarSetoresHistorico();
    carregarHistorico();
});


async function carregarHistorico() {
    const tabela = document.getElementById('tabelaHistorico');
    const listaMobile = document.getElementById('listaHistoricoMobile');

    try {
        const parametros = new URLSearchParams();

        const busca = document.getElementById('filtroBuscaHistorico')?.value.trim();
        const tipo = document.getElementById('filtroTipoHistorico')?.value;
        const setor = document.getElementById('filtroSetorHistorico')?.value;
        const periodo = document.getElementById('filtroPeriodoHistorico')?.value;

        if (busca) parametros.set('busca', busca);
        if (tipo && tipo !== 'todos') parametros.set('tipo', tipo);
        if (periodo) parametros.set('periodo', periodo);

        // O filtro de setor é específico da movimentação patrimonial
        const parametrosPatrimoniais = new URLSearchParams(parametros);

        if (setor && setor !== 'todos') {
            parametrosPatrimoniais.set('setor_destino_id', setor);
        }

        const queryPatrimonial = parametrosPatrimoniais.toString();
        const queryEstoque = parametros.toString();

        const [resPatrimonial, resEstoque] = await Promise.allSettled([
            SIGEPAPI.requisicao(
                '/gestao/movimentacoes-patrimoniais?' + queryPatrimonial
            ),
            SIGEPAPI.requisicao(
                '/gestao/movimentacoes-estoque?' + queryEstoque
            )
        ]);

        // Converte movimentações patrimoniais para formato comum
        const patrimoniais = resPatrimonial.status === 'fulfilled'
            ? (resPatrimonial.value.data ?? []).map(mov => {
                const bem = mov.bem_patrimonial ?? {};

                return {
                    data: mov.data_movimentacao,
                    tipo: normalizarTipoHistorico(mov.tipo),
                    item: bem.descricao ?? 'Bem patrimonial',
                    tombo: bem.numero_tombo ?? '—',
                    quantidade: 1,
                    origem: mov.setor_origem?.nome ?? '—',
                    destino: mov.setor_destino?.nome ?? '—',
                    responsavel: mov.usuario?.name ?? '—'
                };
            })
            : [];

        // Converte movimentações de estoque para formato comum
        const estoque = resEstoque.status === 'fulfilled'
            ? (resEstoque.value.data ?? []).map(mov => {
                const material = mov.material ?? {};

                return {
                    data: mov.data_movimentacao,
                    tipo: normalizarTipoHistorico(mov.tipo),
                    item: material.nome ?? material.descricao ?? 'Material',
                    tombo: '—',
                    quantidade: mov.quantidade ?? 0,
                    origem: '—',
                    destino: '—',
                    responsavel: mov.usuario?.name ?? '—'
                };
            })
            : [];

        // Junta os dois tipos de movimentação e ordena por data
        const movimentacoes = [...patrimoniais, ...estoque]
            .sort((a, b) => {
                return new Date(b.data).getTime() -
                       new Date(a.data).getTime();
            });

        if (
            resPatrimonial.status === 'rejected' &&
            resEstoque.status === 'rejected'
        ) {
            throw new Error('Não foi possível consultar as movimentações.');
        }

        if (movimentacoes.length === 0) {
            const mensagem = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        Nenhuma movimentação registrada.
                    </td>
                </tr>
            `;

            if (tabela) tabela.innerHTML = mensagem;

            if (listaMobile) {
                listaMobile.innerHTML = `
                    <div class="text-center text-muted py-4">
                        Nenhuma movimentação registrada.
                    </div>
                `;
            }

            return;
        }

        // Renderiza tabela desktop
        if (tabela) {
            tabela.innerHTML = movimentacoes.map(mov => `
                <tr>
                    <td class="py-3 px-4 text-muted">
                        ${escapeHTML(formatarData(mov.data))}
                    </td>
                    <td class="py-3 px-3">
                        ${criarBadgeTipo(mov.tipo)}
                    </td>
                    <td class="py-3 px-3 fw-bold text-dark">
                        ${escapeHTML(mov.item)}
                    </td>
                    <td class="py-3 px-3 fw-bold text-dark">
                        ${escapeHTML(mov.tombo)}
                    </td>
                    <td class="py-3 px-3 text-center fw-bold text-dark">
                        ${formatarQuantidade(mov.quantidade)}
                    </td>
                    <td class="py-3 px-3 text-muted">
                        ${escapeHTML(mov.origem)}
                    </td>
                    <td class="py-3 px-3 text-secondary">
                        ${escapeHTML(mov.destino)}
                    </td>
                    <td class="py-3 px-4 text-muted">
                        ${escapeHTML(mov.responsavel ?? '—')}
                    </td>
                </tr>
            `).join('');
        }

        // Renderiza cards mobile
        
        if (listaMobile) {
            listaMobile.innerHTML = movimentacoes.map(mov => {
                const origem = mov.origem ?? '—';
                const destino = mov.destino ?? '—';
                const responsavel = mov.responsavel ?? '—';

                return `
                    <div class="card border-0 shadow-sm p-3 rounded-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="fw-bold text-dark mb-0"
                                style="font-size: 0.9rem;">
                                ${escapeHTML(mov.item)}
                            </h6>

                            <span class="fw-bold text-primary fs-5">
                                ${formatarQuantidade(mov.quantidade)}
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            ${criarBadgeTipo(mov.tipo)}
                            <small class="text-muted" style="font-size: 0.7rem;">
                                ${escapeHTML(formatarData(mov.data))}
                            </small>
                        </div>

                        <small class="text-muted mb-1">
                            Tombo: ${escapeHTML(mov.tombo)}
                        </small>

                        <p class="text-muted small mb-0 pt-2 border-top">
                            De: ${escapeHTML(origem)}
                            • Para: ${escapeHTML(destino)}
                            <br>
                            Responsável: ${escapeHTML(responsavel)}
                        </p>
                    </div>
                `;
            }).join('');
        }


    } catch (erro) {
        console.error('Erro ao carregar histórico:', erro);

        if (tabela) {
            tabela.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        Erro ao carregar o histórico.
                    </td>
                </tr>
            `;
        }

        if (listaMobile) {
            listaMobile.innerHTML = `
                <div class="text-center text-danger py-4">
                    Erro ao carregar o histórico.
                </div>
            `;
        }
    }
}


function formatarData(data) {
    if (!data) return '—';

    const dataObj = new Date(data);

    if (Number.isNaN(dataObj.getTime())) return '—';

    return dataObj.toLocaleString('pt-BR');
}


function formatarQuantidade(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    const numero = Number(valor);

    if (!Number.isFinite(numero)) {
        return '—';
    }

    return Math.round(numero).toLocaleString('pt-BR');
}


function normalizarTipoHistorico(tipo) {
    const valor = String(tipo ?? '').toLowerCase();

    if (valor.includes('entrada')) return 'Entrada';

    if (valor.includes('saida') || valor.includes('saída')) {
        return 'Saída';
    }

    if (valor.includes('transfer')) return 'Transferência';

    return tipo ?? 'Movimentação';
}


function criarBadgeTipo(tipo) {
    const tipoNormalizado = String(tipo ?? '').toLowerCase();

    let estilo = 'background-color: #cfe2ff; color: #084298;';
    let texto = tipo || 'Movimentação';

    if (tipoNormalizado.includes('entrada')) {
        estilo = 'background-color: #d1e7dd; color: #0f5132;';
        texto = 'Entrada';
    } else if (
        tipoNormalizado.includes('saida') ||
        tipoNormalizado.includes('saída')
    ) {
        estilo = 'background-color: #f8d7da; color: #842029;';
        texto = 'Saída';
    } else if (tipoNormalizado.includes('transfer')) {
        estilo = 'background-color: #cfe2ff; color: #084298;';
        texto = 'Transferência';
    }

    return `
        <span class="badge rounded-pill px-2 py-1"
              style="${estilo} display: inline-block; white-space: nowrap; font-size: 0.75rem; font-weight: 600;">
            ${escapeHTML(texto)}
        </span>
    `;
}


function escapeHTML(valor) {
    return String(valor)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}


async function carregarSetoresHistorico() {
    const filtroSetor = document.getElementById('filtroSetorHistorico');

    if (!filtroSetor) return;

    try {
        const resposta = await SIGEPAPI.requisicao('/setores?status=ativo');
        const setores = resposta.data ?? [];

        setores.forEach(setor => {
            const opcao = document.createElement('option');

            opcao.value = setor.id;
            opcao.textContent = setor.nome;

            filtroSetor.appendChild(opcao);
        });

    } catch (erro) {
        console.error('Erro ao carregar setores do histórico:', erro);
    }
}
