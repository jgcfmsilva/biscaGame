<template>
    <header class="flex items-center justify-between gap-3">
        <Button class="btn bg-red-600 hover:bg-red-500 border-red-500 text-white px-3 py-1.5 text-xs md:text-sm" @click="handleResign">
            <Flag class="w-4 h-4 opacity-90" />
            Desistir
        </Button>

        <div class="text-center flex-1 hidden md:block">
            <p class="text-sm text-slate-500">
                {{ game.sessionMode === 'match' ? 'Partida' : 'Jogo' }}
                · Bisca {{ game.state?.type }}
            </p>
            <p class="text-md text-slate-300">
                {{ myName }}
                <span class="text-slate-500">vs</span>
                {{ game.mode === "online" ? opponentName : "Bot" }}
            </p>
        </div>

        <div class="flex items-center gap-3 text-sm text-slate-400">
            <Button variant="ghost" size="icon" class="text-slate-400 hover:text-white hover:bg-slate-800" @click="takeSnapshot" title="Guardar imagem do jogo">
                <Camera class="w-5 h-5" />
            </Button>

            <div class="flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full" :class="game.mode === 'online'
                    ? (ws.authenticated ? 'bg-emerald-400' : 'bg-red-500')
                    : 'bg-slate-500'"></span>

                <span>
                    <template v-if="game.mode === 'online'">
                        {{ ws.authenticated ? "Conectado" : "Desconectado" }}
                    </template>
                    <template v-else>
                        Offline
                    </template>
                </span>
            </div>
            <div v-if="game.mode === 'online'" class="flex items-center gap-1">
                <span class="text-slate-500">Ping</span>
                <span class="text-slate-300">
                    {{ ws.authenticated && ws.lastPingMs != null ? `${ws.lastPingMs}ms` : "—" }}
                </span>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Flag, Camera } from 'lucide-vue-next';
import { useGameStore } from '@/stores/gameStore'
import { useAuthStore } from '@/stores/auth'
import { useWsStore } from '@/stores/ws'
import { toPng } from 'html-to-image';

const auth = useAuthStore()
const game = useGameStore()
const ws = useWsStore()

const handleResign = () => {
    game.resign()
}

const takeSnapshot = async () => {
    const element = document.getElementById('game-board-area');
    if (!element) return;
    
    try {
        const dataUrl = await toPng(element, {
            backgroundColor: '#020617', // slate-950
            pixelRatio: 2, // High resolution
            pixelRatio: 2, // High resolution
            filter: (node) => {
                // Ignore video elements and problematic avatar images (CORS/Load errors)
                if (node.tagName === 'VIDEO') return false;
                if (node.tagName === 'IMG' && node.classList?.contains('aspect-square') && node.classList?.contains('size-full')) return false;
                return true;
            },
            skipOnError: true,
        });
        
        const link = document.createElement('a');
        link.download = `bisca-snapshot-${new Date().toISOString().slice(0,19).replace(/:/g,'-')}.png`;
        link.href = dataUrl;
        link.click();
    } catch (error) {
        console.error('Failed to take snapshot:', error);
    }
}

const myName = computed(() => auth.user?.nickname ?? auth.user?.name ?? "Tu")
const opponentName = computed(() => {
    const nickname = game.opponent?.nickname ?? game.playerNames?.opponent
    return nickname || "Adversário"
})
</script>
