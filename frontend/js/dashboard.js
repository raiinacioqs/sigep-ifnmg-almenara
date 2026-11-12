document.addEventListener('DOMContentLoaded', function () {

    // 1. Busca e filtro em tempo real nas tabelas
    const searchInputs = document.querySelectorAll(
        'input[placeholder*="Buscar"], input[placeholder*="Pesquisar"]'
    );

    searchInputs.forEach(input => {
        input.addEventListener('input', function () {
            const term = input.value.toLowerCase();
            const container = input.closest('.card, main');

            if (!container) return;

            const rows = container.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });

            const mobileCards = container.querySelectorAll('.d-md-none .card');

            mobileCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });

    // 2. Confirmação para ações críticas
    const actionButtons = document.querySelectorAll('button, .btn');

    actionButtons.forEach(btn => {
        const text = btn.textContent.trim().toLowerCase();

        if (text.includes('excluir') || text.includes('rejeitar')) {
            btn.addEventListener('click', function (evento) {
                const confirmado = confirm(
                    'Atenção: tem certeza de que deseja executar esta ação?'
                );

                if (!confirmado) {
                    evento.preventDefault();
                    evento.stopImmediatePropagation();
                }
            });
        }
    });

    // 3. Interatividade nas pílulas de filtro mobile
    const filterPills = document.querySelectorAll(
        '.d-md-none .rounded-pill'
    );

    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => {
                p.classList.remove(
                    'btn-success',
                    'btn-dark',
                    'text-white'
                );

                p.classList.add(
                    'btn-light',
                    'border',
                    'text-secondary'
                );
            });

            this.classList.remove(
                'btn-light',
                'border',
                'text-secondary'
            );

            this.classList.add('btn-success', 'text-white');
        });
    });

    // 4. Identificação da página ativa na navegação mobile
    const paginaAtual = window.location.pathname.split('/').pop();

    const linksMobile = document.querySelectorAll('.mobile-bottom-nav a');

    linksMobile.forEach(link => {
        const href = link.getAttribute('href');

        if (!href) return;

        const paginaLink = href.split('/').pop().split('?')[0];

        if (paginaLink === paginaAtual) {
            link.classList.add('active');
            link.style.backgroundColor = '#14532d';
            link.style.opacity = '1';
        } else {
            link.classList.remove('active');
            link.style.backgroundColor = 'transparent';
            link.style.opacity = '0.75';
        }
    });

});