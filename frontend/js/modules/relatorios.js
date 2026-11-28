document.addEventListener('DOMContentLoaded', () => {
    const botaoRelatorioQuantitativo = document.getElementById('btnRelatorioQuantitativo');

    botaoRelatorioQuantitativo.addEventListener('click', async () => {
        try {
            const resposta = await SIGEPAPI.requisicao('/gestao/bens-patrimoniais');

            const resultado = document.getElementById('resultadoRelatorioQuantitativo');

            resultado.innerHTML = `
                <div class="alert alert-success mb-0">
                    <strong>Total de bens cadastrados:</strong> ${resposta.total}
                </div>
            `;

            const contagemCategorias = {};

            for (let pagina = 1; pagina <= resposta.last_page; pagina++) {
                const dadosPagina = pagina === 1
                    ? resposta
                    : await SIGEPAPI.requisicao(
                        `/gestao/bens-patrimoniais?page=${pagina}`
                    );

                dadosPagina.data.forEach(bem => {
                    const nomeCategoria = bem.categoria?.nome || 'Sem categoria';

                    contagemCategorias[nomeCategoria] =
                        (contagemCategorias[nomeCategoria] || 0) + 1;
                });
            }

            const resultadoCategorias = document.getElementById('resultadoCategorias');

            resultadoCategorias.innerHTML = `
                <h6 class="fw-bold text-dark mb-2">Bens por categoria</h6>
                <ul class="list-group list-group-flush">
                    ${Object.entries(contagemCategorias).map(([categoria, quantidade]) => `
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>${categoria}</span>
                            <span class="badge bg-success rounded-pill">${quantidade}</span>
                        </li>
                    `).join('')}
                </ul>
            `;

            const contagemConservacao = {};

            for (let pagina = 1; pagina <= resposta.last_page; pagina++) {
                const dadosPagina = pagina === 1
                    ? resposta
                    : await SIGEPAPI.requisicao(
                        `/gestao/bens-patrimoniais?page=${pagina}`
                    );

                dadosPagina.data.forEach(bem => {
                    const estado = bem.estado_conservacao || 'Não informado';

                    contagemConservacao[estado] =
                        (contagemConservacao[estado] || 0) + 1;
                });
            }

            const resultadoConservacao = document.getElementById('resultadoConservacao');

            resultadoConservacao.innerHTML = `
                <h6 class="fw-bold text-dark mb-2">Bens por estado de conservação</h6>
                <ul class="list-group list-group-flush">
                    ${Object.entries(contagemConservacao).map(([estado, quantidade]) => `
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>${estado}</span>
                            <span class="badge bg-secondary rounded-pill">${quantidade}</span>
                        </li>
                    `).join('')}
                </ul>
            `;

        } catch (erro) {
            console.error('Erro ao buscar bens patrimoniais:', erro);
        }
    });

        // Relatório de movimentações
        const botaoMovimentacoes = document.getElementById(
            'btnRelatorioMovimentacoesPeriodo'
        );

        if (botaoMovimentacoes) {
            botaoMovimentacoes.addEventListener('click', async () => {
                console.log('Clique no botão Movimentações por Período');

                try {
                    const resposta = await SIGEPAPI.requisicao(
                        '/gestao/movimentacoes-patrimoniais?periodo=30'
                    );

                    console.log('Movimentações:', resposta);
                } catch (erro) {
                    console.error('Erro ao carregar movimentações:', erro);
                }
            });
        }

        // Relatório de estoque de materiais
        const botaoEstoqueMateriais = document.getElementById(
            'btnRelatorioEstoqueMateriais'
        );

        if (botaoEstoqueMateriais) {
            botaoEstoqueMateriais.addEventListener('click', () => {
                console.log('Clique no botão Relatório de Estoque de Materiais');

                gerarRelatorioEstoqueMateriais();
            });
        }

            // Histórico de baixas de materiais
            const botaoHistoricoBaixas = document.getElementById(
                'btnRelatorioHistoricoBaixas'
            );

            if (botaoHistoricoBaixas) {
                botaoHistoricoBaixas.addEventListener('click', () => {
                    gerarRelatorioHistoricoBaixas();
                });
            }

    botaoMovimentacoes.addEventListener('click', async () => {
        console.log('Clique no botão Movimentações por Período');

        try {
            const resposta = await SIGEPAPI.requisicao(
                '/gestao/movimentacoes-patrimoniais?periodo=30'
            );

            console.log('Movimentações:', resposta);
        } catch (erro) {
            console.error('Erro ao carregar movimentações:', erro);
        }

            const botaoEstoqueMateriais = document.getElementById(
                'btnRelatorioEstoqueMateriais'
            );

            if (botaoEstoqueMateriais) {
                botaoEstoqueMateriais.addEventListener('click', gerarRelatorioEstoqueMateriais);
            }
    });
});

