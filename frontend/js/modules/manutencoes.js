const tabelaManutencoes = document.getElementById('tabelaManutencoes');
const andamentoDesktop = document.getElementById('manutencoesEmAndamentoDesktop');
const andamentoMobile = document.getElementById('manutencoesEmAndamentoMobile');
const listaMobile = document.getElementById('listaManutencoesMobile');
const resumoPaginacao = document.getElementById('resumoPaginacaoManutencoes');
const paginacao = document.getElementById('paginacaoManutencoes');

let paginaAtual = 1;

function escaparHTML(valor) {
    return String(valor ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatarData(data) {
    if (!data) return '—';

    const partes = String(data).split('T')[0].split('-');

    if (partes.length !== 3) return '—';

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function formatarMoeda(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    return Number(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function normalizar(valor) {
    return String(valor ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[_-]/g, ' ')
        .trim();
}

function textoTipo(tipo) {
    const valor = normalizar(tipo);

    if (valor === 'preventiva') return 'Preventiva';
    if (valor === 'corretiva') return 'Corretiva';

    return tipo || 'Não informado';
}

function textoStatus(status) {
    const valor = normalizar(status);

    const statusConhecidos = {
        'aberta': 'Aberta',
        'em andamento': 'Em andamento',
        'aguardando peca': 'Aguardando peça',
        'concluida': 'Concluída',
        'cancelada': 'Cancelada'
    };

    return statusConhecidos[valor] || status || 'Não informado';
}

function classeStatus(status) {
    const valor = normalizar(status);

    if (valor === 'concluida') return 'bg-success';
    if (valor === 'cancelada') return 'bg-danger';
    if (valor === 'aguardando peca') return 'bg-info text-dark';
    if (valor === 'em andamento') return 'bg-warning text-dark';

    return 'bg-secondary';
}

function manutencaoFinalizada(manutencao) {
    const status = normalizar(manutencao.status);

    return ['concluida', 'cancelada'].includes(status);
}

function obterBem(manutencao) {
    return manutencao.bem_patrimonial ?? manutencao.bemPatrimonial ?? {};
}

function renderizarCardAndamento(manutencao) {
    const bem = obterBem(manutencao);
    const status = textoStatus(manutencao.status);

    return `
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm p-3 rounded-4 h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary me-1"
                            style="font-size: 0.65rem;">
                            ${escaparHTML(textoTipo(manutencao.tipo))}
                        </span>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Tombo: ${escaparHTML(bem.numero_tombo || '—')}
                        </small>
                    </div>
                    <span class="badge ${classeStatus(manutencao.status)} fw-semibold px-2 py-1"
                        style="font-size: 0.7rem;">
                        ${escaparHTML(status)}
                    </span>
                </div>

                <h6 class="fw-bold text-dark mb-1">
                    ${escaparHTML(bem.descricao || 'Bem não identificado')}
                </h6>

                <small class="text-muted" style="font-size: 0.75rem;">
                    Abertura: ${formatarData(manutencao.data_abertura)}
                    ${manutencao.prestador ? ` • Prestador: ${escaparHTML(manutencao.prestador)}` : ''}
                </small>
            </div>
        </div>
    `;
}

function renderizarCardHistorico(manutencao) {
    const bem = obterBem(manutencao);

    return `
        <div class="card border-0 shadow-sm p-3 rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">
                        ${escaparHTML(bem.descricao || 'Bem não identificado')}
                    </h6>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        ID: MAN-${escaparHTML(manutencao.id)}
                        • ${escaparHTML(textoTipo(manutencao.tipo))}
                    </small>
                </div>

                <span class="badge ${classeStatus(manutencao.status)}"
                    style="font-size: 0.65rem;">
                    ${escaparHTML(textoStatus(manutencao.status))}
                </span>
            </div>
        </div>
    `;
}

function renderizarTabela(manutencoes) {
    if (!manutencoes.length) {
        tabelaManutencoes.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    Nenhuma manutenção cadastrada.
                </td>
            </tr>
        `;
        return;
    }

    tabelaManutencoes.innerHTML = manutencoes.map(manutencao => {
        const bem = obterBem(manutencao);

        return `
            <tr>
                <td class="px-4">MAN-${escaparHTML(manutencao.id)}</td>
                <td>
                    ${escaparHTML(bem.numero_tombo || '—')}
                    <br>
                    <small class="text-muted">
                        ${escaparHTML(bem.descricao || 'Bem não identificado')}
                    </small>
                </td>
                <td>${escaparHTML(textoTipo(manutencao.tipo))}</td>
                <td>${formatarData(manutencao.data_abertura)}</td>
                <td>${formatarData(manutencao.data_conclusao)}</td>
                <td>${escaparHTML(manutencao.prestador || '—')}</td>
                <td>${formatarMoeda(manutencao.custo)}</td>
                <td>
                    <span class="badge ${classeStatus(manutencao.status)}">
                        ${escaparHTML(textoStatus(manutencao.status))}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function renderizarPaginacao(resposta) {
    const total = resposta.total ?? 0;
    const inicio = resposta.from ?? 0;
    const fim = resposta.to ?? 0;
    const pagina = resposta.current_page ?? 1;
    const ultimaPagina = resposta.last_page ?? 1;

    resumoPaginacao.textContent = total === 0
        ? 'Nenhuma manutenção cadastrada.'
        : `Exibindo ${inicio}-${fim} de ${total} manutenção(ões).`;

    if (ultimaPagina <= 1) {
        paginacao.innerHTML = '';
        return;
    }

    paginacao.innerHTML = `
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary"
                data-pagina="${pagina - 1}"
                ${pagina <= 1 ? 'disabled' : ''}>
                Anterior
            </button>

            <button class="btn btn-success" disabled>
                ${pagina} / ${ultimaPagina}
            </button>

            <button class="btn btn-outline-secondary"
                data-pagina="${pagina + 1}"
                ${pagina >= ultimaPagina ? 'disabled' : ''}>
                Próximo
            </button>
        </div>
    `;
}

async function carregarManutencoes(pagina = 1) {
    paginaAtual = pagina;

    tabelaManutencoes.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4">
                Carregando manutenções...
            </td>
        </tr>
    `;

    try {
        const resposta = await SIGEPAPI.requisicao(
            `/gestao/manutencoes?page=${pagina}`
        );

        const manutencoes = resposta.data ?? [];

        renderizarTabela(manutencoes);
        renderizarPaginacao(resposta);

        const emAndamento = manutencoes.filter(
            manutencao => !manutencaoFinalizada(manutencao)
        );

        const historico = manutencoes.filter(
            manutencao => manutencaoFinalizada(manutencao)
        );

        andamentoDesktop.innerHTML = emAndamento.length
            ? emAndamento.map(renderizarCardAndamento).join('')
            : `
                <div class="col-12">
                    <p class="text-muted text-center py-3">
                        Nenhuma manutenção em andamento.
                    </p>
                </div>
            `;

        andamentoMobile.innerHTML = emAndamento.length
            ? emAndamento.map(manutencao => `
                <div class="card border-0 shadow-sm p-3 rounded-3">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <small class="text-muted">
                            Tombo: ${escaparHTML(obterBem(manutencao).numero_tombo || '—')}
                        </small>
                        <span class="badge ${classeStatus(manutencao.status)}">
                            ${escaparHTML(textoStatus(manutencao.status))}
                        </span>
                    </div>
                    <h6 class="fw-bold mb-1">
                        ${escaparHTML(obterBem(manutencao).descricao || 'Bem não identificado')}
                    </h6>
                    <small class="text-muted">
                        ${escaparHTML(textoTipo(manutencao.tipo))}
                        • Abertura: ${formatarData(manutencao.data_abertura)}
                    </small>
                </div>
            `).join('')
            : `
                <p class="text-muted text-center py-3">
                    Nenhuma manutenção em andamento.
                </p>
            `;

        listaMobile.innerHTML = historico.length
            ? historico.map(renderizarCardHistorico).join('')
            : `
                <p class="text-center text-muted py-4 mb-0">
                    Nenhuma manutenção no histórico.
                </p>
            `;

    } catch (erro) {
        console.error('Erro ao carregar manutenções:', erro);

        tabelaManutencoes.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    Não foi possível carregar as manutenções.
                </td>
            </tr>
        `;

        resumoPaginacao.textContent = 'Erro ao carregar dados.';
    }
}

paginacao.addEventListener('click', event => {
    const botao = event.target.closest('[data-pagina]');

    if (!botao || botao.disabled) return;

    carregarManutencoes(Number(botao.dataset.pagina));
});

document.addEventListener('DOMContentLoaded', () => {
    carregarManutencoes(paginaAtual);
});
