<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-12 space-y-12">
            <div v-if="isAdmin" class="mx-auto max-w-2xl rounded-2xl border border-slate-800 bg-slate-950/70 p-6 text-center">
                <h2 class="text-xl font-semibold text-slate-100">Modo de jogo indisponível</h2>
                <p class="mt-2 text-sm text-slate-400">
                    Contas de administrador não podem jogar. Usa uma conta de jogador.
                </p>
                <RouterLink :to="{ name: 'admin-dashboard' }" class="inline-block mt-4">
                    <Button class="btn btn-primary px-5 py-2 text-sm">Ir para o painel</Button>
                </RouterLink>
            </div>
            <template v-else>
            <header class="space-y-6 text-center">
                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold tracking-tight">
                        Escolher modo de jogo
                    </h1>
                    <p class="text-slate-500 text-sm max-w-md mx-auto">
                        Joga uma partida rápida ou um match completo, em single-player contra o bot
                        ou em multiplayer contra outros jogadores.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-4">
                    <!-- Server Status Button -->
                    <div
                        @click="showStatusModal = true"
                        class="inline-flex items-center gap-2.5 rounded-full border border-slate-700 bg-slate-900/90 px-4 py-1.5 text-xs font-semibold text-slate-300 cursor-pointer hover:border-emerald-500/50 hover:bg-slate-900 transition-all duration-300 group relative overflow-hidden shadow-lg shadow-black/20 hover:shadow-emerald-900/20">
                        <span class="relative flex h-2.5 w-2.5">
                           <span :class="['absolute inline-flex h-full w-full animate-ping rounded-full opacity-75', serverStatusColor]"></span>
                           <span :class="['relative inline-flex h-2.5 w-2.5 rounded-full', serverStatusColor]"></span>
                        </span>
                        <span class="group-hover:text-emerald-400 transition-colors duration-300 tracking-wide uppercase">Estado do Servidor</span>
                    </div>
                </div>
            </header>

            <div class="flex justify-center">
                <div
                    class="relative inline-flex items-center rounded-full bg-slate-900 border border-slate-800 px-1 py-1 select-none w-[320px]">
                    <div class="absolute top-[4px] bottom-[4px] w-[calc(50%-4px)] rounded-full bg-slate-800 transition-transform duration-300"
                        :class="tab === 'multi' ? 'translate-x-[calc(100%+4px)]' : 'translate-x-0'" />
                    <button
                        class="relative z-10 flex-1 px-3 py-1.5 text-xs md:text-sm font-medium flex items-center justify-center gap-2 transition-colors"
                        :class="tab === 'single' ? 'text-white' : 'text-slate-400 hover:text-slate-200'"
                        @click="tab = 'single'">
                        <Gamepad2 class="w-4 h-4" />
                        <span>Single-player</span>
                    </button>

                    <button
                        class="relative z-10 flex-1 px-3 py-1.5 text-xs md:text-sm font-medium flex items-center justify-center gap-2 transition-colors"
                        :class="tab === 'multi' ? 'text-white' : 'text-slate-400 hover:text-slate-200'"
                        @click="tab = 'multi'">
                        <Users class="w-4 h-4" />
                        <span>Multiplayer</span>
                    </button>
                </div>
            </div>

            <div v-if="tab === 'single'" class="grid gap-8 md:grid-cols-2">
                <div
                    class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-4 hover:border-emerald-500/30 hover:bg-slate-900/80 hover:scale-[1.01] transition-all duration-300 shadow-lg hover:shadow-emerald-900/10">
                    <h2 class="text-lg font-semibold text-slate-100 group-hover:text-emerald-400 transition-colors">Partida rápida contra o bot</h2>
                    <p class="text-slate-500 text-sm">
                        Joga um único jogo contra um bot simples. Não usa moedas e não
                        fica registado em histórico.
                    </p>

                    <div class="flex gap-3">
                        <Button class="btn btn-outline flex-1 border-slate-700 hover:border-emerald-500 hover:text-emerald-400 hover:bg-emerald-500/10 transition-all" @click="playSingle('3')">
                            Bisca de 3
                        </Button>
                        <Button class="btn btn-outline flex-1 border-slate-700 hover:border-emerald-500 hover:text-emerald-400 hover:bg-emerald-500/10 transition-all" @click="playSingle('9')">
                            Bisca de 9
                        </Button>
                    </div>
                </div>

                <div
                    class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-4 hover:border-amber-500/30 hover:bg-slate-900/80 hover:scale-[1.01] transition-all duration-300 shadow-lg hover:shadow-amber-900/10">
                    <h2 class="text-lg font-semibold text-slate-100 group-hover:text-amber-400 transition-colors">Match completo contra o bot</h2>
                    <p class="text-slate-500 text-sm">
                        Joga um match (até 4 marcas) contra o bot. Sem coins, apenas treino,
                        mas com as mesmas regras de marks (risca, capote, bandeira).
                    </p>

                    <div class="flex gap-3">
                        <Button class="btn btn-outline flex-1 border-slate-700 hover:border-amber-500 hover:text-amber-400 hover:bg-amber-500/10 transition-all" @click="playSingleMatch('3')">
                            Bisca de 3
                        </Button>
                        <Button class="btn btn-outline flex-1 border-slate-700 hover:border-amber-500 hover:text-amber-400 hover:bg-amber-500/10 transition-all" @click="playSingleMatch('9')">
                            Bisca de 9
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="tab === 'multi'" class="space-y-10">
                <div class="text-center" v-if="!isLoggedIn">
                    <p class="text-slate-400 text-sm mb-4">
                        Multiplayer disponível apenas para utilizadores autenticados.
                    </p>
                    <RouterLink to="/login">
                        <Button class="btn btn-primary px-6 py-2.5 text-sm">
                            Iniciar sessão
                        </Button>
                    </RouterLink>
                </div>

                <div v-else class="grid gap-8 md:grid-cols-2">
                    <div
                        class="group rounded-2xl border border-slate-800 bg-slate-950/60 p-6 space-y-4 hover:border-blue-500/30 hover:bg-slate-900/80 hover:scale-[1.01] transition-all duration-300 shadow-lg hover:shadow-blue-900/10">
                        <h2 class="text-lg font-semibold text-slate-100 group-hover:text-blue-400 transition-colors">Jogo único online</h2>
                        <p class="text-slate-500 text-sm">
                            Jogo standalone entre dois jogadores. Custa
                            <span class="font-semibold text-amber-300">2 coins</span> para
                            entrar. Em caso de empate, cada jogador recebe
                            <span class="font-semibold text-amber-300">1 coin</span> de volta.
                        </p>

                        <div class="flex gap-3">
                            <Button class="btn btn-outline flex-1 border-slate-700 hover:border-blue-500 hover:text-blue-400 hover:bg-blue-500/10 transition-all" :disabled="creatingQuickGame"
                                @click="createQuickGame('3')">
                                <span v-if="creatingQuickGame && quickVariant === '3'">
                                    A criar...
                                </span>
                                <span v-else>Bisca de 3</span>
                            </Button>
                            <Button class="btn btn-outline flex-1 border-slate-700 hover:border-blue-500 hover:text-blue-400 hover:bg-blue-500/10 transition-all" :disabled="creatingQuickGame"
                                @click="createQuickGame('9')">
                                <span v-if="creatingQuickGame && quickVariant === '9'">
                                    A criar...
                                </span>
                                <span v-else>Bisca de 9</span>
                            </Button>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-800 bg-slate-950/60 p-6 space-y-4 hover:border-slate-700 transition">
                        <h2 class="text-lg font-semibold">Match multiplayer</h2>
                        <p class="text-slate-500 text-sm">
                            Match até 4 marcas entre dois jogadores. Stake mínimo de
                            <span class="text-amber-300 font-semibold">3 coins</span> por
                            jogador, até um máximo de <span class="font-semibold">100</span>.
                            O vencedor recebe o pot total menos 1 coin de comissão da
                            plataforma.
                        </p>

                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <Button class="flex-1" :class="selectedMode === '3'
                                    ? 'btn btn-primary'
                                    : 'btn btn-outline'" @click="selectedMode = '3'">
                                    Bisca de 3
                                </Button>

                                <Button class="flex-1" :class="selectedMode === '9'
                                    ? 'btn btn-primary'
                                    : 'btn btn-outline'" @click="selectedMode = '9'">
                                    Bisca de 9
                                </Button>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Stake por jogador</span>
                                    <span class="text-amber-300 font-semibold">{{ stake }} coins</span>
                                </div>
                                <input v-model.number="stake" type="range" min="3" max="100" step="1"
                                    class="w-full accent-amber-400" />
                                <p class="text-[11px] text-slate-500">
                                    Pot total: <span class="font-semibold">{{ stake * 2 }}</span> coins
                                    (plataforma retém 1 coin).
                                </p>
                            </div>

                            <Button class="btn btn-primary w-full py-2.5 text-sm" :disabled="creatingMatch"
                                @click="createMatch">
                                {{ creatingMatch ? "A criar match..." : "Criar match" }}
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="isLoggedIn" class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-4">
                    <h2 class="text-lg font-semibold">Juntar-te a um jogo existente</h2>
                    <p class="text-slate-500 text-sm">
                        Procura jogos e matches à espera de adversário e entra diretamente.
                    </p>

                    <Button class="btn btn-primary w-full py-2.5 text-sm" @click="goToLobby">
                        Ver jogos e matches disponíveis
                    </Button>
                </div>
            </div>
            </template>
        </section>

        <ServerStatusModal 
            :is-open="showStatusModal"
            :players-online="serverInfo.playersOnline"
            :latency="serverInfo.latency"
            :last-checked="serverInfo.lastChecked"
            @close="showStatusModal = false"
        />
    </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRouter, useRoute } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import { Button } from "@/components/ui/button";
