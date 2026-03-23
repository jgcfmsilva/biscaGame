<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-10 space-y-8">
            <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-emerald-400">Lobby Multiplayer</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-50">Jogos &amp; Partidas em curso</h1>
                    <p class="text-sm text-slate-400">
                        Consulta jogos standalone e partidas competitivas à espera de adversário.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input v-model="search" type="text" placeholder="Procurar por criador, tipo ou stake..."
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
                    <Button class="btn btn-outline text-sm px-4 py-2" :disabled="loading" @click="refresh">
                        {{ loading ? 'A atualizar...' : 'Atualizar' }}
                    </Button>
                </div>
            </header>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 flex items-center gap-4">
                    <div class="rounded-full bg-emerald-500/15 text-emerald-300 p-2">
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Jogos a decorrer</p>
                        <p class="text-2xl font-semibold text-slate-50">{{ stats.gamesPlaying }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 flex items-center gap-4">
                    <div class="rounded-full bg-amber-500/15 text-amber-200 p-2">
                        <Swords class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Partidas a decorrer</p>
                        <p class="text-2xl font-semibold text-slate-50">{{ stats.matchesPlaying }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 flex items-center gap-4">
                    <div class="rounded-full bg-sky-500/15 text-sky-200 p-2">
                        <Activity class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Utilizadores ativos</p>
                        <p class="text-2xl font-semibold text-slate-50">{{ stats.activeUsers }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-emerald-600/40 bg-emerald-500/5 p-4 flex flex-col gap-2">
                    <p class="text-xs uppercase tracking-wide text-emerald-300">Total de lobbies</p>
                    <p class="text-2xl font-semibold text-slate-50">{{ stats.total }}</p>
                    <p class="text-xs text-slate-400">Jogos e matches disponíveis neste momento.</p>
                </div>
            </div>

            <div
                class="rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-900 via-slate-900 to-emerald-900/40 p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-wide text-emerald-300">Queres criar um novo jogo?</p>
                    <h2 class="text-2xl font-semibold text-slate-50">Abre um lobby em segundos</h2>
                    <p class="text-sm text-slate-400">
                        Cria jogos standalone ou matches diretamente na página de jogo. Escolhe o stake, espera pelo
                        adversário e começa a competir.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <RouterLink to="/play?tab=multi">
                        <Button class="btn btn-primary px-5 py-2.5 w-full sm:w-auto">
                            Criar novo jogo
                        </Button>
                    </RouterLink>
                </div>
            </div>

            <p v-if="errorMessage"
                class="rounded-xl border border-rose-700/60 bg-rose-900/40 px-4 py-3 text-sm text-rose-200">
                {{ errorMessage }}
            </p>

            <div v-if="loading" class="grid gap-6 md:grid-cols-2">
                <div v-for="n in 2" :key="`skeleton-${n}`"
                    class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-4 animate-pulse">
                    <div class="h-4 bg-slate-800/70 rounded w-1/3" />
                    <div class="h-3 bg-slate-800/60 rounded w-2/3" />
                    <div class="h-28 bg-slate-900/70 rounded-xl" />
                </div>
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2">
                <section class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-100">Jogos standalone</h2>
                        <span class="text-xs text-slate-500">{{ filteredGames.length }} em espera</span>
                    </div>

                    <div v-if="!filteredGames.length"
                        class="rounded-xl border border-dashed border-slate-800 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-500">
                        Sem jogos a decorrer neste momento.
                    </div>

                    <div v-else class="space-y-4">
                        <article v-for="game in filteredGames" :key="game.id"
                            :class="[
                                'rounded-xl border p-4 space-y-3',
                                game.id === highlightGameId
                                    ? 'border-emerald-500/70 bg-emerald-950/30 shadow-[0_0_0_1px_rgba(16,185,129,0.25)]'
                                    : 'border-slate-800 bg-slate-900/70'
                            ]">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-100">
                                        Jogo · Bisca de {{ game.type === '9' ? '9' : '3' }} · Stake {{ gameStake }} moedas
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Criador · {{ game.player1?.nickname ?? '—' }}
                                    </p>
                                </div>
                                <Badge :class="getGameState(game).badgeClass">
                                    {{ getGameState(game).label }}
                                </Badge>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                {{ getGameState(game).hint }}
                            </p>

                            <Button
                                class="match-cta btn w-full text-xs py-2.5 border border-emerald-300/60 text-emerald-100 bg-gradient-to-r from-slate-900/60 via-emerald-900/20 to-slate-900/50 hover:from-emerald-900/35 hover:via-emerald-900/25 hover:to-emerald-800/30 hover:text-emerald-50 flex items-center justify-center gap-2 transition"
                                :disabled="joinLoadingId === `game-${game.id}`"
                                @click="enterGame(game)">
                                <LogIn class="h-3.5 w-3.5" />
                                <span>
                                    <template v-if="joinLoadingId === `game-${game.id}`">A entrar...</template>
                                    <template v-else-if="isOwner(game)">Ir para a sala de jogo</template>
                                    <template v-else>Entrar no jogo</template>
                                </span>
                            </Button>
                        </article>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-100">Partidas</h2>
                        <span class="text-xs text-slate-500">{{ filteredMatches.length }} em espera</span>
                    </div>

                    <div v-if="!filteredMatches.length"
                        class="rounded-xl border border-dashed border-slate-800 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-500">
                        Sem partidas a decorrer neste momento.
                    </div>

                    <div v-else class="space-y-4">
                        <article v-for="match in filteredMatches" :key="match.id"
                            class="rounded-xl border border-slate-800 bg-slate-900/70 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-100">
                                        Partida · Bisca de {{ match.type === '9' ? '9' : '3' }} · Stake {{ match.stake }} moedas
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ match.player1?.nickname ?? 'Jogador 1' }} vs {{ match.player2?.nickname ??
                                            '—' }}
                                    </p>
                                </div>
                                <Badge :class="getMatchState(match).badgeClass">
                                    {{ getMatchState(match).label }}
                                </Badge>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                {{ getMatchState(match).hint }}
                            </p>

                            <Button
                                class="match-cta btn w-full text-xs py-2.5 border border-emerald-300/60 text-emerald-100 bg-gradient-to-r from-slate-900/60 via-emerald-900/20 to-slate-900/50 hover:from-emerald-900/35 hover:via-emerald-900/25 hover:to-emerald-800/30 hover:text-emerald-50 flex items-center justify-center gap-2 transition"
                                :disabled="joinLoadingId === `match-${match.id}`" @click="enterMatch(match)">
                                <LogIn class="h-3.5 w-3.5" />
                                <span>
                                    <template v-if="joinLoadingId === `match-${match.id}`">A entrar...</template>
                                    <template v-else-if="isMatchOwner(match)">Ir para o match</template>
                                    <template v-else>Entrar no match</template>
                                </span>
                            </Button>
                        </article>
                    </div>
                </section>
            </div>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Users, Swords, RefreshCw, LogIn, Activity } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { toast } from 'vue-sonner'

