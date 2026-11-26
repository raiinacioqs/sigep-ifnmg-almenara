document.addEventListener('DOMContentLoaded', () => {
    const campoBusca = document.getElementById('buscaResponsaveis');
    const filtroStatus = document.getElementById('filtroStatusResponsaveis');
    const corpoTabela = document.getElementById('corpoTabelaResponsaveis');
    const listaMobile = document.getElementById('listaResponsaveisMobile');
    const textoPaginacao = document.getElementById('textoPaginacaoResponsaveis');
    const paginacao = document.getElementById('paginacaoResponsaveis');
    const textoPaginacaoMobile = document.getElementById('textoPaginacaoResponsaveisMobile');
    const paginacaoMobile = document.getElementById('paginacaoResponsaveisMobile');
    const selectSetor = document.getElementById('setorResponsavel');
    const btnNovoResponsavel = document.getElementById('btnNovoResponsavel');
    const elementoModalResponsavel = document.getElementById('modalResponsavel');
    const formularioResponsavel = document.getElementById('formResponsavel');
    const btnSalvarResponsavel = document.getElementById('btnSalvarResponsavel');
    const mensagemFormularioResponsavel = document.getElementById('mensagemFormularioResponsavel');

    btnNovoResponsavel.addEventListener('click', () => {
        formularioResponsavel.reset();

        mensagemFormularioResponsavel.className = 'alert d-none';
        mensagemFormularioResponsavel.textContent = '';

        const modal = bootstrap.Modal.getOrCreateInstance(
            elementoModalResponsavel
        );

        modal.show();
    });

    formularioResponsavel.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const camposObrigatorios = [
            document.getElementById('siapeResponsavel'),
            document.getElementById('nomeResponsavel'),
            document.getElementById('emailResponsavel'),
            document.getElementById('cargoResponsavel'),
            selectSetor
        ];

        const campoVazio = camposObrigatorios.find(
            campo => !campo.value.trim()
        );

        const campoSiape = document.getElementById('siapeResponsavel');

        if (campoSiape.value.trim().length > 20) {
            mensagemFormularioResponsavel.className = 'alert alert-warning';
            mensagemFormularioResponsavel.textContent =
                'O SIAPE deve ter no máximo 20 caracteres.';

            campoSiape.focus();
            return;
        }

        if (campoVazio) {
            mensagemFormularioResponsavel.className = 'alert alert-warning';
            mensagemFormularioResponsavel.textContent =
                'Preencha todos os campos obrigatórios.';

            campoVazio.focus();
            return;
        }

        const campoEmail = document.getElementById('emailResponsavel');

        const formatoEmailValido =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(campoEmail.value.trim());

        if (!formatoEmailValido) {
            mensagemFormularioResponsavel.className = 'alert alert-warning';
            mensagemFormularioResponsavel.textContent =
                'Informe um endereço de e-mail válido.';

            campoEmail.focus();
            return;
        }

        const dadosResponsavel = {
            siape: document.getElementById('siapeResponsavel').value.trim(),
            nome: document.getElementById('nomeResponsavel').value.trim(),
            email: document.getElementById('emailResponsavel').value.trim(),
            cargo: document.getElementById('cargoResponsavel').value.trim(),
            setor_id: Number(selectSetor.value)
        };

        btnSalvarResponsavel.disabled = true;
        btnSalvarResponsavel.textContent = 'Salvando...';

        try {
            const resposta = await SIGEPAPI.requisicao('/responsaveis', {
                method: 'POST',
                body: dadosResponsavel
            });

            mensagemFormularioResponsavel.className = 'alert alert-success';
            mensagemFormularioResponsavel.textContent =
                resposta.message || 'Responsável cadastrado com sucesso.';

            await carregarResponsaveis();
            formularioResponsavel.reset();

            bootstrap.Modal.getInstance(elementoModalResponsavel)?.hide();
        } catch (erro) {
            console.error('Erro ao cadastrar responsável:', erro);

            mensagemFormularioResponsavel.className = 'alert alert-danger';

            const errosValidacao = erro.dados?.errors;

            if (errosValidacao) {
                mensagemFormularioResponsavel.textContent =
                    Object.values(errosValidacao).flat().join(' ');
            } else {
                mensagemFormularioResponsavel.textContent =
                    erro.message || 'Não foi possível cadastrar o responsável.';
            }
        } finally {
            btnSalvarResponsavel.disabled = false;
            btnSalvarResponsavel.textContent = 'Salvar Responsável';
        }
    });
    

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

            textoPaginacaoMobile.textContent = meta.total > 0
            ? `Exibindo ${meta.from}-${meta.to} de ${meta.total}`
            : 'Nenhum responsável';

            paginacaoMobile.innerHTML = `
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

            paginacaoMobile.querySelectorAll('button[data-page]').forEach(botao => {
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

    async function carregarSetoresResponsavel() {
        try {
            const resposta = await SIGEPAPI.requisicao('/setores');
            const setores = resposta.data;

            selectSetor.innerHTML = `
                <option value="">Selecione o setor</option>
            `;

            setores.forEach(setor => {
                const opcao = document.createElement('option');
                opcao.value = setor.id;
                opcao.textContent = `${setor.sigla} - ${setor.nome}`;

                selectSetor.appendChild(opcao);
            });
        } catch (erro) {
            console.error('Erro ao carregar setores:', erro);
            selectSetor.innerHTML = `
                <option value="">Não foi possível carregar os setores</option>
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
    carregarSetoresResponsavel();
});