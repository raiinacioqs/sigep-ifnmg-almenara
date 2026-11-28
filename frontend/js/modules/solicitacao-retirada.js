document.addEventListener('DOMContentLoaded', () => {
    carregarBensDisponiveis();
    carregarSetores();
});

async function carregarBensDisponiveis() {
    const select = document.getElementById('bemPatrimonial');

    if (!select) return;

    try {
        const resposta = await SIGEPAPI.requisicao(
            '/gestao/bens-patrimoniais'
        );

        const bens = resposta.data;

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
        });

    } catch (erro) {
        console.error('Erro ao carregar setores:', erro);

        select.innerHTML = `
            <option value="">Erro ao carregar setores</option>
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