<template>
    <Transition name="reconnect-banner">
        <div v-if="shouldShow" class="bg-amber-500/15 border-b border-amber-400/40 text-amber-100">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 gap-3 text-sm">
                <div class="flex items-center gap-2">
                    <span class="i-lucide-plug-zap h-5 w-5"></span>
                    <div class="leading-tight">
                        <p class="font-semibold">Partida offline a decorrer</p>
                        <p class="text-amber-200/90 text-[13px]">
                            Retoma para não perderes a sessão atual.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span v-if="snapshot"
                        class="rounded-full border border-amber-400/50 px-3 py-1 text-[12px] text-amber-50/90">
                        Jogo Bisca de {{ snapshot.type === '9' ? '9' : '3' }} · Vaza {{ snapshot.round ?? 1 }}
                    </span>
                    <button @click="goToGame"
                        class="inline-flex items-center gap-1 rounded-lg bg-amber-400/80 px-3 py-1.5 text-slate-900 font-semibold hover:bg-amber-300 transition">
                        Reconnectar
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useGameStore } from '@/stores/gameStore'
import { ArrowUpRight, RotateCw } from 'lucide-vue-next'

const game = useGameStore()
const router = useRouter()
const route = useRoute()

const snapshot = computed(() => game.pendingOfflineState || game.state)
const shouldShow = computed(() =>
    game.offlineReconnectAvailable &&
    !(route.name === "game" && game.mode === "offline" && game.state && !game.gameEnded)
)

async function refreshReconnect() {
    try {
        await game.checkOfflinePersistedGame()
    } catch (err) {
        console.warn("Offline reconnect check failed", err)
    }
}

onMounted(refreshReconnect)

watch(() => route.fullPath, () => {
    refreshReconnect()
})

function goToGame() {
    router.push({ name: 'game' })
}
</script>

<style scoped>
.reconnect-banner-enter-active,
.reconnect-banner-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.reconnect-banner-enter-from,
.reconnect-banner-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