import { Gamepad2, Users } from "lucide-vue-next";
import { useAuthStore } from "@/stores/auth";
import { usePendingLobbyStore } from "@/stores/pendingLobby";
import api from "@/services/api";
import { toast } from "vue-sonner";
import ServerStatusModal from '@/components/ServerStatusModal.vue'

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const pendingStore = usePendingLobbyStore();

const showStatusModal = ref(false);
const serverInfo = ref({
    status: 'offline',
    playersOnline: 0,
    latency: '—',
    lastChecked: '—'
})
const serverStatusColor = computed(() => 
    serverInfo.value.status === 'online' ? 'bg-emerald-400' : 'bg-rose-400'
)

async function checkServerStatus() {
    try {
        const start = performance.now()
        const { data } = await api.get('/public/stats')
        const latency = Math.round(performance.now() - start)
        
        serverInfo.value = {
            status: data.server?.status || 'offline',
            playersOnline: data.players_online || 0,
            latency: latency,
            lastChecked: new Date().toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })
        }
    } catch (e) {
        serverInfo.value.status = 'offline'
    }
}

const tab = ref(route.query.tab === "multi" ? "multi" : "single");

watch(
    () => route.query.tab,
    (value) => {
        tab.value = value === "multi" ? "multi" : "single";
    }
)

