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

    formulario.addEventListener('submit', enviarSolicitacao);

    carregarMateriais();

})();
