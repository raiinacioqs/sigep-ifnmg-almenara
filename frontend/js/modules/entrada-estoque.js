
/**
 * SIGEP - Módulo de Entrada de Estoque
 * Carregamento de fornecedores e materiais pela API.
 */

document.addEventListener('DOMContentLoaded', inicializarModulo);


async function inicializarModulo() {
    const fornecedorSelect = document.getElementById('fornecedor_id');
    const materialSelect = document.getElementById('material_id');

    if (!fornecedorSelect || !materialSelect) {
        console.error('Elementos do formulário de entrada não encontrados.');
        return;
    }

    if (!window.SIGEPAPI) {
        console.error('SIGEPAPI não foi carregada.');
        exibirMensagem('API não disponível. Verifique os scripts.', 'danger');
        return;
    }

    configurarEventos();

    await Promise.all([
        carregarFornecedores(),
        carregarMateriais()
    ]);
}


/**
 * Busca todas as páginas de um endpoint paginado.
 */
async function buscarTodasPaginas(endpoint) {
    let pagina = 1;
    let registros = [];
    let ultimaPagina = 1;

    do {
        const separador = endpoint.includes('?') ? '&' : '?';

        const resposta = await SIGEPAPI.requisicao(
            `${endpoint}${separador}page=${pagina}`
        );

        if (!resposta || !Array.isArray(resposta.data)) {
            throw new Error('Formato de resposta da API inválido.');
        }

        registros = registros.concat(resposta.data);
        ultimaPagina = resposta.last_page || resposta.meta?.last_page || 1;

        pagina++;
    } while (pagina <= ultimaPagina);

    return registros;
}

/**
 * Carrega os fornecedores ativos.
 */
async function carregarFornecedores() {
    const select = document.getElementById('fornecedor_id');

    select.innerHTML = '<option value="">Carregando fornecedores...</option>';
    select.disabled = true;

    try {
        const fornecedores = await buscarTodasPaginas(
            '/fornecedores?status=ativo'
        );

        select.innerHTML = '<option value="">Selecione um fornecedor (opcional)</option>';

        fornecedores.forEach(fornecedor => {
            const option = document.createElement('option');

            option.value = fornecedor.id;
            option.textContent =
                fornecedor.nome_fantasia ||
                fornecedor.razao_social;

            select.appendChild(option);
        });

        if (fornecedores.length === 0) {
            select.innerHTML = '<option value="">Nenhum fornecedor ativo cadastrado</option>';
        }

    } catch (erro) {
        console.error('Erro ao carregar fornecedores:', erro);

        select.innerHTML = '<option value="">Erro ao carregar fornecedores</option>';

        exibirMensagem(
            'Não foi possível carregar os fornecedores. Verifique a conexão com a API.',
            'danger'
        );
    } finally {
        select.disabled = false;
    }
}

/**
 * Carrega somente materiais ativos de consumo.
 */
async function carregarMateriais() {
    const select = document.getElementById('material_id');

    select.innerHTML = '<option value="">Carregando materiais...</option>';
    select.disabled = true;

    try {
        const materiais = await buscarTodasPaginas(
            '/materiais?status=ativo'
        );

        const materiaisConsumo = materiais.filter(material =>
            material.categoria?.tipo === 'consumo'
        );

        select.innerHTML = '<option value="">Selecione um material</option>';

        materiaisConsumo.forEach(material => {
            const option = document.createElement('option');

            option.value = material.id;
            option.textContent =
                `${material.nome} (${material.unidade_medida})`;

            option.dataset.nome = material.nome;
            option.dataset.unidade = material.unidade_medida;
            option.dataset.estoque =
                material.estoque?.quantidade_atual ?? 0;

            select.appendChild(option);
        });

        if (materiaisConsumo.length === 0) {
            select.innerHTML = '<option value="">Nenhum material de consumo ativo cadastrado</option>';
        }

    } catch (erro) {
        console.error('Erro ao carregar materiais:', erro);

        select.innerHTML = '<option value="">Erro ao carregar materiais</option>';

        exibirMensagem(
            'Não foi possível carregar os materiais. Verifique a conexão com a API.',
            'danger'
        );
    } finally {
        select.disabled = false;
    }
}

