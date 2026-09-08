document.addEventListener("DOMContentLoaded", function () {
    const solicitacaoForm = document.getElementById("solicitacaoForm");

    if (solicitacaoForm) {
        solicitacaoForm.addEventListener("submit", function (event) {
            event.preventDefault(); // Evita o recarregamento padrão da página

            // Aqui você pode capturar os valores se precisar futuramente:
            // const nome = document.getElementById("nome").value;

            alert("Solicitação enviada com sucesso! Redirecionando para a tela de login...");
            
            // Retorna para a tela de login após enviar
            window.location.href = "index.html";
        });
    }
});