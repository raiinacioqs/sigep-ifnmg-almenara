document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('solicitacaoForm');

    if (form) {
        form.addEventListener('submit', async function (event) {
            // Evita o recarregamento padrão da página
            event.preventDefault();
            event.stopPropagation();

            // Validação visual nativa do formulário
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            // Capturar os valores inseridos pelo usuário
            const dadosSolicitacao = {
                nome: document.getElementById('nome').value.trim(),
                email: document.getElementById('email').value.trim(),
                siape: document.getElementById('siape').value.trim(),
                setor: document.getElementById('setor').value.trim(),
                perfil: document.getElementById('perfil').value
            };

            try {
                // Envio dos dados para a rota do back-end 
                const resposta = await fetch('/api/solicitacoes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dadosSolicitacao)
                });

                if (resposta.ok) {
                    const resultado = await resposta.json();
                    alert('Solicitação enviada com sucesso! O administrador irá analisar o seu pedido.');
                    form.reset();
                    form.classList.remove('was-validated');
                    
                    // Opcional: redirecionar para o login após o envio
                } else {
                    const erroServidor = await resposta.json();
                    alert(`Não foi possível concluir a solicitação: ${erroServidor.mensagem || 'Erro desconhecido.'}`);
                }

            } catch (erro) {
                console.error('Erro de conexão com o servidor:', erro);
                alert('O serviço está indisponível no momento. Tente novamente em alguns minutos.');
            }
        });
    }
});