/**
 * Exibe mensagens no formulário.
 */
function exibirMensagem(mensagem, tipo = 'info') {
    const elemento = document.getElementById('mensagemEntrada');

    if (!elemento) return;

    elemento.innerHTML = '';

    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.setAttribute('role', 'alert');
    alerta.textContent = mensagem;

    const fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.className = 'btn-close';
    fechar.setAttribute('data-bs-dismiss', 'alert');
    fechar.setAttribute('aria-label', 'Fechar');

    alerta.appendChild(fechar);
    elemento.appendChild(alerta);
}


/**
 * Itens adicionados à entrada atual.
 */
let itensEntrada = [];

/**
 * Configura os eventos dos campos e botões.
 */

function configurarEventos() {
    const botaoAdicionar = document.getElementById('btnAdicionarItem');
    const listaItens = document.getElementById('listaItensEntrada');
    const formulario = document.getElementById('formEntradaEstoque');

    botaoAdicionar.addEventListener('click', adicionarItem);

    listaItens.addEventListener('click', function (event) {
        const botao = event.target.closest('[data-remover-item]');

        if (!botao) return;

        removerItem(Number(botao.dataset.removerItem));
    });

    listaItens.addEventListener('change', function (event) {
        const campo = event.target.closest('[data-editar-item]');

        if (!campo) return;

        editarItem(
            Number(campo.dataset.indice),
            campo.dataset.editarItem,
            campo.value
        );
    });

    formulario.addEventListener('submit', function (event) {
        event.preventDefault();
        enviarEntrada();
    });
}


/**
 * Formata valores monetários.
 */
