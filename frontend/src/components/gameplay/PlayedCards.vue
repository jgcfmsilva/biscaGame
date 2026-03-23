<template>
    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-5 relative">
        <div class="flex flex-col items-center gap-1 text-[11px] text-slate-400">
            <span class="mb-1">Minha Carta</span>
            <Transition name="played-card" mode="out-in">
                <div v-if="playerCard" :key="'player-card'" class="card-slot">
                    <CardImage :carta="playerCard" size="table" class="drop-shadow-[0_8px_16px_rgba(0,0,0,0.5)]" />
                </div>
                <div v-else :key="'player-placeholder'" class="card-placeholder card-slot h-24 w-16 sm:h-28 sm:w-20 md:h-32 md:w-22"></div>
            </Transition>
        </div>

        <div class="flex flex-col items-center gap-2 text-[11px] text-slate-400 mx-4">
            <span class="text-sm font-mono">
                Vaza {{ round }}
            </span>

            <span class="h-px w-14" :class="currentTurn === 'me'
                ? 'bg-emerald-500/70'
                : 'bg-blue-400/60'"></span>

            <span v-if="game.sessionMode === 'match'" class="text-sm font-mono">
                Jogo {{ gameNumber }}
            </span>
        </div>

        <div class="flex flex-col items-center gap-1 text-[11px] text-slate-400">
            <span class="mb-1">Adversário</span>
            <Transition name="played-card" mode="out-in">
                <div v-if="opponentCard" :key="'op-card'" class="card-slot">
                    <CardImage :carta="opponentCard" size="table" class="drop-shadow-[0_6px_12px_rgba(0,0,0,0.45)]" />
                </div>
                <div v-else :key="'op-placeholder'" class="card-placeholder card-slot h-24 w-16 sm:h-28 sm:w-20 md:h-32 md:w-22"></div>
            </Transition>
        </div>
    </div>
</template>

<script setup>
import { useGameStore } from '@/stores/gameStore'
import CardImage from '@/components/gameplay/CardImage.vue'
import { computed } from 'vue'

const game = useGameStore()

const playerCard = computed(() => {
    if (game.mode === "offline") {
        return game.lastPlayedCards?.player1 ?? null;
    }
    return game.lastPlayedCards?.player1 ?? game.me?.playedCard ?? null;
});
const opponentCard = computed(() => {
    if (game.mode === "offline") {
        return game.lastPlayedCards?.player2 ?? null;
    }
    return game.lastPlayedCards?.player2 ?? game.opponent?.playedCard ?? null;
});

const round = computed(() => game.round);
const currentTurn = computed(() => game.currentTurn);
const gameNumber = computed(() => game.matchGameNumber);
</script>

<style scoped>
.card-placeholder {
    border: 2px dashed rgba(148, 163, 184, 0.7);
    border-radius: 0.5rem;
}

.card-slot {
    transition: opacity 220ms ease, transform 220ms ease;
}

.played-card-enter-from,
.played-card-leave-to {
    opacity: 0;
    transform: translateY(6px) scale(0.98);
}

.played-card-enter-active,
.played-card-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}
</style>
