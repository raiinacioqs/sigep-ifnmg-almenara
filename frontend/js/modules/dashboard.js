document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Busca e Filtro em Tempo Real nas Tabelas
    const searchInputs = document.querySelectorAll('input[placeholder*="Buscar"], input[placeholder*="Pesquisar"]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function (e) {
            const term = e.target.value.toLowerCase();
            const container = input.closest('.card, main');
            if (!container) return;
            
            // Filtra linhas de tabelas
            const rows = container.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });

            // Filtra cards mobile (caso existam)
            const mobileCards = container.querySelectorAll('.d-md-none .card');
            mobileCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });

    // 2. Feedback ao Enviar Formulários (Cadastro, Retirada, Configurações)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        
    });

    // 3. Confirmação para Ações Críticas (Excluir, Rejeitar)
    const actionButtons = document.querySelectorAll('button, .btn');
    actionButtons.forEach(btn => {
        const text = btn.textContent.trim().toLowerCase();
        if (text.includes('excluir') || text.includes('rejeitar')) {
            btn.addEventListener('click', function (e) {
                if (!confirm('Atenção: Tem certeza que deseja executar esta ação permanente?')) {
                    e.preventDefault();
                }
            });
        } else if (text.includes('aprovar') || text.includes('salvar') || text.includes('executar backup')) {
            btn.addEventListener('click', function (e) {
                if (btn.type !== 'submit' && !btn.closest('form')) {
                    alert('Ação processada com sucesso no sistema!');
                }
            });
        }
    });

    // 4. Interatividade nas Pílulas de Filtro (Mobile)
    const filterPills = document.querySelectorAll('.d-md-none .rounded-pill');
    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => {
                p.classList.remove('btn-success', 'btn-dark', 'text-white');
                p.classList.add('btn-light', 'border', 'text-secondary');
            });
            this.classList.remove('btn-light', 'border', 'text-secondary');
            this.classList.add('btn-success', 'text-white');
        });
    });

    
    carregarIndicadoresDashboard();
    carregarMovimentacoesDashboard();
    

    async function carregarIndicadoresDashboard() {
        const ids = [
            'totalItens',
            'estoqueBaixo',
            'solicitacoesPendentes',
            'setoresAtivos'
        ];

        ids.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) elemento.textContent = '...';
        });

        try {
            const [materiais, solicitacoes, setores] = await Promise.all([
                buscarTodosDashboard('/materiais?status=ativo'),
                buscarTodosDashboard(
                    '/gestao/solicitacoes-retirada?status=pendente'
                ),
                buscarTodosDashboard('/setores?status=ativo')
            ]);

            const totalItens = materiais.reduce((total, material) => {
                return total + Number(
                    material.estoque?.quantidade_atual ?? 0
                );
            }, 0);

            const estoqueBaixo = materiais.filter(material => {
                const atual = Number(
                    material.estoque?.quantidade_atual ?? 0
                );

                const minimo = Number(
                    material.quantidade_minima ?? 0
                );

                return atual <= minimo;
            }).length;

            atualizarIndicadorDashboard('totalItens', totalItens);
            atualizarIndicadorDashboard('estoqueBaixo', estoqueBaixo);
            atualizarIndicadorDashboard(
                'solicitacoesPendentes',
                solicitacoes.length
            );
            atualizarIndicadorDashboard('setoresAtivos', setores.length);

        } catch (erro) {
            console.error('Erro ao carregar indicadores:', erro);

            ids.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) elemento.textContent = '—';
            });
        }
    }

    async function buscarTodosDashboard(endpoint) {
        const resposta = await SIGEPAPI.requisicao(endpoint);

        const registros = [...(resposta.data ?? [])];
        const ultimaPagina = Number(resposta.meta?.last_page ?? 1);

        for (let pagina = 2; pagina <= ultimaPagina; pagina++) {
            const separador = endpoint.includes('?') ? '&' : '?';

            const proximaResposta = await SIGEPAPI.requisicao(
                `${endpoint}${separador}page=${pagina}`
            );

            registros.push(...(proximaResposta.data ?? []));
        }

        return registros;
    }

    function atualizarIndicadorDashboard(id, valor) {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent = Number(valor).toLocaleString('pt-BR');
        }
    }


    async function carregarMovimentacoesDashboard() {
        const tabela = document.getElementById('tabelaMovimentacoes');
        const listaMobile = document.getElementById('listaMovimentacoesMobile');

        try {
            const resposta = await SIGEPAPI.requisicao(
                '/gestao/movimentacoes-patrimoniais'
            );

            const movimentacoes = resposta.data ?? [];

            if (movimentacoes.length === 0) {
                tabela.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Nenhuma movimentação registrada.
                        </td>
                    </tr>
                `;

                listaMobile.innerHTML = `
                    <div class="text-center text-muted py-4">
                        Nenhuma movimentação registrada.
                    </div>
                `;

                return;
            }

            tabela.innerHTML = movimentacoes.map(movimentacao => {
                const bem = movimentacao.bem_patrimonial ?? {};
                const origem = movimentacao.setor_origem?.nome ?? '—';
                const destino = movimentacao.setor_destino?.nome ?? '—';

                return `
                    <tr>
                        <td>${formatarDataMovimentacao(
                            movimentacao.data_movimentacao
                        )}</td>
                        <td>${escapeHTML(movimentacao.tipo ?? '—')}</td>
                        <td>${escapeHTML(bem.descricao ?? '—')}</td>
                        <td>${escapeHTML(bem.numero_tombo ?? '—')}</td>
                        <td class="text-center">1</td>
                        <td>${escapeHTML(origem)}</td>
                        <td>${escapeHTML(destino)}</td>
                    </tr>
                `;
            }).join('');

            listaMobile.innerHTML = movimentacoes.map(movimentacao => {
                const bem = movimentacao.bem_patrimonial ?? {};
                const origem = movimentacao.setor_origem?.nome ?? '—';
                const destino = movimentacao.setor_destino?.nome ?? '—';

                return `
                    <div class="p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-success">
                                ${escapeHTML(movimentacao.tipo ?? 'Movimentação')}
                            </span>
                            <small class="text-muted">
                                ${formatarDataMovimentacao(
                                    movimentacao.data_movimentacao
                                )}
                            </small>
                        </div>

                        <h6 class="fw-bold text-dark mb-1 small">
                            ${escapeHTML(bem.descricao ?? 'Bem patrimonial')}
                        </h6>

                        <div class="small text-muted mb-2">
                            Tombo: ${escapeHTML(bem.numero_tombo ?? '—')}
                        </div>

                        <div class="small text-muted border-top pt-2">
                            <div>Origem: ${escapeHTML(origem)}</div>
                            <div>Destino: ${escapeHTML(destino)}</div>
                        </div>
                    </div>
                `;
            }).join('');

        } catch (erro) {
            console.error('Erro ao carregar movimentações:', erro);

            tabela.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        Erro ao carregar movimentações.
                    </td>
                </tr>
            `;

            listaMobile.innerHTML = `
                <div class="text-center text-danger py-4">
                    Erro ao carregar movimentações.
                </div>
            `;
        }
    }

    function formatarDataMovimentacao(data) {
        if (!data) return '—';

        const dataObj = new Date(data);

        if (Number.isNaN(dataObj.getTime())) {
            return '—';
        }

        return dataObj.toLocaleString('pt-BR');
    }

    function escapeHTML(valor) {
        return String(valor)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


});