const API_BASE_URL = 'http://127.0.0.1:8000/api';

const CHAVE_TOKEN = 'sigep_token';

const SIGEPAPI = {
    baseURL: API_BASE_URL,

    obterToken() {
        return sessionStorage.getItem(CHAVE_TOKEN);
    },

    salvarToken(token) {
        sessionStorage.setItem(CHAVE_TOKEN, token);
    },

    removerToken() {
        sessionStorage.removeItem(CHAVE_TOKEN);
    },

    async requisicao(endpoint, opcoes = {}) {
        const headers = new Headers(opcoes.headers || {});

        const token = this.obterToken();

        if (token) {
            headers.set('Authorization', `Bearer ${token}`);
        }

        let body = opcoes.body;

        if (
            body &&
            typeof body === 'object' &&
            !(body instanceof FormData) &&
            !(body instanceof Blob)
        ) {
            headers.set('Content-Type', 'application/json');
            body = JSON.stringify(body);
        }

        headers.set('Accept', 'application/json');

        const resposta = await fetch(`${API_BASE_URL}${endpoint}`, {
            ...opcoes,
            headers,
            body
        });

        const tipoConteudo = resposta.headers.get('content-type');

        let dados = null;

        if (tipoConteudo && tipoConteudo.includes('application/json')) {
            dados = await resposta.json();
        } else {
            dados = await resposta.text();
        }

        if (!resposta.ok) {
            const erro = new Error(
                dados?.message || `Erro HTTP ${resposta.status}`
            );

            erro.status = resposta.status;
            erro.dados = dados;

            throw erro;
        }

        return dados;
    }
};

window.SIGEPAPI = SIGEPAPI;
