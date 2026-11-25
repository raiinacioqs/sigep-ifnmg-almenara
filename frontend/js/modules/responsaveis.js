document.addEventListener('DOMContentLoaded', () => {
    const campoBusca = document.getElementById('buscaResponsaveis');
    const filtroStatus = document.getElementById('filtroStatusResponsaveis');
    const corpoTabela = document.getElementById('corpoTabelaResponsaveis');
    const listaMobile = document.getElementById('listaResponsaveisMobile');
    const textoPaginacao = document.getElementById('textoPaginacaoResponsaveis');
    const paginacao = document.getElementById('paginacaoResponsaveis');
    const textoPaginacaoMobile = document.getElementById('textoPaginacaoResponsaveisMobile');
    const paginacaoMobile = document.getElementById('paginacaoResponsaveisMobile');
    

    async function carregarResponsaveis(busca = '', pagina = 1) {
        try {
            const parametros = new URLSearchParams();
            parametros.set('page', pagina);

            if (busca.trim()) {
                parametros.set('busca', busca.trim());
            }

            if (filtroStatus.value === '1') {
                parametros.set('status', 'ativo');
            } else if (filtroStatus.value === '2') {
                parametros.set('status', 'inativo');
            }

            const query = parametros.toString();

            const resposta = await SIGEPAPI.requisicao(
                `/responsaveis${query ? `?${query}` : ''}`
            );
            const responsaveis = resposta.data;

            listaMobile.innerHTML = responsaveis.map(responsavel => `
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">${responsavel.nome}</h6>
                        <p class="small text-muted mb-2">${responsavel.cargo || '-'}</p>

                        <div class="small">
                            <div><strong>SIAPE:</strong> ${responsavel.siape}</div>
                            <div><strong>E-mail:</strong> ${responsavel.email}</div>
                            <div><strong>Setor:</strong> ${responsavel.setor?.sigla || '-'}</div>
                            <div><strong>Status:</strong> ${responsavel.status === 'ativo' ? 'Ativo' : 'Inativo'}</div>
                        </div>
                    </div>
                </div>
            `).join('');

            corpoTabela.innerHTML = responsaveis.map(responsavel => `
                <tr>
                    <td>${responsavel.siape}</td>
                    <td>${responsavel.nome}</td>
                    <td>${responsavel.email}</td>
                    <td>${responsavel.setor?.sigla || '-'}</td>
                    <td>${responsavel.cargo}</td>
                    <td>-</td>
                    <td>${responsavel.status === 'ativo' ? 'Ativo' : 'Inativo'}</td>
                    <td>-</td>
                </tr>
            `).join('');

            const meta = resposta.meta;

            textoPaginacao.textContent = meta.total > 0
                ? `Exibindo ${meta.from}-${meta.to} de ${meta.total} responsáveis`
                : 'Nenhum responsável encontrado';

            const paginaAtual = meta.current_page;
            const ultimaPagina = meta.last_page;

            paginacao.innerHTML = `
                <li class="page-item ${paginaAtual <= 1 ? 'disabled' : ''}">
                    <button class="page-link" data-page="${paginaAtual - 1}"
                        ${paginaAtual <= 1 ? 'disabled' : ''}>
                        Anterior
                    </button>
                </li>

                <li class="page-item active">
                    <span class="page-link">${paginaAtual} de ${ultimaPagina}</span>
                </li>

                <li class="page-item ${paginaAtual >= ultimaPagina ? 'disabled' : ''}">
                    <button class="page-link" data-page="${paginaAtual + 1}"
                        ${paginaAtual >= ultimaPagina ? 'disabled' : ''}>
                        Próximo
                    </button>
                </li>
            `;

            paginacao.querySelectorAll('button[data-page]').forEach(botao => {
                botao.addEventListener('click', () => {
                    const pagina = Number(botao.dataset.page);

                    if (pagina >= 1 && pagina <= ultimaPagina && pagina !== paginaAtual) {
                        carregarResponsaveis(campoBusca.value, pagina);
                    }
                });
            });

        } catch (erro) {
            console.error('Erro ao carregar responsáveis:', erro);
            corpoTabela.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        Não foi possível carregar os responsáveis.
                    </td>
                </tr>
            `;
        }
    }

    campoBusca.addEventListener('input', () => {
        carregarResponsaveis(campoBusca.value);
    });

    filtroStatus.addEventListener('change', () => {
        carregarResponsaveis(campoBusca.value);
    });

    carregarResponsaveis();
});