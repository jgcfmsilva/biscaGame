<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-6 md:py-8 space-y-6">

            <GameLoader v-if="showLoader" :message="loaderMessage" />

            <div v-else-if="error"
                class="rounded-xl border border-red-800 bg-red-950/70 p-6 text-center space-y-3 shadow-[0_18px_45px_rgba(127,29,29,0.45)]">
                <p class="text-red-300 font-semibold text-lg">
                    Não foi possível iniciar o jogo
                </p>
                <p class="text-red-400 text-sm">
                    {{ error }}
                </p>

                <button @click="retry"
                    class="mt-2 px-4 py-2 rounded-lg text-sm font-medium bg-red-700 text-white hover:bg-red-600">
                    Tentar novamente
                </button>

                <button @click="$router.push('/play')"
                    class="block mx-auto mt-1 px-3 py-1 text-xs text-slate-300 underline-offset-2 hover:underline">
                    Voltar à seleção de jogo
                </button>
            </div>

            <template v-else>
                <div v-if="game.realtimeError"
                    class="rounded-2xl border border-rose-800/70 bg-rose-950/70 p-5 text-sm text-rose-100 shadow-[0_18px_45px_rgba(127,29,29,0.35)] space-y-2">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-base font-semibold text-rose-50">Ligação em tempo-real indisponível</p>
                            <p class="text-rose-200/90">
                                {{ game.realtimeError }}
                            </p>
                            <p class="text-xs text-rose-300 mt-1">
                                Tanto tu como o adversário precisam da ligação WebSocket para confirmar presença e
                                jogar. Voltamos a tentar automaticamente a cada instantes.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button class="btn btn-outline border-rose-400/70 text-rose-100 hover:bg-rose-900/60"
                                @click="retryRealtime">
                                Tentar reconectar agora
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-if="showLobbyStage"
                    class="rounded-xl border border-slate-800 bg-slate-950/80 p-6 space-y-4 shadow-[0_18px_45px_rgba(15,23,42,0.45)]">
                    <div class="flex flex-col gap-2 text-left md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-emerald-400">Lobby online</p>
                            <h2 class="text-xl font-semibold text-slate-100">À espera do adversário</h2>
                            <p class="text-sm font-medium text-slate-300">
                                {{ lobbyModeLabel }} · {{ lobbyTypeLabel }} · Stake {{ lobbyStakeLabel }} moedas
                            </p>
                        </div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-4 py-2 text-xs font-medium text-slate-200">
                            <span class="h-2 w-2 rounded-full"
                                :class="game.waitingForOpponent ? 'bg-amber-400' : (countdownActive ? 'bg-emerald-400' : 'bg-emerald-500')"></span>
                            <span>
                                {{ game.waitingForOpponent ? "Aguarda adversário" : (countdownActive ? "A começar" :
                                    "Confirmar presença") }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4 text-left">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Tu</p>
                            <div class="flex items-center gap-2 text-base font-semibold text-slate-100">
                                <span>{{ meLabel }}</span>
                                <svg v-if="meIsOwner" class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" aria-label="Criador do lobby" role="img">
                                    <path fill="currentColor" d="M3 6l4 4 5-6 5 6 4-4v12H3V6zm2 10h14V9.83l-3.2 3.2L12 8.33 8.2 13.03 5 9.83V16z"/>
                                </svg>
                            </div>
                            <p class="text-sm" :class="meReady ? 'text-emerald-400' : 'text-amber-300'">
                                {{ meReady ? "Pronto" : "Por confirmar" }}
                            </p>
                        </div>
                        <div class="relative rounded-lg border border-slate-800 bg-slate-900/60 p-4 text-left">
                            <button
                                v-if="canKickOpponent"
                                class="absolute right-3 top-3 rounded-md border border-rose-500/70 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-200 hover:bg-rose-500/20"
                                :disabled="kickingOpponent"
                                @click="kickOpponent">
                                {{ kickingOpponent ? "A expulsar" : "Expulsar" }}
                            </button>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Adversário</p>
                            <div class="flex items-center gap-2 text-base font-semibold text-slate-100">
                                <span>{{ opponentLabel }}</span>
                                <svg v-if="opponentIsOwner" class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" aria-label="Criador do lobby" role="img">
                                    <path fill="currentColor" d="M3 6l4 4 5-6 5 6 4-4v12H3V6zm2 10h14V9.83l-3.2 3.2L12 8.33 8.2 13.03 5 9.83V16z"/>
                                </svg>
                            </div>
                            <p class="text-sm" :class="opponentReady ? 'text-emerald-400' : 'text-amber-300'">
                                {{ opponentStatus }}
                            </p>
                        </div>
                    </div>

                    <div v-if="game.waitingForOpponent" class="w-full mt-10">
                        <Button class="btn w-full bg-red-600 hover:bg-red-500 border-red-500 text-white text-sm py-3"
                            @click="cancelLobby">
                            Cancelar lobby
                        </Button>
                    </div>

                    <div v-else-if="countdownActive" class="space-y-3 text-center">
                        <p class="text-sm text-emerald-200">Ambos estão prontos! A mesa abre em</p>
                        <div
                            class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-emerald-400/60 bg-emerald-500/10 text-3xl font-bold text-emerald-200 animate-pulse">
                            {{ countdownSeconds }}
                        </div>
                        <div class="relative h-2 w-full overflow-hidden rounded-full bg-slate-800">
                            <div class="absolute left-0 top-0 h-full bg-emerald-500 transition-all duration-1000"
                                :style="{ width: countdownProgress + '%' }"></div>
                        </div>
                    </div>

                    <div v-else class="space-y-2 text-center">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-center">
                            <Button class="btn btn-primary px-4 py-2"
                                :disabled="readying || countdownActive || game.waitingForOpponent" @click="game.readyUp()">
                                {{ readying ? "Aguarda..." : readyButtonLabel }}
                            </Button>
                            <Button v-if="!game.waitingForOpponent && game.ownerId === auth.user.id" class="btn btn-outline px-4 py-2"
                                @click="cancelLobby">
                                Cancelar lobby
                            </Button>
                            <Button v-else-if="!game.waitingForOpponent && game.ownerId !== auth.user.id" class="btn btn-outline px-4 py-2"
                                @click="cancelLobby">
                                Sair do lobby
                            </Button>
                        </div>
                    </div>

                    <div class="mt-6 rounded-lg border border-slate-800 bg-slate-900/50 p-4 text-left">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Histórico do lobby</p>
                        <ul v-if="lobbyLogEntries.length" class="mt-3 space-y-2 text-sm text-slate-200">
                            <li v-for="(entry, index) in lobbyLogEntries" :key="index" class="flex items-center gap-2">
                                <span class="text-slate-400">{{ formatLogTime(entry.at) }}</span>
                                <span class="font-medium" :class="isLogOwner(entry) ? 'text-amber-300' : 'text-slate-100'">
                                    {{ entry.nickname || `Jogador ${entry.userId ?? ''}` }}
                                </span>
                                <span class="text-slate-300">{{ formatLogAction(entry.action) }}</span>
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-sm text-slate-400">Sem eventos registados.</p>
                    </div>
                </div>

            </template>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from "vue-sonner"

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import GameLoader from '@/components/gameplay/GameLoader.vue'
import { Button } from "@/components/ui/button"

import { useGameStore } from '@/stores/gameStore'
import { useAuthStore } from '@/stores/auth'
import { usePendingLobbyStore } from '@/stores/pendingLobby'
import { useWsStore } from '@/stores/ws'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const game = useGameStore()
const auth = useAuthStore()
const pendingStore = usePendingLobbyStore()
const wsStore = useWsStore()

const error = ref(null)
const readying = ref(false)
const countdownSeconds = ref(5)
const countdownActive = ref(false)
const countdownTimer = ref(null)
const waitingMatchId = ref(null)
const waitingMatchMeta = ref(null)
const pendingNavigateToGame = ref(false)
let matchPollTimer = null
const COUNTDOWN_TOTAL = 5
const LOBBY_STATUSES = new Set(["WaitingPlayers", "WaitingReady", "Pending"])
const isLobbyStatus = (status) => Boolean(status && LOBBY_STATUSES.has(status))
const effectiveStatus = computed(() => game.state?.status ?? game.serverStatus)
const loaderMessage = computed(() => game.waitingForOpponent
    ? "A aguardar adversário..."
    : "A preparar a partida..."
)
const isLobbyRoute = computed(() =>
    ["game-online-lobby", "match-online-lobby"].includes(route.name)
)
const isTableRoute = computed(() =>
    ["game-online", "match-game"].includes(route.name)
)
const showLobbyStage = computed(() => {
    if (game.mode !== 'online') return false
    if (game.loading && !effectiveStatus.value && !game.state) {
        return false
    }
    if (!isLobbyRoute.value) {
        return false
    }
    const waiting = game.waitingForOpponent || !game.state
    const notEveryoneReady = !(game.state?.ready?.me && game.state?.ready?.opponent)
    return waiting || countdownActive.value || isLobbyStatus(effectiveStatus.value) || notEveryoneReady
})
const showLoader = computed(() => game.loading && !showLobbyStage.value && !error.value)
const meReady = computed(() => game.ready().me)
const opponentReady = computed(() => game.ready().opponent)
const lobbyLogEntries = computed(() => game.lobbyLog ?? [])
const meIsOwner = computed(() => {
    if (!game.ownerId || !auth.user?.id) return false
    return Number(game.ownerId) === Number(auth.user.id)
})
const opponentIsOwner = computed(() => {
    if (!game.ownerId) return false
    const opponentId = game.playerNames?.opponentId ?? game.state?.opponent?.id ?? null
    if (!opponentId) return false
    return Number(game.ownerId) === Number(opponentId)
})
const opponentLabel = computed(() => {
    if (game.waitingForOpponent) return "A aguardar adversário"
    return game.state?.opponent?.nickname ?? game.playerNames?.opponent ?? "Adversário"
})
const opponentStatus = computed(() => {
    if (game.waitingForOpponent) return "Por chegar"
    return opponentReady.value ? "Pronto" : "A aguardar ready"
})
const lobbyModeLabel = computed(() => game.sessionMode === "match" ? "Partida" : "Jogo")
const lobbyTypeLabel = computed(() => {
    const t = game.state?.type ?? game.currentVariant;
    if (t === "9") return "Bisca de 9";
    if (t === "3") return "Bisca de 3";
    return "Bisca";
})
const lobbyStakeLabel = computed(() => {
    if (game.sessionMode === "match") return game.matchStake ?? "—";
    return game.entryStake ?? 2;
})
const meLabel = computed(() => game.playerNames?.me ?? auth.user?.nickname ?? "Tu")
const readyButtonLabel = computed(() => meReady.value ? "Não pronto" : "Ficar pronto")
const countdownProgress = computed(() => {
    const pct = (countdownSeconds.value / COUNTDOWN_TOTAL) * 100
    return Math.max(0, Math.min(100, pct))
})
const canKickOpponent = computed(() => {
    if (game.waitingForOpponent) return false
    if (countdownActive.value) return false
    if (!game.ownerId || !auth.user?.id) return false
    const isOwner = Number(game.ownerId) === Number(auth.user.id)
    const opponentId = game.playerNames?.opponentId ?? game.state?.opponent?.id ?? null
    return isOwner && Boolean(opponentId)
})
const kickingOpponent = ref(false)

function formatLogAction(action) {
    if (action === "create") return "criou o lobby";
    if (action === "join") return "entrou no lobby";
    if (action === "leave") return "saiu do lobby";
    if (action === "kick") return "foi expulso do lobby";
    return "teve uma atualização";
}

function formatLogTime(value) {
    if (!value) return "--:--";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "--:--";
    return date.toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
}

function isLogOwner(entry) {
    if (!entry?.userId || !game.ownerId) return false;
    return Number(entry.userId) === Number(game.ownerId);
}

const shouldTrackPendingGame = computed(() =>
    Boolean(route.params.gameId) && game.mode === "online"
)

const syncPendingGame = () => {
    if (!shouldTrackPendingGame.value) return
    const gameId = game.gameId ?? route.params.gameId
    if (!gameId) return
    const pending =
        game.waitingForOpponent ||
        !game.state ||
        isLobbyStatus(effectiveStatus.value)
    if (pending) {
        pendingStore.setPending({
            id: gameId,
            type: game.currentVariant,
            stake: game.entryStake,
        })
        return
    }
    pendingStore.setPending(null)
}

async function fetchMatchDetails(matchId, attemptJoin = true) {
    try {
        const res = await api.get(`/matches/${matchId}`)
        return res.data
    } catch (err) {
        const status = err.response?.status
        if (status === 404) {
            const message = err.response?.data?.message ?? "Match não encontrado."
            const notFoundError = new Error(message)
            notFoundError.code = 404
            throw notFoundError
        }
        if (attemptJoin && status === 403) {
            const res = await api.post(`/matches/${matchId}/join`)
            return res.data
        }
        if (status === 422) {
            const message = err.response?.data?.message ?? "Match indisponível."
            toast.error(message, { id: "match-error" })
        }
        throw err
    }
}

async function hydrateMatchFromPayload(payload, matchId) {
    const matchData = payload.match ?? payload
    game.sessionMode = "match"
    game.matchId = matchId
    if (!game.ownerId) {
        game.ownerId = matchData.player1_user_id ?? matchData.player1?.id ?? game.ownerId
    }
    game.matchStake = matchData.stake ?? game.matchStake
    game.currentVariant = matchData.type ?? game.currentVariant
    game.mode = "online"
    game.waitingForOpponent = true
    game.loading = false

    const activeId = payload.active_game?.id ?? payload.firstGame?.id
    if (activeId) {
        pendingStore.clearMatch()
        waitingMatchId.value = null
        waitingMatchMeta.value = null
        stopMatchPolling()
        await game.startOnlineGame(activeId)
        return
    }

    waitingMatchId.value = matchId
    waitingMatchMeta.value = {
        type: matchData.type ?? "3",
        stake: matchData.stake ?? 3,
        player1: matchData.player1 ?? null,
    }
    const lobbyLog = matchData?.custom?.lobby_log
    if (Array.isArray(lobbyLog) && lobbyLog.length) {
        game.setLobbyLog(lobbyLog)
    } else if (matchData.player1) {
        game.setLobbyLog([{
            action: "create",
            userId: matchData.player1.id,
            nickname: matchData.player1.nickname,
            at: matchData.created_at ?? null,
        }])
    }
    pendingStore.setPendingMatch({
        id: matchId,
        matchId,
        type: waitingMatchMeta.value.type,
        stake: waitingMatchMeta.value.stake,
        hostNickname: waitingMatchMeta.value.player1?.nickname ?? auth.user?.nickname ?? null,
        ownerId: waitingMatchMeta.value.player1?.id ?? null,
    })
    startMatchPolling(matchId)
}

function startMatchPolling(matchId) {
    stopMatchPolling()
    matchPollTimer = setInterval(async () => {
        try {
            const payload = await fetchMatchDetails(matchId, false)
            const activeId = payload.active_game?.id ?? payload.firstGame?.id
            const matchData = payload.match ?? payload
            if (!game.ownerId) {
                game.ownerId = matchData.player1_user_id ?? matchData.player1?.id ?? game.ownerId
            }
            if (matchData?.stake) {
                game.matchStake = matchData.stake
            }
            if (matchData?.type) {
                game.currentVariant = matchData.type
            }
            if (["Cancelled", "Interrupted", "Ended"].includes(matchData?.status)) {
                pendingStore.clearMatch()
                waitingMatchId.value = null
                waitingMatchMeta.value = null
                stopMatchPolling()
                toast.info("Lobby cancelado pelo criador.", { id: "match-cancelled" })
                router.push('/play')
                return
            }

            if (activeId) {
                pendingStore.clearMatch()
                waitingMatchId.value = null
                waitingMatchMeta.value = null
                stopMatchPolling()
                await game.startOnlineGame(activeId)
            }
        } catch (err) {
            if (err?.code === 404) {
                pendingStore.clearMatch()
                waitingMatchId.value = null
                waitingMatchMeta.value = null
                stopMatchPolling()
                toast.error(err.message ?? "Match já não está disponível.", { id: "match-end" })
                router.push('/play')
                return
            }
            console.error("Erro ao atualizar match pendente", err)
        }
    }, 4000)
}

function stopMatchPolling() {
    if (matchPollTimer) {
        clearInterval(matchPollTimer)
        matchPollTimer = null
    }
}

async function startGame() {
    try {
        error.value = null
        waitingMatchId.value = null
        waitingMatchMeta.value = null
        stopMatchPolling()
        const gameId = route.params.gameId
        const matchId = route.params.matchId
        const gameParamId = route.params.gameId

        if (!auth.isAuthenticated) {
            await router.push({ name: "login", query: { redirect: route.fullPath } })
            return
        }

        if (gameId) {
            await game.startOnlineGame(gameId)
            syncPendingGame()
            return
        }

        if (matchId && gameParamId) {
            game.sessionMode = "match"
            game.matchId = matchId
            await game.startOnlineGame(gameParamId)
            return
        }

        if (matchId) {
            const payload = await fetchMatchDetails(matchId, true)
            await hydrateMatchFromPayload(payload, matchId)
            return
        }

        await router.replace({ name: "play", query: { tab: "multi" } })
        toast.error("Seleciona um jogo multiplayer válido.", { id: "missing-multiplayer" })
    } catch (err) {
        console.error(err)
        const message =
            err?.response?.data?.message ??
            err?.message ??
            "Ocorreu um erro ao comunicar com o servidor."
        error.value = message
        toast.error(message, { id: "game-start-error" })
    }
}

onMounted(() => {
    startGame()
});
watch(
    () => route.fullPath,
    () => {
        startGame()
    }
)
onUnmounted(() => {
    resetCountdown({ preserveOverride: true })
    stopMatchPolling()
})

watch(
    () => effectiveStatus.value,
    (status) => {
        if (!status || isLobbyStatus(status)) {
            resetCountdown()
            return
        }
        if (status === "Playing") {
            if (!countdownActive.value) {
                resetCountdown()
            }
            return
        }
        if (status === "Ended") {
            resetCountdown()
        }
    }
)

watch(
    () => [game.waitingForOpponent, effectiveStatus.value, game.state],
    () => {
        syncPendingGame()
    }
)

watch(
    () => effectiveStatus.value,
    (status) => {
        if (!status) return
        if (status === "Playing" && isLobbyRoute.value) {
            if (countdownActive.value) {
                pendingNavigateToGame.value = true
                return
            }
            if (route.params.matchId) {
                router.replace({
                    name: "match-game",
                    params: { matchId: route.params.matchId },
                })
                return
            }
            if (route.params.gameId) {
                router.replace({ name: "game-online", params: { gameId: route.params.gameId } })
            }
            return
        }
        if (isTableRoute.value && isLobbyStatus(status)) {
            if (route.params.matchId) {
                router.replace({ name: "match-online-lobby", params: { matchId: route.params.matchId } })
                return
            }
            if (route.params.gameId) {
                router.replace({ name: "game-online-lobby", params: { gameId: route.params.gameId } })
            }
        }
    }
)

watch(
    () => ({
        me: meReady.value,
        opponent: opponentReady.value,
        waiting: game.waitingForOpponent,
    }),
    ({ me, opponent, waiting }) => {
        if (!isLobbyRoute.value) {
            if (countdownActive.value) {
                resetCountdown()
            }
            return
        }
        if (waiting) {
            if (countdownActive.value) {
                resetCountdown()
            }
            return
        }

        if (me && opponent) {
            if (!countdownActive.value) {
                startCountdown()
            }
        } else if (countdownActive.value) {
            resetCountdown()
        }
    }
)

function retry() {
    startGame()
}

function retryRealtime() {
    if (!auth.token) return
    wsStore.connect(auth.token)
}

async function cancelLobby() {
    stopMatchPolling()
    waitingMatchId.value = null
    waitingMatchMeta.value = null
    pendingStore.clearMatch()
    try {
        const outcome = await game.cancelLobby()
        router.push('/play')
        if (outcome === "cancelled") {
            toast.success("Lobby cancelado com sucesso");
        } else {
            toast.success("Saíste do lobby");
        }
    } catch (err) {
        console.error("Falha ao cancelar lobby", err)
        toast.error(err?.response?.data?.message ?? "Não foi possível cancelar o lobby.")
    }
}

async function kickOpponent() {
    if (!game.gameId || kickingOpponent.value) return
    kickingOpponent.value = true
    try {
        await api.post(`/games/${game.gameId}/kick`)
        game.handleLobbyReset?.(game.gameId)
        toast.success("Adversário expulso do lobby")
    } catch (err) {
        const message = err?.response?.data?.message ?? "Não foi possível expulsar o adversário."
        toast.error(message, { id: "kick-error" })
    } finally {
        kickingOpponent.value = false
    }
}

function startCountdown() {
    resetCountdown()
    pendingNavigateToGame.value = true
    game.turnTimerFrozen = true
    countdownActive.value = true
    countdownTimer.value = setInterval(() => {
        countdownSeconds.value = countdownSeconds.value - 1
        if (countdownSeconds.value <= 0) {
            const shouldNavigate = pendingNavigateToGame.value
            resetCountdown({ keepTimerFrozen: true, preserveOverride: true })
            game.turnTimerOverride = true
            game.turnTimerRound = game.state?.round ?? null
            game.turnTimerStartedAt = null
            game.turnTimerDurationMs = 20000
            if (shouldNavigate && isLobbyRoute.value) {
                if (route.params.matchId) {
                    router.replace({
                        name: "match-game",
                        params: { matchId: route.params.matchId },
                    })
                } else if (route.params.gameId) {
                    router.replace({ name: "game-online", params: { gameId: route.params.gameId } })
                }
            }
            if (game.mode === "online" && game.gameId) {
                wsStore.send({ type: "join_game", gameId: game.gameId })
            }
            if (game.gameId) {
                api.get(`/games/${game.gameId}/state`)
                    .then((res) => {
                        if (res.data?.state) {
                            game.updateState(res.data.state)
                        }
                    })
                    .catch((err) => {
                        console.warn("Falha a obter estado do jogo após countdown", err?.response?.status || err)
                    })
            }
        }
    }, 1000)
}

function resetCountdown(options = {}) {
    const { keepTimerFrozen = false, preserveOverride = false } = options
    if (countdownTimer.value) {
        clearInterval(countdownTimer.value)
        countdownTimer.value = null
    }
    countdownSeconds.value = COUNTDOWN_TOTAL
    countdownActive.value = false
    pendingNavigateToGame.value = false
    game.turnTimerFrozen = keepTimerFrozen ? true : false
    if (!preserveOverride) {
        game.turnTimerOverride = false
    }
}
</script>
