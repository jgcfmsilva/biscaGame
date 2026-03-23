<template>
    <div id="game-board-area"
        class="rounded-3xl border border-slate-800 bg-slate-950/80 p-4 md:p-6 shadow-[0_18px_45px_rgba(15,23,42,0.85)] space-y-6">
        <div class="grid gap-4 lg:grid-cols-[1fr_minmax(0,1.2fr)1fr]">

            <div class="order-2 flex flex-col items-center gap-4 lg:order-none">
                <Deck />
                <Trunfo />
            </div>

            <div class="order-3 lg:order-none">
                <GameTable />
            </div>

            <div class="order-1 flex flex-col justify-between gap-4 lg:order-none">
                <GameDetails />
            </div>
        </div>

        <PlayerHand />
    </div>
</template>

<script setup>
import GameTable from './GameTable.vue'
import PlayerHand from './PlayerHand.vue'
import GameDetails from './GameDetails.vue'
import Trunfo from './Trunfo.vue'
import Deck from './Deck.vue'
</script>
