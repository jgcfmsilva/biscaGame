<template>
    <div class="min-h-screen flex flex-col bg-slate-950 text-slate-50">
        <AppNavbar />

        <OfflineReconnectBanner />

        <div v-if="showEmailVerificationWarning" class="bg-amber-500/15 border-b border-amber-400/50">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-3 text-sm text-amber-100 md:flex-row md:items-center md:gap-3">
                <div class="flex items-start gap-3">
                    <AlertTriangle class="h-8 w-8 mt-0.5 flex-shrink-0 text-amber-200" />
                    <div class="space-y-1">
                        <p class="font-semibold text-amber-50">Email ainda não verificado</p>
                        <p class="text-amber-100/90">
                            Confirma o endereço <strong class="font-semibold text-amber-50">{{ auth.user?.email }}</strong> através do link enviado para o teu email para desbloqueares todas as funcionalidades.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 md:ml-auto">
                    <button
                        type="button"
                        class="rounded-md border border-amber-300/70 bg-amber-400/20 px-3 py-1.5 text-xs font-semibold text-amber-50 hover:bg-amber-400/30 disabled:opacity-60"
                        :disabled="resendLoading"
                        @click="resendVerification"
                    >
                        {{ resendLoading ? 'A enviar...' : 'Reenviar email de verificação' }}
                    </button>
                    <span v-if="resendMessage" class="text-[11px] text-amber-200">{{ resendMessage }}</span>
                </div>
            </div>
        </div>

        <div v-if="activeMatchInProgress && !hideLobbyBanner && !auth.isAdmin" class="bg-amber-400/20 border-b border-amber-300/70">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2 text-sm text-amber-50">
                <span class="font-semibold">Partida em curso</span>
                <span class="text-amber-100/80">Tens um jogo a decorrer. Volta para a mesa para continuar.</span>
                <button
                    class="ml-auto rounded-lg bg-amber-400 px-3 py-1 text-xs font-semibold text-amber-950 hover:bg-amber-300 transition"
                    @click="goToActiveMatch">
                    Voltar à mesa
                </button>
            </div>
        </div>
        <div v-else-if="activeLobbyResolved && !hideLobbyBanner && !auth.isAdmin" class="bg-emerald-900/40 border-b border-emerald-700/60">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2 text-sm text-emerald-50">
                <span class="font-semibold">{{ bannerTitle }}</span>
                <span class="text-emerald-100/80">{{ bannerDescription }}</span>
                <button
                    class="ml-auto rounded-lg bg-emerald-500 px-3 py-1 text-xs font-semibold text-emerald-950 hover:bg-emerald-400 transition"
                    @click="goToLobby">
                    Ir para a sala de jogo
                </button>
            </div>
        </div>
        <div v-else-if="readyGameId && !hideLobbyBanner && !auth.isAdmin" class="bg-emerald-900/40 border-b border-emerald-700/60">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2 text-sm text-emerald-50">
                <span class="font-semibold">Adversário chegou</span>
                <span class="text-emerald-100/80">Abre a mesa, confirma que estás pronto e começa o jogo.</span>
                <button
                    class="ml-auto rounded-lg bg-emerald-500 px-3 py-1 text-xs font-semibold text-emerald-950 hover:bg-emerald-400 transition"
                    @click="openReadyMatch">
                    Ir para o match
                </button>
            </div>
        </div>

        <main class="flex-1">
            <slot />
        </main>

        <ChatBot />
        <VideoChat />
        <AppFooter />
    </div>
</template>

<script setup>
import AppNavbar from '@/components/layouts/AppNavbar.vue'
import AppFooter from '@/components/layouts/AppFooter.vue'
import OfflineReconnectBanner from '@/components/layouts/OfflineReconnectBanner.vue'
import ChatBot from '@/components/ChatBot.vue'
import VideoChat from '@/components/VideoChat.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePendingLobbyStore } from '@/stores/pendingLobby'
import { useGameStore } from '@/stores/gameStore'
import { useAuthStore } from '@/stores/auth'
import { AlertTriangle } from 'lucide-vue-next'
import api from '@/services/api'
import { toast } from 'vue-sonner'

const pendingStore = usePendingLobbyStore()
const game = useGameStore()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const resendLoading = ref(false)
const resendMessage = ref('')