watch(tab, (value) => {
    router.replace({
        query: { ...route.query, tab: value }
    })
})

function playSingle(type) {
    router.push({ path: "/game", query: { type } });
}

function playSingleMatch(type) {
    router.push({
        path: "/game",
        query: { type, mode: "match" }
    });
}

const isLoggedIn = computed(() => auth.isAuthenticated);
const isAdmin = computed(() => auth.isAdmin);

const creatingQuickGame = ref(false);
const quickVariant = ref(null); // "3" | "9"

async function createQuickGame(type) {
    if (creatingQuickGame.value) return;
    if (pendingStore.pendingGame) {
        router.push({ path: `/game/${pendingStore.pendingGame.id}/lobby` });
        return;
    }
    creatingQuickGame.value = true;
    quickVariant.value = type;

    try {
        const res = await api.post("/games/quick", {
            type // "3" ou "9"
        });

        const gameId = res.data.game.id;
        pendingStore.setPending(res.data.game);
        router.push({ path: `/game/${gameId}/lobby` });
    } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message ?? "Erro ao criar jogo multiplayer.";
        toast.error(msg, { id: "quick-game-error" });
    } finally {
        creatingQuickGame.value = false;
        quickVariant.value = null;
    }
}

const selectedMode = ref("3");
const stake = ref(3);
const creatingMatch = ref(false);
const pendingLoading = ref(false);

async function createMatch() {
    if (creatingMatch.value) return;

    if (stake.value < 3 || stake.value > 100) {
        alert("Stake tem de estar entre 3 e 100 coins.");
        return;
    }

    creatingMatch.value = true;

    try {
        const res = await api.post("/matches", {
            type: selectedMode.value,
            stake: stake.value
        });

        const matchPayload = res.data.match ?? {};
        const matchId = matchPayload.id;
        pendingStore.setPendingMatch({
            id: matchId,
            matchId,
            type: matchPayload.type ?? selectedMode.value,
            stake: matchPayload.stake ?? stake.value,
            hostNickname: matchPayload.player1?.nickname ?? auth.user?.nickname ?? null,
            ownerId: matchPayload.player1?.id ?? null,
        });
        router.push(`/match/${matchId}/lobby`);
    } catch (e) {
        console.error(e);
        const message = e.response?.data?.message ?? "Não foi possível criar o match.";
        toast.error(message, { id: "create-match-error" });
    } finally {
        creatingMatch.value = false;
    }
}

function goToLobby() {
    if (pendingStore.pendingMatch) {
        const matchId = pendingStore.pendingMatch.matchId ?? pendingStore.pendingMatch.id
        router.push({ path: `/match/${matchId}/lobby` })
        return
    }
    if (pendingStore.pendingGame) {
        router.push({ path: `/game/${pendingStore.pendingGame.id}/lobby` })
        return
    }
    router.push("/lobby")
}

async function fetchPendingGame() {
    if (!auth.isAuthenticated) return;
    pendingLoading.value = true;
    try {
        await pendingStore.refresh(true);
    } catch (err) {
        console.error("Erro ao verificar lobby pendente", err);
    } finally {
        pendingLoading.value = false;
    }
}

onMounted(() => {
    fetchPendingGame();
    checkServerStatus();
});
</script>
