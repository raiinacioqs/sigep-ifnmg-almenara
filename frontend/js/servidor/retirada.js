(function () {
    'use strict';

    const API = window.SIGEPAPI;

    const formulario = document.getElementById('formSolicitacaoRetirada');
    const campoMaterial = document.getElementById('material');
    const campoQuantidade = document.getElementById('quantidade');
    const campoData = document.getElementById('dataNecessaria');
    const campoJustificativa = document.getElementById('justificativa');
    const mensagem = document.getElementById('mensagemSolicitacao');
    const botaoEnviar = document.getElementById('btnEnviarSolicitacao');

    function exibirMensagem(texto, tipo = 'danger') {
        mensagem.innerHTML = '';

        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.setAttribute('role', 'alert');
        alerta.textContent = texto;

        mensagem.appendChild(alerta);
    }

    async function carregarMateriais() {
        try {
            let pagina = 1;
            let materiais = [];

            while (true) {
                const resposta = await API.requisicao(
                    `/materiais?status=ativo&page=${pagina}`
                );

                materiais = materiais.concat(resposta.data || []);

                if (!resposta.links?.next) {
                    break;
                }

                pagina++;
            }

            campoMaterial.innerHTML = '';

            const opcaoInicial = document.createElement('option');
            opcaoInicial.value = '';
            opcaoInicial.textContent = 'Selecione um material...';
            campoMaterial.appendChild(opcaoInicial);

            materiais
                .filter(material =>
                    material.categoria?.tipo === 'consumo'
                )
                .forEach(material => {
                    const quantidade = Number(
                        material.estoque?.quantidade_atual ?? 0
                    );

                    const opcao = document.createElement('option');

                    opcao.value = material.id;
                    opcao.textContent =
                        `${material.nome} (Disponível: ${quantidade} ${material.unidade_medida})`;

                    campoMaterial.appendChild(opcao);
                });

            if (campoMaterial.options.length === 1) {
                campoMaterial.options[0].textContent =
                    'Nenhum material de consumo disponível';
            }

        } catch (erro) {
            console.error('Erro ao carregar materiais:', erro);

            campoMaterial.innerHTML =
                '<option value="">Erro ao carregar materiais</option>';

            exibirMensagem(
                'Não foi possível carregar os materiais. Verifique a conexão com o servidor.'
            );
        }
    }

    async function enviarSolicitacao(evento) {
        evento.preventDefault();

        mensagem.innerHTML = '';

        const materialId = Number(campoMaterial.value);
        const quantidade = Number(campoQuantidade.value);

        if (!materialId || !Number.isFinite(quantidade) || quantidade <= 0) {
            exibirMensagem('Selecione um material e informe uma quantidade válida.');
            return;
        }

        const dados = {
            data_necessaria: campoData.value,
            justificativa: campoJustificativa.value.trim(),
            itens: [
                {
                    material_id: materialId,
                    quantidade_solicitada: quantidade
                }
            ]
        };

        botaoEnviar.disabled = true;
        botaoEnviar.textContent = 'Enviando...';

        try {
            await API.requisicao('/servidor/solicitacoes-retirada', {
                method: 'POST',
                body: dados
            });

            exibirMensagem(
                'Solicitação registrada com sucesso!',
                'success'
            );

            formulario.reset();
            await carregarHistorico();

        } catch (erro) {
            console.error('Erro ao enviar solicitação:', erro);

            const erros = erro.dados?.errors;

            if (erros) {
                const primeiroErro = Object.values(erros).flat()[0];

                exibirMensagem(primeiroErro || 'Verifique os dados informados.');
            } else {
                exibirMensagem(
                    erro.dados?.message || 'Não foi possível registrar a solicitação.'
                );
            }

        } finally {
            botaoEnviar.disabled = false;
            botaoEnviar.textContent = 'Enviar Solicitação';
        }
    }

        const listaHistorico = document.getElementById(
        'listaHistoricoSolicitacoes'
    );

    const listaHistoricoMobile = document.getElementById(
        'listaHistoricoMobile'
    );

    function formatarData(data) {
        if (!data) return '—';

        const somenteData = String(data).substring(0, 10);
        const partes = somenteData.split('-');

        if (partes.length !== 3) return somenteData;

        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatarStatus(status) {
        const statusMap = {
            pendente: {
                texto: 'Pendente',
                classe: 'bg-warning bg-opacity-10 text-warning'
            },
            atendida: {
                texto: 'Atendida',
                classe: 'bg-success bg-opacity-10 text-success'
            },
            rejeitada: {
                texto: 'Rejeitada',
                classe: 'bg-danger bg-opacity-10 text-danger'
            }
        };

        return statusMap[String(status || '').toLowerCase()] || {
            texto: status || '—',
            classe: 'bg-secondary bg-opacity-10 text-secondary'
        };
    }

    function criarBadgeStatus(status) {
        const configuracao = formatarStatus(status);
        const badge = document.createElement('span');

        badge.className = `badge ${configuracao.classe} px-2 py-1`;
        badge.textContent = configuracao.texto;

        return badge;
    }

    function obterItensSolicitacao(solicitacao) {
        const itens = solicitacao.itens || [];

        if (!Array.isArray(itens) || itens.length === 0) {
            return [{
                nome: 'Solicitação de retirada',
                quantidade: '—'
            }];
        }

        return itens.map(item => ({
            nome:
                item.material?.nome ||
                item.material?.descricao ||
                item.bem_patrimonial?.descricao ||
                item.bemPatrimonial?.descricao ||
                item.nome_material ||
                'Material',
            quantidade:
                item.quantidade_solicitada ??
                item.quantidade ??
                '—'
        }));
    }

    function renderizarHistorico(solicitacoes) {
        listaHistorico.innerHTML = '';
        listaHistoricoMobile.innerHTML = '';

        if (!solicitacoes.length) {
            const linha = document.createElement('tr');
            const celula = document.createElement('td');

            celula.colSpan = 4;
            celula.className = 'text-center text-muted py-4';
            celula.textContent = 'Você ainda não possui solicitações.';

            linha.appendChild(celula);
            listaHistorico.appendChild(linha);

            const vazio = document.createElement('div');
            vazio.className = 'text-center text-muted py-4';
            vazio.textContent = 'Você ainda não possui solicitações.';

            listaHistoricoMobile.appendChild(vazio);
            return;
        }

        solicitacoes.forEach(solicitacao => {
            const data = formatarData(
                solicitacao.data_solicitacao ||
                solicitacao.created_at
            );

            const itens = obterItensSolicitacao(solicitacao);

            itens.forEach(item => {
                // Linha para desktop
                const linha = document.createElement('tr');

                const celulaNome = document.createElement('td');
                celulaNome.className = 'text-secondary fw-semibold';
                celulaNome.textContent = item.nome;

                const celulaQuantidade = document.createElement('td');
                celulaQuantidade.className = 'text-dark fw-bold';
                celulaQuantidade.textContent = item.quantidade;

                const celulaData = document.createElement('td');
                celulaData.className = 'text-muted';
                celulaData.textContent = data;

                const celulaStatus = document.createElement('td');
                celulaStatus.appendChild(
                    criarBadgeStatus(solicitacao.status)
                );

                linha.append(
                    celulaNome,
                    celulaQuantidade,
                    celulaData,
                    celulaStatus
                );

                listaHistorico.appendChild(linha);

                // Card para mobile
                const card = document.createElement('div');
                card.className = 'p-3 bg-light rounded-3';

                const cabecalho = document.createElement('div');
                cabecalho.className =
                    'd-flex justify-content-between align-items-start gap-2 mb-1';

                const nome = document.createElement('span');
                nome.className = 'fw-bold text-dark small';
                nome.textContent = item.nome;

                cabecalho.append(
                    nome,
                    criarBadgeStatus(solicitacao.status)
                );

                const detalhes = document.createElement('div');
                detalhes.className =
                    'd-flex justify-content-between text-muted';
                detalhes.style.fontSize = '0.75rem';

                const textoData = document.createElement('span');
                textoData.textContent = `Data: ${data}`;

                const textoQuantidade = document.createElement('span');
                textoQuantidade.textContent = `Qtd: ${item.quantidade}`;

                detalhes.append(textoData, textoQuantidade);
                card.append(cabecalho, detalhes);

                listaHistoricoMobile.appendChild(card);
            });
        });
    }

    async function carregarHistorico() {
        listaHistorico.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    Carregando histórico...
                </td>
            </tr>
        `;

        listaHistoricoMobile.textContent = 'Carregando histórico...';

        try {
            const resposta = await API.requisicao(
                '/servidor/solicitacoes-retirada'
            );

            const solicitacoes = resposta.data || [];

            renderizarHistorico(solicitacoes);

        } catch (erro) {
            console.error('Erro ao carregar histórico:', erro);

            listaHistorico.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-4">
                        Não foi possível carregar o histórico.
                    </td>
                </tr>
            `;

            listaHistoricoMobile.textContent =
                'Não foi possível carregar o histórico.';
        }
    }

    formulario.addEventListener('submit', enviarSolicitacao);

    carregarMateriais();
    carregarHistorico();

})();