async function gerarRelatorioEstoqueMateriais() {
    const resultado = document.getElementById(
        'resultadoRelatorioEstoqueMateriais'
    );

    try {
        resultado.innerHTML = `
            <div class="text-muted small">
                Carregando estoque de materiais...
            </div>
        `;

        const resposta = await SIGEPAPI.requisicao('/materiais');

        const materiais = [...(resposta.data || [])];
        const ultimaPagina = resposta.meta?.last_page || resposta.last_page || 1;

        for (let pagina = 2; pagina <= ultimaPagina; pagina++) {
            const dadosPagina = await SIGEPAPI.requisicao(
                `/materiais?page=${pagina}`
            );

            materiais.push(...(dadosPagina.data || []));
        }

        const materiaisConsumo = materiais.filter(
            material => material.categoria?.tipo === 'consumo'
        );

        const materiaisBaixoEstoque = materiaisConsumo.filter(material => {
            const quantidadeAtual = Number(
                material.estoque?.quantidade_atual ?? 0
            );

            const quantidadeMinima = Number(
                material.quantidade_minima ?? 0
            );

            return quantidadeAtual <= quantidadeMinima;
        });

        resultado.innerHTML = `
            <div class="alert alert-success mb-2">
                <strong>Materiais de consumo:</strong>
                ${materiaisConsumo.length}
                <br>
                <strong>Materiais no estoque mínimo ou abaixo:</strong>
                ${materiaisBaixoEstoque.length}
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Saldo</th>
                            <th>Unidade</th>
                            <th>Estoque mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${materiaisConsumo.map(material => {
                            const quantidadeAtual = Number(
                                material.estoque?.quantidade_atual ?? 0
                            );

                            const quantidadeMinima = Number(
                                material.quantidade_minima ?? 0
                            );

                            const estoqueBaixo =
                                quantidadeAtual <= quantidadeMinima;

                            return `
                                <tr>
                                    <td>${material.nome}</td>
                                    <td>
                                        <span class="${estoqueBaixo ? 'text-danger fw-bold' : ''}">
                                            ${quantidadeAtual}
                                        </span>
                                    </td>
                                    <td>${material.unidade_medida || '—'}</td>
                                    <td>${quantidadeMinima}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;

    } catch (erro) {
        console.error('Erro ao gerar relatório de estoque:', erro);

        resultado.innerHTML = `
            <div class="alert alert-danger mb-0">
                Não foi possível carregar o estoque de materiais.
            </div>
        `;
    }
}

async function gerarRelatorioHistoricoBaixas() {
    const resultado = document.getElementById(
        'resultadoRelatorioHistoricoBaixas'
    );

    try {
        resultado.innerHTML = `
            <div class="text-muted small">
                Carregando histórico de baixas...
            </div>
        `;

        const resposta = await SIGEPAPI.requisicao(
            '/gestao/movimentacoes-estoque?tipo=saida'
        );

        console.log('Resposta do histórico de baixas:', resposta);

        console.log(
            'Primeira movimentação:',
            resposta.data[0]
        );

        console.log(
            'Usuário responsável:',
            resposta.data[0]?.usuario
        );

        const movimentacoes = [...(resposta.data || [])];
        const ultimaPagina = resposta.last_page || resposta.meta?.last_page || 1;

        for (let pagina = 2; pagina <= ultimaPagina; pagina++) {
            const dadosPagina = await SIGEPAPI.requisicao(
                `/gestao/movimentacoes-estoque?tipo=saida&page=${pagina}`
            );

            movimentacoes.push(...(dadosPagina.data || []));
        }

        resultado.innerHTML = `
            <div class="alert alert-success mb-2">
                <strong>Total de baixas:</strong>
                ${movimentacoes.length}
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle"
                    style="min-width: 650px;">
                    <thead>
                        <tr>
                            <th style="min-width: 130px;">Data e hora</th>
                            <th style="min-width: 120px;">Material</th>
                            <th style="min-width: 100px;">Quantidade</th>
                            <th style="min-width: 130px;">Responsável</th>
                            <th style="min-width: 180px;">Justificativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${movimentacoes.map(movimentacao => {
                            const data = movimentacao.data_movimentacao
                                ? new Date(movimentacao.data_movimentacao)
                                    .toLocaleString('pt-BR', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })
                                : '—';

                            return `
                                <tr>
                                    <td>${data}</td>
                                    <td>${movimentacao.material?.nome || 'Material não informado'}</td>
                                    <td>${movimentacao.quantidade}</td>
                                    <td>${movimentacao.usuario?.name || 'Não informado'}</td>
                                    <td>${movimentacao.observacao || '—'}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;

    } catch (erro) {
        console.error('Erro ao gerar histórico de baixas:', erro);

        resultado.innerHTML = `
            <div class="alert alert-danger mb-0">
                Não foi possível carregar o histórico de baixas.
            </div>
        `;
    }
}