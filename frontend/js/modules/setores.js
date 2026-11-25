(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        let paginaAtual = 1;
        let setorEmEdicao = null;
        let statusSetorEmEdicao = 'ativo';

        function renderizarPaginacao(meta) {
            const atual = meta?.current_page || 1;
            const ultima = meta?.last_page || 1;

            const anteriorDesktop = document.getElementById('btnPaginaAnteriorSetor');
            const proximoDesktop = document.getElementById('btnPaginaProximaSetor');
            const paginaAtualDesktop = document.getElementById('paginaAtualSetor');

            const anteriorMobile = document.getElementById('btnPaginaAnteriorMobileSetor');
            const proximoMobile = document.getElementById('btnPaginaProximaMobileSetor');
            const resumoMobile = document.getElementById('resumoPaginacaoMobileSetor');

            if (paginaAtualDesktop) {
                paginaAtualDesktop.textContent = `${atual} de ${ultima}`;
            }

            if (anteriorDesktop) {
                anteriorDesktop.disabled = atual <= 1;
                anteriorDesktop.onclick = () => {
                    if (paginaAtual > 1) {
                        paginaAtual = atual - 1;
                        carregarSetores();
                    }
                };
            }

            if (proximoDesktop) {
                proximoDesktop.disabled = atual >= ultima;
                proximoDesktop.onclick = () => {
                    if (paginaAtual < ultima) {
                        paginaAtual = atual + 1;
                        carregarSetores();
                    }
                };
            }

            if (anteriorMobile) {
                anteriorMobile.disabled = atual <= 1;
                anteriorMobile.onclick = () => {
                    if (paginaAtual > 1) {
                        paginaAtual = atual - 1;
                        carregarSetores();
                    }
                };
            }

            if (proximoMobile) {
                proximoMobile.disabled = atual >= ultima;
                proximoMobile.onclick = () => {
                    if (paginaAtual < ultima) {
                        paginaAtual = atual + 1;
                        carregarSetores();
                    }
                };
            }

            if (resumoMobile) {
                resumoMobile.textContent = `${atual} de ${ultima}`;
            }
        }

        async function carregarSetores(pagina = paginaAtual) {

            paginaAtual = pagina;

            try {
                const campoBusca = document.getElementById('buscaSetor');
                const filtroStatus = document.getElementById('filtroStatusSetor');

                const parametros = new URLSearchParams();
                parametros.set('page', paginaAtual);

                if (campoBusca?.value.trim()) {
                    parametros.set('busca', campoBusca.value.trim());
                }

                if (filtroStatus?.value) {
                    parametros.set('status', filtroStatus.value);
                }

                const query = parametros.toString();
                const endpoint = query ? `/setores?${query}` : '/setores';

                const resposta = await SIGEPAPI.requisicao(endpoint);
                const setores = resposta.data || [];

                const listaDesktop = document.getElementById('listaSetores');
                const listaMobile = document.getElementById('listaSetoresMobile');
                const paginacaoDesktop = document.getElementById('paginacaoSetores');

                const escapar = (valor) => String(valor ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                if (listaDesktop) {
                    if (setores.length === 0) {
                        listaDesktop.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhum setor encontrado.
                                </td>
                            </tr>
                        `;
                    } else {
                        listaDesktop.innerHTML = setores.map((setor) => `
                            <tr>
                                <td>${setor.id}</td>
                                <td>
                                    <div class="fw-semibold">${escapar(setor.nome)}</div>
                                    <small class="text-muted">${escapar(setor.sigla)}</small>
                                </td>
                                <td>${escapar(setor.localizacao || '—')}</td>
                                <td>—</td>
                                <td>—</td>
                                <td>
                                    <span class="badge ${setor.status === 'ativo'
                                        ? 'bg-success-subtle text-success'
                                        : 'bg-secondary-subtle text-secondary'}">
                                        ${setor.status === 'ativo' ? 'Ativo' : 'Inativo'}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-editar-setor"
                                            data-id="${setor.id}"
                                            title="Editar setor"
                                            aria-label="Editar setor">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm ${setor.status === 'ativo' ? 'btn-outline-danger btn-inativar-setor' : 'btn-outline-success btn-inativar-setor'}"
                                            data-id="${setor.id}"
                                            title="${setor.status === 'ativo' ? 'Inativar setor' : 'Ativar setor'}"
                                            aria-label="${setor.status === 'ativo' ? 'Inativar setor' : 'Ativar setor'}">
                                            <i class="bi ${setor.status === 'ativo' ? 'bi-person-x' : 'bi-person-check'}"></i>
                                            <span class="d-md-none ms-1">${setor.status === 'ativo' ? 'Inativar' : 'Ativar'}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    }
                }

                if (listaMobile) {
                    if (setores.length === 0) {
                        listaMobile.innerHTML = `
                            <div class="card border-0 shadow-sm p-3 rounded-3">
                                <div class="text-center text-muted py-3">
                                    Nenhum setor encontrado.
                                </div>
                            </div>
                        `;
                    } else {
                        listaMobile.innerHTML = setores.map((setor) => `
                            <div class="card border-0 shadow-sm p-3 rounded-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <h6 class="fw-semibold mb-1">${escapar(setor.nome)}</h6>
                                        <small class="text-muted">${escapar(setor.sigla)}</small>
                                    </div>
                                    <span class="badge ${setor.status === 'ativo'
                                        ? 'bg-success-subtle text-success'
                                        : 'bg-secondary-subtle text-secondary'}">
                                        ${setor.status === 'ativo' ? 'Ativo' : 'Inativo'}
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    ${escapar(setor.localizacao || 'Localização não informada')}
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-editar-setor"
                                        data-id="${setor.id}"
                                        title="Editar setor"
                                        aria-label="Editar setor">
                                        <i class="bi bi-pencil"></i>
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger btn-inativar-setor"
                                        data-id="${setor.id}"
                                        title="Inativar setor"
                                        aria-label="Inativar setor">
                                        <i class="bi bi-person-x"></i>
                                        Inativar
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                }

                const meta = resposta.meta || {};
                renderizarPaginacao(meta);

                if (paginacaoDesktop) {
                    const atual = meta.current_page || 1;
                    const ultima = meta.last_page || 1;

                    paginacaoDesktop.innerHTML = `
                        <li class="page-item ${atual <= 1 ? 'disabled' : ''}">
                            <button class="page-link" data-pagina="${atual - 1}"
                                ${atual <= 1 ? 'disabled' : ''}>
                                Anterior
                            </button>
                        </li>
                        <li class="page-item active">
                            <span class="page-link">${atual} de ${ultima}</span>
                        </li>
                        <li class="page-item ${atual >= ultima ? 'disabled' : ''}">
                            <button class="page-link" data-pagina="${atual + 1}"
                                ${atual >= ultima ? 'disabled' : ''}>
                                Próximo
                            </button>
                        </li>
                    `;

                    paginacaoDesktop.querySelectorAll('button[data-pagina]').forEach((botao) => {
                        botao.addEventListener('click', () => {
                            const pagina = Number(botao.dataset.pagina);

                            if (pagina > 0) {
                                carregarSetores(pagina);
                            }
                        });
                    });
                }

                const atual = meta.current_page || 1;

                const resumo = `Exibindo ${meta.from ?? 0}-${meta.to ?? 0} de ${meta.total ?? 0} setores`;

                const resumoDesktop = document.getElementById('resumoSetores');
                const resumoMobile = document.getElementById('resumoPaginacaoMobileSetor');

                if (resumoDesktop) {
                    resumoDesktop.textContent = resumo;
                }

                if (resumoMobile) {
                    resumoMobile.textContent = resumo;
                }

            } catch (erro) {
                console.error('Erro ao carregar setores:', erro);
            }
        }

                let temporizadorBuscaSetor;

                const campoBusca = document.getElementById('buscaSetor');
                const filtroStatus = document.getElementById('filtroStatusSetor');

                if (campoBusca) {
                    campoBusca.addEventListener('input', () => {
                        clearTimeout(temporizadorBuscaSetor);

                        temporizadorBuscaSetor = setTimeout(() => {
                            carregarSetores(1);
                        }, 400);
                    });
                }

                if (filtroStatus) {
                    filtroStatus.addEventListener('change', () => {
                        carregarSetores(1);
                    });
                }

        const botaoNovoSetor = document.getElementById('btnNovoSetor');
        const elementoModalSetor = document.getElementById('modalSetor');

        if (botaoNovoSetor && elementoModalSetor) {
            const modalSetor = new bootstrap.Modal(elementoModalSetor);

            botaoNovoSetor.addEventListener('click', () => {
                modalSetor.show();
            });

            elementoModalSetor.addEventListener('show.bs.modal', () => {
            const formulario = document.getElementById('formSetor');

            if (formulario) {
                formulario.reset();
            }

            setorEmEdicao = null;

            const mensagem = document.getElementById('mensagemFormularioSetor');

            if (mensagem) {
                mensagem.className = 'alert d-none';
                mensagem.textContent = '';
            }
        });
        }

        const formularioSetor = document.getElementById('formSetor');
        const mensagemFormulario = document.getElementById('mensagemFormularioSetor');

        if (formularioSetor && mensagemFormulario) {
                formularioSetor.addEventListener('submit', async (evento) => {
                evento.preventDefault();

                const nome = document.getElementById('nomeSetor').value.trim();
                const sigla = document.getElementById('siglaSetor').value.trim();
                const localizacao = document.getElementById('localizacaoSetor').value.trim();

                mensagemFormulario.className = 'alert alert-info';
                mensagemFormulario.textContent = 'Preparando cadastro...';

                try {
                    const endpointSetor = setorEmEdicao
                        ? `/setores/${setorEmEdicao}`
                        : '/setores';
                        const metodoSetor = setorEmEdicao ? 'PUT' : 'POST';

                    console.log('Dados da edição:', {
                        id: setorEmEdicao,
                        metodo: metodoSetor,
                        nome,
                        sigla,
                        localizacao
                    });

                    const resposta = await SIGEPAPI.requisicao(endpointSetor, {
                        method: metodoSetor,
                       body: {
                            nome,
                            sigla,
                            localizacao: localizacao || null,
                            status: setorEmEdicao ? statusSetorEmEdicao : 'ativo'
                        }
                    });

                    mensagemFormulario.className = 'alert alert-success';
                    mensagemFormulario.textContent = resposta.message || 'Setor cadastrado com sucesso.';

                    await carregarSetores();
                    formularioSetor.reset();

                    const modalElemento = document.getElementById('modalSetor');
                    const instanciaModal = bootstrap.Modal.getInstance(modalElemento);
                    instanciaModal?.hide();

                } catch (erro) {
                    mensagemFormulario.className = 'alert alert-danger';

                    const errosValidacao = erro.dados?.errors;

                    if (errosValidacao) {
                        mensagemFormulario.textContent =
                            Object.values(errosValidacao).flat().join(' ');
                    } else {
                        mensagemFormulario.textContent =
                            erro.message || 'Não foi possível cadastrar o setor.';
                    }
                }
            });
        }

        document.addEventListener('click', async (evento) => {
            const botaoEditar = evento.target.closest('.btn-editar-setor');

            if (!botaoEditar) return;

            const id = botaoEditar.dataset.id;

            try {
                const resposta = await SIGEPAPI.requisicao(`/setores/${id}`);
                const setor = resposta.data || resposta;

                const modal = document.getElementById('modalSetor');
                bootstrap.Modal.getOrCreateInstance(modal).show();

                setorEmEdicao = Number(id);
                statusSetorEmEdicao = setor.status;

                document.getElementById('nomeSetor').value = setor.nome || '';
                document.getElementById('siglaSetor').value = setor.sigla || '';
                document.getElementById('localizacaoSetor').value = setor.localizacao || '';

                const mensagem = document.getElementById('mensagemFormularioSetor');

                if (mensagem) {
                    mensagem.className = 'alert d-none';
                    mensagem.textContent = '';
                }

            } catch (erro) {
                console.error('Erro ao carregar setor para edição:', erro);
                alert('Não foi possível carregar os dados do setor.');
            }
        });


        document.addEventListener('click', async (evento) => {
        const botaoInativar = evento.target.closest('.btn-inativar-setor');

        if (!botaoInativar) return;

        const id = botaoInativar.dataset.id;

        try {
            const resposta = await SIGEPAPI.requisicao(`/setores/${id}`);
            const setor = resposta.data || resposta;

            if (setor.status !== 'ativo') {
                const confirmarAtivacao = confirm(
                    `Deseja realmente ativar o setor "${setor.nome}"?`
                );

                if (!confirmarAtivacao) return;

                await SIGEPAPI.requisicao(`/setores/${id}`, {
                    method: 'PUT',
                    body: {
                        nome: setor.nome,
                        sigla: setor.sigla,
                        localizacao: setor.localizacao,
                        status: 'ativo'
                    }
                });

                alert('Setor ativado com sucesso.');
                await carregarSetores();
                return;
            }

            const confirmar = confirm(
                `Deseja realmente inativar o setor "${setor.nome}"?`
            );

            if (!confirmar) return;

            await SIGEPAPI.requisicao(`/setores/${id}`, {
                method: 'DELETE'
            });

            alert('Setor inativado com sucesso.');
            await carregarSetores();

        } catch (erro) {
            console.error('Erro ao alterar situação do setor:', erro);
            alert(erro.message || 'Não foi possível alterar a situação do setor.');
        }
    });

        carregarSetores();
    });
})();