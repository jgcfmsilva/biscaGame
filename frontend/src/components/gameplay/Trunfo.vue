<template>
    <div class="flex flex-col items-center gap-1">
        <span class="uppercase tracking-wide text-slate-400 text-[10px] mb-1">
            Trunfo
        </span>
        <CardImage v-if="trumpCard" :carta="trumpCard" size="xl"
            class="shadow-[0_0_15px_rgba(34,197,94,0.35)] border-emerald-500/50" />
        <div v-else
            class="h-[160px] w-[110px] rounded-lg border border-slate-700 bg-slate-800/40 flex items-center justify-center text-[10px] text-slate-400">
            Sem trunfo
        </div>
    </div>
</template>

<script setup>
import CardImage from './CardImage.vue'
import { computed } from 'vue'
import { useGameStore } from '@/stores/gameStore'

const game = useGameStore()
const trumpCard = computed(() => game.trumpCard);
</script>
