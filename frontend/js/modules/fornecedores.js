
(() => {
    'use strict';

    let fornecedores = [];
    let modalFornecedor;

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        const elementoModal = document.getElementById('modalFornecedor');

        modalFornecedor = bootstrap.Modal.getOrCreateInstance(elementoModal);

        document.getElementById('btnNovoFornecedor')
            .addEventListener('click', abrirCadastro);

        document.getElementById('formFornecedor')
            .addEventListener('submit', salvarFornecedor);

        document.getElementById('buscaFornecedor')
            .addEventListener('input', renderizarFornecedores);

        document.getElementById('filtroStatus')
            .addEventListener('change', renderizarFornecedores);

        document.getElementById('cnpj')
            .addEventListener('input', aplicarMascaraCnpj);

        await carregarFornecedores();
    }

    async function carregarFornecedores() {
        try {
            const resposta = await SIGEPAPI.requisicao('/fornecedores');

            fornecedores = resposta.data || [];

            renderizarFornecedores();
            atualizarIndicadores();
        } catch (erro) {
            console.error('Erro ao carregar fornecedores:', erro);

            document.getElementById('tabelaFornecedores').innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        Não foi possível carregar os fornecedores.
                    </td>
                </tr>
            `;

            document.getElementById('listaFornecedoresMobile').innerHTML = `
                <div class="text-center text-danger py-4">
                    Não foi possível carregar os fornecedores.
                </div>
            `;
        }
    }

    function renderizarFornecedores() {
        const busca = document.getElementById('buscaFornecedor')
            .value.trim().toLowerCase();

        const status = document.getElementById('filtroStatus').value;

        const filtrados = fornecedores.filter(fornecedor => {
            const texto = [
                fornecedor.razao_social,
                fornecedor.nome_fantasia,
                fornecedor.cnpj
            ].join(' ').toLowerCase();

            const correspondeBusca = texto.includes(busca);
            const correspondeStatus = !status || fornecedor.status === status;

            return correspondeBusca && correspondeStatus;
        });

        renderizarTabela(filtrados);
        renderizarMobile(filtrados);
    }

    function renderizarTabela(lista) {
        const tbody = document.getElementById('tabelaFornecedores');

        if (lista.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Nenhum fornecedor encontrado.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = lista.map(fornecedor => `
            <tr>
                <td class="px-3 fw-semibold">
                    ${escaparHTML(fornecedor.razao_social)}
                </td>
                <td>${escaparHTML(fornecedor.nome_fantasia || '-')}</td>
                <td>${escaparHTML(fornecedor.cnpj)}</td>
                <td>${escaparHTML(fornecedor.telefone || '-')}</td>
                <td>${badgeStatus(fornecedor.status)}</td>
                <td class="text-end px-3">
                    <button class="btn btn-sm btn-outline-primary me-1"
                        onclick="editarFornecedor(${fornecedor.id})"
                        title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>

                    ${fornecedor.status === 'ativo' ? `
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="inativarFornecedor(${fornecedor.id})"
                            title="Inativar">
                            <i class="bi bi-person-x"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `).join('');
    }

    function renderizarMobile(lista) {
        const container = document.getElementById('listaFornecedoresMobile');

        if (lista.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    Nenhum fornecedor encontrado.
                </div>
            `;
            return;
        }

        container.innerHTML = lista.map(fornecedor => `
            <div class="border rounded-3 p-3 mb-3 bg-white">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-semibold">
                        ${escaparHTML(fornecedor.razao_social)}
                    </div>
                    ${badgeStatus(fornecedor.status)}
                </div>

                <div class="small text-muted mt-2">
                    ${escaparHTML(fornecedor.nome_fantasia || 'Sem nome fantasia')}
                </div>

                <div class="small mt-2">
                    <strong>CNPJ:</strong> ${escaparHTML(fornecedor.cnpj)}
                </div>

                <div class="small">
                    <strong>Telefone:</strong> ${escaparHTML(fornecedor.telefone || '-')}
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-primary"
                        onclick="editarFornecedor(${fornecedor.id})">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </button>

                    ${fornecedor.status === 'ativo' ? `
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="inativarFornecedor(${fornecedor.id})">
                            <i class="bi bi-person-x me-1"></i>Inativar
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    function atualizarIndicadores() {
        document.getElementById('totalFornecedores').textContent =
            fornecedores.length;

        document.getElementById('fornecedoresAtivos').textContent =
            fornecedores.filter(f => f.status === 'ativo').length;

        document.getElementById('fornecedoresInativos').textContent =
            fornecedores.filter(f => f.status === 'inativo').length;
    }

    function abrirCadastro() {
        document.getElementById('formFornecedor').reset();
        document.getElementById('fornecedorId').value = '';
        document.getElementById('tituloModalFornecedor').textContent =
            'Novo Fornecedor';
        document.getElementById('statusFornecedor').value = 'ativo';

        modalFornecedor.show();
    }

    window.editarFornecedor = function (id) {
        const fornecedor = fornecedores.find(f => f.id === id);

        if (!fornecedor) return;

        document.getElementById('formFornecedor').reset();

        document.getElementById('fornecedorId').value = fornecedor.id;
        document.getElementById('razaoSocial').value = fornecedor.razao_social || '';
        document.getElementById('nomeFantasia').value = fornecedor.nome_fantasia || '';
        document.getElementById('cnpj').value = fornecedor.cnpj || '';
        document.getElementById('emailFornecedor').value = fornecedor.email || '';
        document.getElementById('telefoneFornecedor').value = fornecedor.telefone || '';
        document.getElementById('enderecoFornecedor').value = fornecedor.endereco || '';
        document.getElementById('statusFornecedor').value = fornecedor.status || 'ativo';

        document.getElementById('tituloModalFornecedor').textContent =
            'Editar Fornecedor';

        modalFornecedor.show();
    };

    async function salvarFornecedor(event) {
        event.preventDefault();

        const id = document.getElementById('fornecedorId').value;

        const dados = {
            razao_social: document.getElementById('razaoSocial').value.trim(),
            nome_fantasia: document.getElementById('nomeFantasia').value.trim() || null,
            cnpj: document.getElementById('cnpj').value.trim(),
            email: document.getElementById('emailFornecedor').value.trim() || null,
            telefone: document.getElementById('telefoneFornecedor').value.trim() || null,
            endereco: document.getElementById('enderecoFornecedor').value.trim() || null,
            status: document.getElementById('statusFornecedor').value
        };

        const botao = document.getElementById('btnSalvarFornecedor');
        botao.disabled = true;

        try {
            if (id) {
                await SIGEPAPI.requisicao(`/fornecedores/${id}`, {
                    method: 'PUT',
                    body: dados
                });
            } else {
                await SIGEPAPI.requisicao('/fornecedores', {
                    method: 'POST',
                    body: dados
                });
            }

            modalFornecedor.hide();
            await carregarFornecedores();

            alert(id
                ? 'Fornecedor atualizado com sucesso!'
                : 'Fornecedor cadastrado com sucesso!');
        } catch (erro) {
            console.error('Erro ao salvar fornecedor:', erro);

            const mensagem = erro.dados?.message
                || 'Não foi possível salvar o fornecedor. Verifique os dados.';

            alert(mensagem);
        } finally {
            botao.disabled = false;
        }
    }

    window.inativarFornecedor = async function (id) {
        const fornecedor = fornecedores.find(f => f.id === id);

        if (!fornecedor) return;

        const confirmar = confirm(
            `Deseja realmente inativar o fornecedor "${fornecedor.razao_social}"?`
        );

        if (!confirmar) return;

        try {
            await SIGEPAPI.requisicao(`/fornecedores/${id}`, {
                method: 'DELETE'
            });

            await carregarFornecedores();

            alert('Fornecedor inativado com sucesso!');
        } catch (erro) {
            console.error('Erro ao inativar fornecedor:', erro);
            alert('Não foi possível inativar o fornecedor.');
        }
    };

    function badgeStatus(status) {
        return status === 'ativo'
            ? '<span class="badge bg-success-subtle text-success">Ativo</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">Inativo</span>';
    }

    function aplicarMascaraCnpj(event) {
        let valor = event.target.value.replace(/\D/g, '').slice(0, 14);

        valor = valor.replace(/^(\d{2})(\d)/, '$1.$2');
        valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        valor = valor.replace(/\.(\d{3})(\d)/, '.$1/$2');
        valor = valor.replace(/(\d{4})(\d)/, '$1-$2');

        event.target.value = valor;
    }

    function escaparHTML(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, caractere => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[caractere]);
    }
})();
