document.addEventListener('DOMContentLoaded', () => {
    
    carregarBensDisponiveis();
    carregarSetores();
    carregarMinhasSolicitacoes();

    const listaSolicitacoes = document.getElementById(
        'listaMinhasSolicitacoes'
    );

    if (listaSolicitacoes) {
        listaSolicitacoes.addEventListener('click', (event) => {
            const botao = event.target.closest(
                '.btn-atender-solicitacao'
            );

            if (!botao) return;

            const id = botao.dataset.id;

            atenderSolicitacao(id, botao);
        });
    }
});

async function carregarBensDisponiveis() {
    const select = document.getElementById('bemPatrimonial');

    if (!select) return;

    try {
        const resposta = await SIGEPAPI.requisicao(
            '/gestao/bens-patrimoniais'
        );

        const bens = resposta.data;
        console.log('Resposta dos bens patrimoniais:', resposta);

        select.innerHTML = `
            <option value="">Selecione um bem patrimonial</option>
        `;

        bens.forEach((bem) => {
            const option = document.createElement('option');

            option.value = bem.id;
            option.textContent = `${bem.numero_tombo} - ${bem.descricao}`;

            select.appendChild(option);
        });

    } catch (erro) {
        console.error('Erro ao carregar bens patrimoniais:', erro);

        select.innerHTML = `
            <option value="">Erro ao carregar bens patrimoniais</option>
        `;
    }
}

async function carregarSetores() {
    const select = document.getElementById('setorDestino');

    if (!select) return;

    try {
        const resposta = await SIGEPAPI.requisicao('/setores');
        console.log('Resposta dos setores:', resposta);

        // Trata respostas paginadas ou listas simples
        const setores = resposta.data?.data ?? resposta.data;

        select.innerHTML = `
            <option value="">Selecione um setor de destino</option>
        `;

        setores.forEach((setor) => {
            const option = document.createElement('option');

            option.value = setor.id;
            option.textContent = `${setor.sigla} - ${setor.nome}`;

            select.appendChild(option);

            console.log('Opções de bens no select:', select.options.length);
            console.log('Bem carregado:', select.options[1]?.textContent);
            console.log('Opções de setores no select:', select.options.length);
            console.log('Setor carregado:', select.options[1]?.textContent);
        });

    } catch (erro) {
        console.error('Erro ao carregar setores:', erro);

        select.innerHTML = `
            <option value="">Erro ao carregar setores</option>
        `;
    }
}

