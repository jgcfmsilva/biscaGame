<template>
    <div class="flex flex-col justify-between rounded-2xl border border-emerald-800/60 
           bg-gradient-to-b from-emerald-950/60 via-slate-950 to-slate-950 
           px-4 py-4 sm:px-6 sm:py-6 md:px-8 md:py-7 space-y-6 sm:space-y-10">
        <OpponentHand />

        <div class="flex flex-col items-center mt-2">
            <PlayedCards />
        </div>
    </div>
</template>


<script setup>
import OpponentHand from './OpponentHand.vue'
import PlayedCards from './PlayedCards.vue'
</script>