function formatarMoeda(valor) {
    return Number(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

/**
 * Adiciona um material à entrada.
 */

function adicionarItem() {
    const selectMaterial = document.getElementById('material_id');
    const campoQuantidade = document.getElementById('quantidade');
    const campoValor = document.getElementById('valor_unitario');

    const materialId = Number(selectMaterial.value);
    const quantidade = Number(campoQuantidade.value);

    const valorInformado = campoValor.value.trim();
    const valorUnitario = valorInformado === ''
        ? null
        : Number(valorInformado);

    const opcao = selectMaterial.selectedOptions[0];

    if (!materialId || !opcao || !opcao.dataset.nome) {
        exibirMensagem('Selecione um material válido.', 'warning');
        return;
    }

    if (!Number.isFinite(quantidade) || quantidade <= 0) {
        exibirMensagem('Informe uma quantidade maior que zero.', 'warning');
        campoQuantidade.focus();
        return;
    }

    if (
        valorUnitario !== null &&
        (!Number.isFinite(valorUnitario) || valorUnitario < 0)
    ) {
        exibirMensagem('Informe um valor unitário válido.', 'warning');
        campoValor.focus();
        return;
    }

    const existente = itensEntrada.find(
        item => item.material_id === materialId
    );

    if (existente) {
        if (existente.valor_unitario !== valorUnitario) {
            exibirMensagem(
                'Esse material já está na lista com outro valor unitário. Edite o item existente.',
                'warning'
            );
            return;
        }

        existente.quantidade += quantidade;

        exibirMensagem(
            'Quantidade somada ao material já existente.',
            'success'
        );
    } else {
        itensEntrada.push({
            material_id: materialId,
            nome: opcao.dataset.nome,
            unidade: opcao.dataset.unidade,
            quantidade: quantidade,
            valor_unitario: valorUnitario
        });

        exibirMensagem('Material adicionado à entrada.', 'success');
    }

    atualizarListaItens();

    campoQuantidade.value = '';
    campoValor.value = '';
    selectMaterial.value = '';
}


/**
 * Remove um material da lista.
 */
function removerItem(indice) {
    if (indice < 0 || indice >= itensEntrada.length) return;

    itensEntrada.splice(indice, 1);

    atualizarListaItens();
}

/**
 * Atualiza a tabela e os valores totais.
 */

function atualizarListaItens() {
    const tbody = document.getElementById('listaItensEntrada');
    const totalElemento = document.getElementById('totalEntrada');

    tbody.replaceChildren();

    if (itensEntrada.length === 0) {
        const linha = document.createElement('tr');
        const celula = document.createElement('td');

        celula.colSpan = 5;
        celula.className = 'text-center text-muted py-3';
        celula.textContent = 'Nenhum material adicionado.';

        linha.appendChild(celula);
        tbody.appendChild(linha);
    }

    let total = 0;

    itensEntrada.forEach((item, indice) => {
        const subtotal = item.valor_unitario === null
            ? 0
            : item.quantidade * item.valor_unitario;

        total += subtotal;

        const linha = document.createElement('tr');

        const nome = document.createElement('td');
        nome.textContent = `${item.nome} (${item.unidade})`;

        const quantidade = document.createElement('td');
        const campoQuantidade = document.createElement('input');

        campoQuantidade.type = 'number';
        campoQuantidade.className = 'form-control form-control-sm';
        campoQuantidade.min = '0.01';
        campoQuantidade.step = 'any';
        campoQuantidade.value = item.quantidade;
        campoQuantidade.dataset.editarItem = 'quantidade';
        campoQuantidade.dataset.indice = indice;
        campoQuantidade.setAttribute('aria-label', 'Quantidade');

        quantidade.appendChild(campoQuantidade);

        const valor = document.createElement('td');
        const campoValor = document.createElement('input');

        campoValor.type = 'number';
        campoValor.className = 'form-control form-control-sm';
        campoValor.min = '0';
        campoValor.step = '0.01';
        campoValor.placeholder = 'Não informado';
        campoValor.value = item.valor_unitario ?? '';
        campoValor.dataset.editarItem = 'valor_unitario';
        campoValor.dataset.indice = indice;
        campoValor.setAttribute('aria-label', 'Valor unitário');

        valor.appendChild(campoValor);

        const subtotalCelula = document.createElement('td');

        subtotalCelula.textContent = item.valor_unitario === null
            ? 'Não calculado'
            : formatarMoeda(subtotal);

        const acao = document.createElement('td');
        acao.className = 'text-center';

        const botaoRemover = document.createElement('button');

        botaoRemover.type = 'button';
        botaoRemover.className = 'btn btn-sm btn-outline-danger';
        botaoRemover.dataset.removerItem = indice;
        botaoRemover.setAttribute('aria-label', 'Remover material');
        botaoRemover.innerHTML = '<i class="bi bi-trash"></i>';

        acao.appendChild(botaoRemover);

        linha.append(nome, quantidade, valor, subtotalCelula, acao);
        tbody.appendChild(linha);
    });

    totalElemento.textContent = formatarMoeda(total);
}

let envioEmAndamento = false;

/**
 * Valida e envia a entrada para o Laravel.
 */
async function enviarEntrada() {

    if (envioEmAndamento) {
        return;
    }

    const formulario = document.getElementById('formEntradaEstoque');
    const botaoSalvar = document.getElementById('btnSalvarEntrada');
    const arquivo = document.getElementById('arquivo_nota_fiscal');

    if (itensEntrada.length === 0) {
        exibirMensagem('Adicione pelo menos um material à entrada.', 'warning');
        return;
    }

    if (!formulario.reportValidity()) {
        return;
    }

    // Validação adicional do arquivo.
    
    if (arquivo.files.length > 0) {
        const pdf = arquivo.files[0];
        const extensaoValida = /\.pdf$/i.test(pdf.name);

        if (!extensaoValida || pdf.type !== 'application/pdf') {
            exibirMensagem(
                'Selecione um arquivo PDF válido para a nota fiscal.',
                'warning'
            );
            return;
        }

        if (pdf.size === 0) {
            exibirMensagem('O arquivo PDF está vazio.', 'warning');
            return;
        }

        if (pdf.size > 10 * 1024 * 1024) {
            exibirMensagem(
                'O PDF não pode ultrapassar 10 MB.',
                'warning'
            );
            return;
        }
    }


    const dados = new FormData(formulario);

    // Remove qualquer item com índice de formulário e monta a estrutura esperada.
    itensEntrada.forEach((item, indice) => {
        dados.append(`itens[${indice}][material_id]`, item.material_id);
        dados.append(`itens[${indice}][quantidade]`, item.quantidade);

        if (item.valor_unitario !== null) {
            dados.append(
                `itens[${indice}][valor_unitario]`,
                item.valor_unitario
            );
        }
    });

    envioEmAndamento = true;

    botaoSalvar.disabled = true;
    botaoSalvar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span> Salvando...';

    try {
        const resposta = await SIGEPAPI.requisicao('/entradas-estoque', {
            method: 'POST',
            body: dados
        });

        exibirMensagem(
            resposta.message || 'Entrada de estoque registrada com sucesso!',
            'success'
        );

        formulario.reset();
        itensEntrada = [];
        atualizarListaItens();

    } catch (erro) {
        console.error('Erro ao registrar entrada:', erro);

        exibirErros(erro);

    } finally {
        envioEmAndamento = false;
        botaoSalvar.disabled = false;
        botaoSalvar.innerHTML =
            '<i class="bi bi-save me-2"></i> Salvar Entrada';
    }

}


/**
 * Trata erros retornados pela API Laravel.
 */
function exibirErros(erro) {
    console.error('Detalhes do erro:', erro);

    // Falha de conexão ou servidor indisponível.
    if (!erro.status && !erro.dados) {
        exibirMensagem(
            'Não foi possível conectar ao servidor. Verifique se o Laravel está em execução.',
            'danger'
        );
        return;
    }

    // Sessão expirada ou token inválido.
    if (erro.status === 401) {
        exibirMensagem(
            'Sua sessão expirou. Faça login novamente.',
            'warning'
        );
        return;
    }

    // Usuário sem permissão.
    if (erro.status === 403) {
        exibirMensagem(
            'Você não possui permissão para registrar entradas de estoque.',
            'danger'
        );
        return;
    }

    // Erros de validação do Laravel.
    if (erro.status === 422 && erro.dados?.errors) {
        const mensagens = Object.values(erro.dados.errors).flat();

        exibirMensagem(mensagens.join(' | '), 'warning');
        return;
    }

    // Arquivo muito grande.
    if (erro.status === 413) {
        exibirMensagem(
            'O arquivo enviado ultrapassa o limite permitido pelo servidor.',
            'warning'
        );
        return;
    }

    // Erros internos do servidor.
    if (erro.status >= 500) {
        exibirMensagem(
            'Ocorreu um erro interno no servidor. Tente novamente mais tarde.',
            'danger'
        );
        return;
    }

    exibirMensagem(
        erro.dados?.message ||
        erro.message ||
        'Não foi possível registrar a entrada.',
        'danger'
    );
}

    /**
     * Atualiza quantidade ou valor unitário de um item.
     */
    function editarItem(indice, campo, valor) {
        const item = itensEntrada[indice];

        if (!item) return;

        const valorTexto = String(valor ?? '').trim();

        // Permite deixar o valor unitário em branco.
        if (campo === 'valor_unitario' && valorTexto === '') {
            item.valor_unitario = null;
            atualizarListaItens();
            return;
        }

        const numero = Number(valorTexto);

        if (!Number.isFinite(numero)) {
            exibirMensagem(
                'Informe um valor numérico válido.',
                'warning'
            );

            atualizarListaItens();
            return;
        }

        if (campo === 'quantidade') {
            if (numero <= 0) {
                exibirMensagem(
                    'A quantidade deve ser maior que zero.',
                    'warning'
                );

                atualizarListaItens();
                return;
            }

            item.quantidade = numero;
        } else if (campo === 'valor_unitario') {
            if (numero < 0) {
                exibirMensagem(
                    'O valor unitário não pode ser negativo.',
                    'warning'
                );

                atualizarListaItens();
                return;
            }

            item.valor_unitario = numero;
        }

        atualizarListaItens();
}
