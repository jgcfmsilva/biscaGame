<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-6 md:py-8 space-y-6">
            <GameLoader v-if="showLoader" message="A preparar a partida..." />

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

                <GameHeader />
                <GameResultModal />
                <GameLayout />
            </template>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from "vue-sonner"

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import GameHeader from '@/components/gameplay/GameHeader.vue'
import GameLayout from '@/components/gameplay/GameLayout.vue'
import GameLoader from '@/components/gameplay/GameLoader.vue'
import GameResultModal from '@/components/gameplay/GameResultModal.vue'
import { Button } from "@/components/ui/button"

import { useGameStore } from '@/stores/gameStore'
import { useAuthStore } from '@/stores/auth'
import { useWsStore } from '@/stores/ws'

const route = useRoute()
const router = useRouter()
const game = useGameStore()
const auth = useAuthStore()
const wsStore = useWsStore()

const error = ref(null)
const LOBBY_STATUSES = new Set(["WaitingPlayers", "WaitingReady", "Pending"])
const effectiveStatus = computed(() => game.state?.status ?? game.serverStatus)

const showLoader = computed(() => !error.value && (game.loading || !effectiveStatus.value))

async function startGame() {
    try {
        error.value = null
        const matchId = route.params.matchId
        const gameId = route.params.gameId

        if (!auth.isAuthenticated) {
            await router.push({ name: "login", query: { redirect: route.fullPath } })
            return
        }

        if (matchId) {
            game.sessionMode = "match"
            game.matchId = matchId
            await game.startOnlineGame(gameId ?? null)
            return
        }

        if (gameId) {
            await game.startOnlineGame(gameId)
            return
        }

        await router.replace({ name: "play", query: { tab: "multi" } })
        toast.error("Seleciona um jogo multiplayer válido.", { id: "missing-multiplayer" })
    } catch (err) {
        console.error(err)
        game.loading = false
        const status = err?.response?.status
        if (status === 404) {
            await router.replace({ name: "play" })
            toast.error("Jogo indisponível.", { id: "missing-multiplayer" })
            return
        }
        const message =
            err?.response?.data?.message ??
            err?.message ??
            "Ocorreu um erro ao comunicar com o servidor."
        error.value = message
        toast.error(message, { id: "game-start-error" })
    }
}

function retry() {
    startGame()
}

function retryRealtime() {
    if (!auth.token) return
    wsStore.connect(auth.token)
}

onMounted(() => {
    startGame()
})

watch(
    () => route.fullPath,
    () => {
        startGame()
    }
)

onUnmounted(() => {
    // keep state for reconnection handled by store
})
</script>
