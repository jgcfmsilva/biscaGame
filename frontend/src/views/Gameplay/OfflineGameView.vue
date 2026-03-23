<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-6 md:py-8 space-y-6">
            <GameLoader v-if="game.loading" message="A preparar a partida..." />

            <div v-else-if="error"
                class="rounded-xl border border-red-800 bg-red-950/70 p-6 text-center space-y-3 shadow-[0_18px_45px_rgba(127,29,29,0.45)]">
                <p class="text-red-300 font-semibold text-lg">
                    Não foi possível iniciar o jogo offline
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
                <GameHeader />
                <GameResultModal />
                <GameLayout />
            </template>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, watch, ref } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import GameHeader from '@/components/gameplay/GameHeader.vue'
import GameLayout from '@/components/gameplay/GameLayout.vue'
import GameLoader from '@/components/gameplay/GameLoader.vue'
import GameResultModal from '@/components/gameplay/GameResultModal.vue'
import { useGameStore } from '@/stores/gameStore'

const route = useRoute()
const game = useGameStore()
const error = ref(null)

const typeParam = computed(() => route.query.type ?? "3")
const modeParam = computed(() => route.query.mode === "match" ? "match" : "game")

async function startOfflineSession() {
    try {
        error.value = null
        game.sessionMode = modeParam.value
        game.matchGameNumber = 1
        game.currentVariant = typeParam.value

        const reconnected = await game.reconnectOffline()
        if (!reconnected) {
            await game.startOffline(typeParam.value)
        }
    } catch (err) {
        console.error(err)
        game.loading = false
        const message =
            err?.response?.data?.message ??
            err?.message ??
            "Ocorreu um erro ao iniciar o jogo offline."
        error.value = message
        toast.error(message, { id: "offline-game-error" })
    }
}

function retry() {
    startOfflineSession()
}

watch(
    () => `${typeParam.value}-${modeParam.value}`,
    () => {
        startOfflineSession()
    }
)

onMounted(() => {
    startOfflineSession()
})
</script>