const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const errorMessage = ref('')
const search = ref('')
const activeGames = ref([])
const activeMatches = ref([])
const gameStake = 2
const joinLoadingId = ref(null)
const highlightGameId = ref(null)
const watchingHighlight = ref(false)
const stats = ref({
    gamesPlaying: 0,
    matchesPlaying: 0,
    total: 0,
    activeUsers: 0,
})
let refreshTimer = null

const normalizedTerm = computed(() => search.value.trim().toLowerCase())

const filteredGames = computed(() => {
    if (!normalizedTerm.value) return activeGames.value
    return activeGames.value.filter((game) =>
        [game.player1?.nickname, game.type, game.stake]
            .filter(Boolean)
            .some((chunk) => chunk.toString().toLowerCase().includes(normalizedTerm.value))
    )
})

const filteredMatches = computed(() => {
    if (!normalizedTerm.value) return activeMatches.value
    return activeMatches.value.filter((match) =>
        [match.player1?.nickname, match.player2?.nickname, match.type, match.stake]
            .filter(Boolean)
            .some((chunk) => chunk.toString().toLowerCase().includes(normalizedTerm.value))
    )
})

async function refresh() {
    loading.value = true
    errorMessage.value = ''
    try {
        const { data } = await api.get('/lobby/active')
        activeGames.value = data.games ?? []
        activeMatches.value = data.matches ?? []

        const remoteStats = data.stats ?? {}
        const gamesPlaying = remoteStats.games_playing ?? remoteStats.gamesPlaying ?? 0
        const matchesPlaying = remoteStats.matches_playing ?? remoteStats.matchesPlaying ?? 0
        const totalCount = activeGames.value.length + activeMatches.value.length
        const remoteActiveUsers = remoteStats.active_users ?? remoteStats.activeUsers
        const derivedActiveUsers = typeof remoteActiveUsers === 'number'
            ? remoteActiveUsers
            : computeParticipantCount()

        stats.value = {
            gamesPlaying,
            matchesPlaying,
            total: totalCount,
            activeUsers: derivedActiveUsers,
        }

        if (highlightGameId.value && !watchingHighlight.value) {
            const target = activeGames.value.find((g) => g.id === highlightGameId.value)
            if (target) {
                if (!isOwner(target)) {
                    watchingHighlight.value = true
                    router.push(`/game/${target.id}/lobby`)
                } else if (target.player2_user_id && target.player2_user_id !== target.player1_user_id) {
                    watchingHighlight.value = true
                    router.push(`/game/${target.id}/lobby`)
                }
            }
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Não foi possível carregar o lobby. Tenta novamente em instantes.'
    } finally {
        loading.value = false
    }
}

function computeParticipantCount() {
    const playerIds = new Set()

    const registerPlayer = (identifier) => {
        if (identifier == null) return
        playerIds.add(identifier)
    }

    activeGames.value.forEach((game) => {
        registerPlayer(game.player1_user_id ?? game.player1?.id ?? null)
        registerPlayer(game.player2_user_id ?? game.player2?.id ?? null)
    })

    activeMatches.value.forEach((match) => {
        registerPlayer(match.player1_user_id ?? match.player1?.id ?? null)
        registerPlayer(match.player2_user_id ?? match.player2?.id ?? null)
    })

    return playerIds.size
}

async function enterGame(game) {
    joinLoadingId.value = `game-${game.id}`
    try {
        await router.push(`/game/${game.id}/lobby`)
    } finally {
        joinLoadingId.value = null
    }
}

async function enterMatch(match) {
    joinLoadingId.value = `match-${match.id}`
    try {
        if (!isMatchOwner(match)) {
            await api.post(`/matches/${match.id}/join`)
        }
        await router.push(`/match/${match.id}/lobby`)
    } catch (err) {
        const message = err.response?.data?.message ?? 'Não foi possível entrar no match.'
        toast.error(message, { id: `match-${match.id}-error` })
    } finally {
        joinLoadingId.value = null
    }
}

function getGameState(game) {
    const waiting = Boolean(game?.custom?.waiting_for_opponent ?? game?.waiting_for_opponent)
    if (waiting) {
        return {
            label: 'À espera de adversário',
            badgeClass: 'bg-amber-500/20 text-amber-100 border border-amber-500/30 text-xs font-medium px-3 py-1',
            hint: 'Ainda não há adversário, podes entrar imediatamente.'
        }
    }
    if (game?.status === 'Pending') {
        return {
            label: 'Jogadores a confirmar',
            badgeClass: 'bg-emerald-500/20 text-emerald-100 border border-emerald-500/30 text-xs font-medium px-3 py-1',
            hint: 'Dois jogadores presentes. A aguardar confirmação para começar.'
        }
    }
    if (game?.status === 'Playing') {
        return {
            label: 'Em jogo',
            badgeClass: 'bg-slate-800 text-slate-200 text-xs font-medium px-3 py-1',
            hint: 'A partida já começou.'
        }
    }
    return {
        label: formatStatus(game?.status),
        badgeClass: 'bg-slate-800 text-slate-200 text-xs font-medium px-3 py-1',
        hint: 'Estado atual da sala.'
    }
}

function getMatchState(match) {
    if (match?.waiting_for_opponent) {
        return {
            label: 'À espera de adversário',
            badgeClass: 'bg-amber-500/20 text-amber-100 border border-amber-500/30 text-xs font-medium px-3 py-1',
            hint: 'O criador aguarda um adversário para iniciar o match.'
        }
    }
    if (match?.status === 'Pending') {
        return {
            label: 'Jogadores a ficarem prontos',
            badgeClass: 'bg-emerald-500/20 text-emerald-100 border border-emerald-500/30 text-xs font-medium px-3 py-1',
            hint: 'Já existem dois jogadores, a fase de ready está em curso.'
        }
    }
    if (match?.status === 'Playing') {
        return {
            label: 'Match em jogo',
            badgeClass: 'bg-slate-800 text-slate-200 text-xs font-medium px-3 py-1',
            hint: 'Partida a decorrer.'
        }
    }
    return {
        label: formatStatus(match?.status),
        badgeClass: 'bg-slate-800 text-slate-200 text-xs font-medium px-3 py-1',
        hint: 'Estado atual da partida.'
    }
}

function formatStatus(status) {
    if (!status) return 'Pendente'
    return {
        Pending: 'Em espera',
        WaitingPlayers: 'À espera de adversário',
        WaitingReady: 'A aguardar confirmação',
        Playing: 'Em jogo',
        Ended: 'Terminado',
        Interrupted: 'Interrompido'
    }[status] ?? status
}

function isOwner(game) {
    const me = auth.user?.id
    return me && game?.player1_user_id === me
}

function isMatchOwner(match) {
    const me = auth.user?.id
    return me && match?.player1_user_id === me
}

function formatRelative(dateString) {
    if (!dateString) return 'alguns segundos'
    const target = new Date(dateString)
    if (Number.isNaN(target.getTime())) return 'alguns segundos'
    const diffSeconds = Math.max(1, Math.floor((Date.now() - target.getTime()) / 1000))
    if (diffSeconds < 60) return `${diffSeconds}s`
    if (diffSeconds < 3600) return `${Math.floor(diffSeconds / 60)}m`
    if (diffSeconds < 86400) return `${Math.floor(diffSeconds / 3600)}h`
    return `${Math.floor(diffSeconds / 86400)}d`
}

onMounted(() => {
    const idFromQuery = Number(new URLSearchParams(window.location.search).get('highlightGame'))
    highlightGameId.value = Number.isFinite(idFromQuery) ? idFromQuery : null

    refresh()

    window.addEventListener('focus', refresh)
    window.addEventListener('lobby_active_update', refresh)
})

onUnmounted(() => {
    window.removeEventListener('focus', refresh)
    window.removeEventListener('lobby_active_update', refresh)
})
</script>

<style scoped>
.match-cta {
    position: relative;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.match-cta:hover:enabled {
    transform: translateY(-1px) scale(1.01);
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.12);
}

.match-cta:active:enabled {
    transform: translateY(0);
}

.match-cta::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent 0%, rgba(16, 185, 129, 0.25) 40%, rgba(16, 185, 129, 0.35) 60%, transparent 100%);
    transform: translateX(-120%);
    animation: lobbyShimmer 3s ease-in-out infinite;
    pointer-events: none;
}

.match-cta:disabled::after {
    display: none;
}

@keyframes lobbyShimmer {
    0% {
        transform: translateX(-130%);
    }
    60% {
        transform: translateX(130%);
    }
    100% {
        transform: translateX(130%);
    }
}
</style>
