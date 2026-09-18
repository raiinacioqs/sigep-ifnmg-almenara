document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Busca e Filtro em Tempo Real nas Tabelas
    const searchInputs = document.querySelectorAll('input[placeholder*="Buscar"], input[placeholder*="Pesquisar"]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function (e) {
            const term = e.target.value.toLowerCase();
            const container = input.closest('.card, main');
            if (!container) return;
            
            // Filtra linhas de tabelas
            const rows = container.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });

            // Filtra cards mobile (caso existam)
            const mobileCards = container.querySelectorAll('.d-md-none .card');
            mobileCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });

    // 2. Feedback ao Enviar Formulários (Cadastro, Retirada, Configurações)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Operação realizada com sucesso! Os dados foram salvos no SIGEP - Campus Almenara.');
            form.reset();
        });
    });

    // 3. Confirmação para Ações Críticas (Excluir, Rejeitar)
    const actionButtons = document.querySelectorAll('button, .btn');
    actionButtons.forEach(btn => {
        const text = btn.textContent.trim().toLowerCase();
        if (text.includes('excluir') || text.includes('rejeitar')) {
            btn.addEventListener('click', function (e) {
                if (!confirm('Atenção: Tem certeza que deseja executar esta ação permanente?')) {
                    e.preventDefault();
                }
            });
        } else if (text.includes('aprovar') || text.includes('salvar') || text.includes('executar backup')) {
            btn.addEventListener('click', function (e) {
                if (btn.type !== 'submit' && !btn.closest('form')) {
                    alert('Ação processada com sucesso no sistema!');
                }
            });
        }
    });

    // 4. Interatividade nas Pílulas de Filtro (Mobile)
    const filterPills = document.querySelectorAll('.d-md-none .rounded-pill');
    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => {
                p.classList.remove('btn-success', 'btn-dark', 'text-white');
                p.classList.add('btn-light', 'border', 'text-secondary');
            });
            this.classList.remove('btn-light', 'border', 'text-secondary');
            this.classList.add('btn-success', 'text-white');
        });
    });

});