async function carregarMinhasSolicitacoes() {
    const lista = document.getElementById('listaMinhasSolicitacoes');

    if (!lista) return;

    try {
        lista.innerHTML = `
            <div class="text-center text-muted">
                Carregando solicitações...
            </div>
        `;

        const resposta = await SIGEPAPI.requisicao(
            '/gestao/solicitacoes-retirada'
        );

        const solicitacoes = resposta.data;

        if (!solicitacoes || solicitacoes.length === 0) {
            lista.innerHTML = `
                <div class="alert alert-info">
                    Você ainda não possui solicitações.
                </div>
            `;
            return;
        }

        lista.innerHTML = '';

        solicitacoes.forEach((solicitacao) => {
            const itens = solicitacao.itens || [];

            const descricaoItens = itens.map((item) => {
                if (item.bem_patrimonial) {
                    return `
                        <li>
                            ${item.bem_patrimonial.numero_tombo}
                            - ${item.bem_patrimonial.descricao}
                        </li>
                    `;
                }

                if (item.material) {
                    return `
                        <li>
                            ${item.material.nome}
                            - Quantidade: ${item.quantidade_solicitada}
                        </li>
                    `;
                }

                return '<li>Item não identificado</li>';
            }).join('');

            const status = solicitacao.status || 'pendente';

            const coresStatus = {
                pendente: 'warning',
                atendida: 'success',
                rejeitada: 'danger'
            };

            const cor = coresStatus[status] || 'secondary';

            const card = document.createElement('div');
            card.className = 'card mb-3';

            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between
                                align-items-center mb-2">
                        <h6 class="mb-0">
                            Solicitação #${solicitacao.id}
                        </h6>

                        <span class="badge bg-${cor}">
                            ${status.charAt(0).toUpperCase() + status.slice(1)}
                        </span>
                    </div>

                    <p class="mb-1">
                        <strong>Solicitante:</strong>
                        ${solicitacao.usuario?.nome || 'Não identificado'}
                    </p>

                    <p class="mb-1">
                        <strong>Setor de origem:</strong>
                        ${solicitacao.setor?.sigla || ''}
                        ${solicitacao.setor?.nome || 'Não identificado'}
                    </p>

                    <p class="mb-1">
                        <strong>Data necessária:</strong>
                        ${solicitacao.data_necessaria || 'Não informada'}
                    </p>

                    <p class="mb-1">
                        <strong>Justificativa:</strong>
                        ${solicitacao.justificativa || 'Não informada'}
                    </p>

                    <strong>Itens solicitados:</strong>
                    <ul class="mb-0">
                        ${descricaoItens}
                    </ul>
                </div>
                ${status === 'pendente' ? `
                    <div class="mt-3 text-end">
                        <button
                            type="button"
                            class="btn btn-success me-2 mb-2 btn-sm btn-atender-solicitacao"
                            data-id="${solicitacao.id}">
                            Atender solicitação
                        </button>
                    </div>
                ` : ''}
            `;

            lista.appendChild(card);
        });

    } catch (erro) {
        console.error('Erro ao carregar minhas solicitações:', erro);

        lista.innerHTML = `
            <div class="alert alert-danger">
                Não foi possível carregar suas solicitações.
            </div>
        `;
    }
}

const formSolicitacao = document.getElementById('formSolicitacaoRetirada');

if (formSolicitacao) {
    formSolicitacao.addEventListener('submit', enviarSolicitacao);
}

async function enviarSolicitacao(event) {
    event.preventDefault();

    const botao = document.getElementById('btnEnviarSolicitacao');
    const mensagem = document.getElementById('mensagemSolicitacaoRetirada');

    const dados = {
        data_necessaria: document.getElementById('dataNecessaria').value,
        justificativa: document.getElementById('justificativa').value,
        itens: [
            {
                bem_patrimonial_id: document.getElementById('bemPatrimonial').value,
                quantidade_solicitada: 1
            }
        ]
    };

    try {
        botao.disabled = true;
        botao.textContent = 'Enviando...';

        mensagem.innerHTML = '';

        await SIGEPAPI.requisicao(
            '/servidor/solicitacoes-retirada',
            {
                method: 'POST',
                body: dados
            }
        );

        mensagem.innerHTML = `
            <div class="alert alert-success">
                Solicitação enviada com sucesso!
            </div>
        `;

        formSolicitacao.reset();

        document.getElementById('quantidadeSolicitada').value = 1;

        carregarBensDisponiveis();
        carregarSetores();

    } catch (erro) {
        console.error('Erro ao enviar solicitação:', erro);

        let mensagemErro = erro.message;

        if (erro.dados?.errors) {
            mensagemErro = Object.values(erro.dados.errors)
                .flat()
                .join('<br>');
        }

        mensagem.innerHTML = `
            <div class="alert alert-danger">
                ${mensagemErro}
            </div>
        `;

    } finally {
        botao.disabled = false;
        botao.textContent = 'Enviar Solicitação';
    }
}

async function atenderSolicitacao(id, botao) {
    if (!confirm(`Deseja atender a solicitação #${id}?`)) {
        return;
    }

    try {
        botao.disabled = true;
        botao.textContent = 'Atendendo...';

        await SIGEPAPI.requisicao(
            `/gestao/solicitacoes-retirada/${id}/atender`,
            {
                method: 'POST'
            }
        );

        alert('Solicitação atendida com sucesso!');

        await carregarMinhasSolicitacoes();

    } catch (erro) {
        console.error('Erro ao atender solicitação:', erro);

        alert(
            erro.message || 'Não foi possível atender a solicitação.'
        );

        botao.disabled = false;
        botao.textContent = 'Atender solicitação';
    }
}