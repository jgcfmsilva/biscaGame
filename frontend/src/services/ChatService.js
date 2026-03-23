
/**
 * ChatBot Service - "Bisca Guru"
 * Provides keyword-based assistance for Bisca game rules.
 */

const KNOWLEDGE_BASE = [
    {
        keywords: ['moeda', 'coins', 'comprar', 'loja', 'pagar', 'preço', 'custo'],
        response: 'Podes comprar moedas acedendo ao teu Perfil > Comprar Moedas, ou usando o botão rápido na página inicial (se tiveres login feito).'
    },
    {
        keywords: ['registo', 'registar', 'criar', 'conta', 'login', 'entrar'],
        response: 'Para criares conta, usa o botão "Criar conta" na página principal. O login permite ganhar moedas, ver estatísticas e contactar suporte.'
    },
    {
        keywords: ['ola', 'olá', 'oi', 'boas', 'ajuda', 'help'],
        response: 'Olá! Sou o Bisca Guru. Pergunta-me sobre "pontos", "regras", "trunfo" ou "sinais"!'
    },
    {
        keywords: ['ponto', 'valor', 'valem'],
        response: 'Valores das cartas:\nÁs (11 pts), 7/Bisca (10 pts), Rei (4 pts), Valete (3 pts), Dama (2 pts). As outras valem 0.'
    },
    {
        keywords: ['trunfo'],
        response: 'O trunfo é o naipe da carta virada no início. Ganha a qualquer outro naipe. Se jogares trunfo, só perdes para um trunfo maior.'
    },
    {
        keywords: ['bisca', '7'],
        response: 'A "Bisca" é o 7. Vale 10 pontos. É a segunda carta mais forte depois do Ás.'
    },
    {
        keywords: ['as', 'ás', '11'],
        response: 'O Ás é a carta mais forte! Vale 11 pontos.'
    },
    {
        keywords: ['rei', '4'],
        response: 'O Rei vale 4 pontos.'
    },
    {
        keywords: ['valete', '3'],
        response: 'O Valete vale 3 pontos.'
    },
    {
        keywords: ['dama', '2'],
        response: 'A Dama vale 2 pontos.'
    },
    {
        keywords: ['jogar', 'regras', 'tutorial'],
        response: 'Regras básicas: \n1. Seguir naipe é obrigatório apenas quando o baralho acaba.\n2. Quem ganha a vaza puxa primeiro.\n3. O objetivo é fazer >60 pontos.'
    },
    {
        keywords: ['fim', 'acaba', 'vencer', 'ganhar'],
        response: 'O jogo acaba quando se jogam todas as cartas. Quem tiver 61+ pontos ganha. 120 pontos é Bandeira!'
    },
    {
        keywords: ['empate'],
        response: 'Se ambos tiverem 60 pontos, é empate. Ninguém ganha marcas.'
    },
    {
        keywords: ['renuncia', 'renúncia', 'engano'],
        response: 'Se não assistires ao naipe (quando obrigatório), perdes o jogo imediatamente (Renúncia).'
    },
    {
        keywords: ['stats', 'estatistica', 'estatística', 'ranking', 'leaderboard', 'top'],
        response: 'Podes consultar as tuas estatísticas no menu Perfil. Os Leaderboards globais mostram os melhores jogadores da plataforma.'
    },
    {
        keywords: ['3', '9', 'bisca de 3', 'bisca de 9', 'diferença', 'modos'],
        response: 'Na Bisca de 3 tens 3 cartas na mão e compras até ao fim. Na Bisca de 9 recebes 9 cartas logo de início e jogas até acabarem.'
    },
    {
        keywords: ['aposta', 'stake', 'premio', 'prémio', 'ganhos'],
        response: 'Nos jogos com aposta (Match), cada jogador entra com X moedas. O vencedor leva o total do pote!'
    },
    {
        keywords: ['avatar', 'foto', 'perfil', 'mudar', 'senha', 'password'],
        response: 'Podes alterar o teu Avatar e a tua Password na área de Perfil. Clica no teu nome no canto superior direito.'
    },
    {
        keywords: ['bot', 'computador', 'offline', 'sozinho'],
        response: 'Podes jogar contra Bots em modo Single-player (offline) ou preencher lugares vazios em jogos Multiplayer.'
    }
];

const DEFAULT_RESPONSE = "Desculpa, não percebi. Tenta perguntar sobre 'pontos', 'trunfo' ou 'regras'.";
import { useAuthStore } from '@/stores/auth' // Assuming we might use auth later, or just fetch
import axios from 'axios'

export const ChatService = {
    DEFAULT_RESPONSE,

    /**
     * Tenta encontrar uma resposta localmente para o utilizador.
     * @param {string} message 
     * @returns {string}
     */
    getAnswer(message) {
        if (!message) return "";

        const lowerMsg = message.toLowerCase();

        // Tenta encontrar a primeira regra que bata certo
        const match = KNOWLEDGE_BASE.find(rule =>
            rule.keywords.some(keyword => lowerMsg.includes(keyword))
        );

        return match ? match.response : DEFAULT_RESPONSE;
    },

    /**
     * Pergunta ao Backend (AI) se não souber responder.
     * @param {string} message 
     * @returns {Promise<string>}
     */
    async askAI(message) {
        // Use the configured axios instance or fetch
        // Assuming a global axios config or relative path
        try {
            const response = await axios.post('/api/chat/ask', { message });
            return response.data.answer;
        } catch (e) {
            console.error("AI Error", e);
            throw e;
        }
    },
    /**
     * Devolve uma lista de perguntas rápidas para facilitar.
     */
    getSuggestions() {
        return [
            "Quanto valem as cartas?",
            "O que é o trunfo?",
            "Regras básicas",
            "Como ganho?"
        ];
    }
};
