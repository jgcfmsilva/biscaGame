<template>
    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-5 py-5
                space-y-6 text-[12px] text-slate-300 w-full">

        <div v-if="showPotTotal" class="flex items-center justify-between">
            <span class="text-[10px] uppercase tracking-wide text-slate-400">
                Partida
            </span>
            <span>
                Pote total:
                <span class="text-amber-300 font-semibold">{{ potTotal }}</span>
                moedas
            </span>
        </div>

        <div class="space-y-2">
            <p class="text-[10px] uppercase tracking-wide text-slate-400">Tempo da jogada</p>

            <div class="relative h-4 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="absolute left-0 top-0 h-full" :class="{
                    'bg-gradient-to-r from-red-500 to-red-600': isLowTime,
                    'bg-gradient-to-r from-emerald-400 to-emerald-500': !isLowTime
                }" :style="{ width: progressPercent + '%' }"></div>

                <div class="absolute inset-0 flex items-center justify-center text-[11px] font-medium"
                    :class="isLowTime ? 'text-red-200' : 'text-white'">
                    {{ Math.ceil(remainingSeconds) }}s
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-slate-900/60 border border-slate-800 px-4 py-4 space-y-3">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Pontos</p>

            <div class="flex items-center justify-between">

                <div class="flex flex-col items-center gap-1 w-full">
                    <p class="text-[11px] text-emerald-400 uppercase">Tu</p>

                    <div class="score-slot">
                        <p class="text-2xl font-semibold text-slate-100 score-value" :class="{ 'score-pulse': meScorePulse }">
                            {{ me.score }}
                        </p>
                    </div>
                </div>

                <div class="px-2 text-slate-600 font-bold text-xl">·</div>

                <div class="flex flex-col items-center gap-1 w-full">
                    <p class="text-[11px] text-red-400 uppercase">Adv</p>

                    <div class="score-slot">
                        <p class="text-2xl font-semibold text-slate-100 score-value" :class="{ 'score-pulse': opponentScorePulse }">
                            {{ opponent.score }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-slate-900/40 border border-slate-800 px-4 py-4 space-y-2">

            <p class="text-[10px] uppercase tracking-wide text-slate-500">Marcas</p>

            <div class="flex items-center justify-between">
                <div class="flex flex-col items-center gap-1 w-full">
                    <p class="text-[11px] text-emerald-400 uppercase">Tu</p>

                    <p class="text-lg font-semibold text-slate-100">
                        {{ meMarks }}
                    </p>
                </div>

                <div class="px-2 text-slate-600 font-bold text-xl">·</div>

                <div class="flex flex-col items-center gap-1 w-full">
                    <p class="text-[11px] text-red-400 uppercase">Adv</p>

                    <p class="text-lg font-semibold text-slate-100">
                        {{ opponentMarks }}
                    </p>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { useGameStore } from '@/stores/gameStore'
import { computed, onBeforeUnmount, ref, watch, watchEffect } from 'vue'

const game = useGameStore()

const me = computed(() => game.me ?? { score: 0, marks: 0 });
const opponent = computed(() => game.opponent ?? { score: 0, marks: 0 });

const meMarks = computed(() => {
    if (game.sessionMode === "match") return game.matchMarks?.me ?? 0;
    return me.value.marks ?? 0;
});

const opponentMarks = computed(() => {
    if (game.sessionMode === "match") return game.matchMarks?.opponent ?? 0;
    return opponent.value.marks ?? 0;
});

const showPotTotal = computed(() => game.mode === "online");
const potTotal = computed(() => {
    if (game.sessionMode === "match") {
        const stake = Number(game.matchStake ?? 0);
        return stake > 0 ? stake * 2 : "–";
    }
    const entry = Number(game.entryStake ?? 0);
    return entry > 0 ? entry * 2 : "–";
});

const DEFAULT_TURN_DURATION_MS = 20000;
const totalMs = computed(() => game.turnTimerDurationMs ?? DEFAULT_TURN_DURATION_MS);
const remainingMs = ref(totalMs.value);
const currentRound = computed(() => game.state?.round ?? null);
let intervalId = null;
const meScorePulse = ref(false);
const opponentScorePulse = ref(false);

const remainingSeconds = computed(() => remainingMs.value / 1000);
const progressPercent = computed(() => {
    return Math.max(
        0,
        Math.min(100, (remainingMs.value / totalMs.value) * 100),
    );
});

const isLowTime = computed(() => remainingMs.value <= 5000);

const timedOut = ref(false);
const lastTurn = ref(null);
const isTimerPaused = computed(() => Boolean(game.turnTimerFrozen));

function syncRemainingFromSnapshot() {
    const duration = totalMs.value;
    const startedAt = game.turnTimerStartedAt ?? Date.now();
    const elapsed = Date.now() - startedAt;
    remainingMs.value = Math.max(0, duration - elapsed);
}

function startTimer() {
    stopTimer({ preserveSnapshot: true });
    timedOut.value = false;

    const duration = totalMs.value;
    const round = currentRound.value;

    if (game.turnTimerRound !== round || !game.turnTimerStartedAt) {
        game.turnTimerRound = round;
        game.turnTimerStartedAt = Date.now();
        game.turnTimerDurationMs = duration;
    }

    syncRemainingFromSnapshot();

    if (remainingMs.value <= 0) {
        stopTimer();
        onTimeout();
        return;
    }

    intervalId = setInterval(() => {
        syncRemainingFromSnapshot();
        if (remainingMs.value <= 0) {
            stopTimer();
            onTimeout();
        }
    }, 100);
}

function stopTimer(options = {}) {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
    if (!options.preserveSnapshot) {
        game.turnTimerRound = null;
        game.turnTimerStartedAt = null;
    }
}

watchEffect(() => {
    if (game.realtimeError) {
        stopTimer();
        remainingMs.value = totalMs.value;
        lastTurn.value = null;
        return;
    }
    const isPlaying = game.state?.status === "Playing" && !game.gameEnded;
    const hasTurn = Boolean(game.currentTurn);
    if (isPlaying && hasTurn) {
        if (lastTurn.value !== game.currentTurn) {
            game.turnTimerRound = currentRound.value;
            game.turnTimerStartedAt = Date.now();
            game.turnTimerDurationMs = totalMs.value;
            lastTurn.value = game.currentTurn;
        }
        startTimer();
        return;
    }
    stopTimer();
    remainingMs.value = totalMs.value;
    lastTurn.value = null;
});

function triggerScorePulse(target) {
    target.value = false;
    requestAnimationFrame(() => {
        target.value = true;
        setTimeout(() => target.value = false, 420);
    });
}

watch(() => me.value?.score, (newVal, oldVal) => {
    if (newVal == null || oldVal == null || newVal === oldVal) return;
    triggerScorePulse(meScorePulse);
});

watch(() => opponent.value?.score, (newVal, oldVal) => {
    if (newVal == null || oldVal == null || newVal === oldVal) return;
    triggerScorePulse(opponentScorePulse);
});

onBeforeUnmount(() => {
    if (game.state?.status === "Playing" && game.currentTurn && !game.gameEnded) {
        stopTimer({ preserveSnapshot: true });
    } else {
        stopTimer();
    }
});

function onTimeout() {
    if (timedOut.value) return;
    timedOut.value = true;
    game.handleTimeout?.();
}
</script>

<style scoped>
.score-slot {
    min-height: 2.6rem; /* keeps height stable to avoid layout jump */
    display: flex;
    align-items: center;
    justify-content: center;
}

.score-value {
    transition: transform 220ms ease, color 220ms ease;
}

.score-pulse {
    color: #a5f3fc;
    transform: scale(1.08);
}
</style>
