<template>
    <div class="relative flex flex-col items-center">

        <span class="uppercase tracking-wide text-slate-400 text-[10px] mb-1">
            Deck
        </span>

        <CardImage v-if="deckCount > 0" :carta="CARTA_VIRADA" size="xl" />

        <div v-else
            class="h-[120px] w-[80px] rounded-lg border border-slate-700 bg-slate-800/40 flex items-center justify-center text-[10px] text-slate-400">

        </div>

        <div class="absolute mt-7 ml-10 bg-emerald-600 text-white text-sm
             rounded-full px-2 py-0.5 shadow-md font-semibold">
            {{ deckCount }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useGameStore } from '@/stores/gameStore'
import CardImage from './CardImage.vue'
import { CARTA_VIRADA } from '@/constants/cards'

const game = useGameStore()
const deckCount = computed(() => game.deckCount);
</script>