const activeLobby = computed(() => pendingStore.pendingMatch ?? pendingStore.pendingGame)
const LOBBY_STATUSES = new Set(['WaitingPlayers', 'WaitingReady', 'Pending'])
const inferredLobby = computed(() => {
    if (activeLobby.value) return null
    if (game.mode !== 'online' || (!game.gameId && !game.matchId)) return null
    const status = game.state?.status ?? game.serverStatus
    const hasOpponent =
        Boolean(game.state?.opponent?.id) ||
        Boolean(game.playerNames?.opponentId)
    const isLobbyStatus = !status || LOBBY_STATUSES.has(status) || (game.waitingForOpponent && !hasOpponent)
    if (!isLobbyStatus) return null
    const isMatch = game.sessionMode === 'match' || Boolean(game.matchId)
    if (isMatch) {
        return {
            kind: 'match',
            id: game.matchId ?? game.gameId,
            matchId: game.matchId ?? null,
            type: game.currentVariant ?? null,
            stake: game.matchStake ?? null,
        }
    }
    return {
        kind: 'game',
        id: game.gameId,
        type: game.currentVariant ?? null,
        stake: game.entryStake ?? null,
    }
})
const activeLobbyResolved = computed(() => activeLobby.value ?? inferredLobby.value)
const activeMatchInProgress = computed(() =>
    game.mode === 'online' &&
    (game.gameId || game.matchId) &&
    game.state?.status === 'Playing'
)
const readyGameId = computed(() => pendingStore.readyGameId)

const hideLobbyBanner = computed(() =>
    ['game', 'game-online', 'game-online-lobby', 'match-game', 'match-online-lobby'].includes(route.name)
)
const hideVerificationBannerRoutes = new Set(['verify-email', 'verify-email-query', 'verify-email-link'])
const showEmailVerificationWarning = computed(() =>
    auth.isAuthenticated &&
    !auth.user?.email_verified_at &&
    !hideVerificationBannerRoutes.has(route.name)
)
const bannerTitle = computed(() => {
    if (activeLobbyResolved.value?.kind === 'match') {
        return 'Partida em espera'
    }
    return 'Lobby em espera'
})
const bannerDescription = computed(() => {
    const lobby = activeLobbyResolved.value
    if (!lobby) {
        return 'Tens um jogo a aguardar adversário.'
    }
    const isOwner = game.ownerId && auth.user?.id && game.ownerId === auth.user.id
    if (lobby.kind === 'match') {
        if (!isOwner) {
            return 'Estás num match em espera. Volta para a sala para continuar.'
        }
        const variant = lobby.type === '9' ? 'Bisca de 9' : 'Bisca de 3'
        const stakeLabel = lobby.stake ? ` · Stake ${lobby.stake} coins` : ''
        return `Tens um match (${variant}${stakeLabel}) à espera de adversário.`
    }
    if (!isOwner) {
        return 'Estás num lobby em espera. Volta para a sala para continuar.'
    }
    return 'Tens um jogo a aguardar adversário.'
})

const goToLobby = () => {
    const lobby = activeLobbyResolved.value
    if (!lobby) {
        router.push('/lobby')
        return
    }
    if (lobby.kind === 'match') {
        const matchId = lobby.matchId ?? lobby.id
        router.push({ path: `/match/${matchId}/lobby` })
        return
    }
    router.push({ path: `/game/${lobby.id}/lobby` })
}

const goToActiveMatch = () => {
    if (game.sessionMode === 'match' && game.matchId) {
        router.push({ path: `/match/${game.matchId}` })
        return
    }
    if (game.gameId) {
        router.push({ path: `/game/${game.gameId}` })
    }
}

const openReadyMatch = () => {
    if (!readyGameId.value) return
    router.push({ path: `/game/${readyGameId.value}/lobby` })
    pendingStore.readyGameId = null
}

async function resendVerification() {
    if (resendLoading.value) return
    resendLoading.value = true
    resendMessage.value = ''
    try {
        const { data } = await api.post('/email/resend')
        resendMessage.value = data?.message || 'Email de verificação enviado.'
        toast.success(resendMessage.value)
    } catch (error) {
        const message = error?.response?.data?.message || 'Não foi possível enviar o email de verificação.'
        resendMessage.value = message
        toast.error(message)
    } finally {
        resendLoading.value = false
    }
}

async function checkPending() {
    await pendingStore.refresh(auth.isAuthenticated)
}

onMounted(() => {
    checkPending()
})

</script>
