<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <Avatar class="h-10 w-10">
                    <AvatarImage :src="playerAvatar" />
                    <AvatarFallback class=" text-xs">{{ initials }}</AvatarFallback>
                </Avatar>
                <div class="leading-tight">
                    <p class="text-[15px] text-slate-200 font-medium">
                        Tu
                    </p>

                    <p class="text-[12px] font-medium mt-1" :class="isMyTurn
                        ? 'text-emerald-400'
                        : 'text-blue-400'">
                        {{ isMyTurn ? 'A tua vez!' : 'Aguarda…' }}
                    </p>
                </div>
            </div>

            <div v-if="isMyTurn" class="px-2 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-400/40 
                       text-[12px] text-emerald-300 font-semibold shadow-md">
                TUA VEZ
            </div>

            <div v-else class="px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-400/30 
                       text-[12px] text-blue-300 font-medium shadow-sm">
                O ADVERSÁRIO ESTÁ A JOGAR
            </div>
        </div>

        <!-- Caso nao tenha cartas nenhumas na mao adicionar algo a dizer que nao tem cartas-->
        <div class="flex flex-nowrap justify-start gap-3 sm:gap-4 px-3 sm:px-4 py-3 rounded-xl border shadow-inner overflow-x-auto sm:overflow-visible sm:flex-wrap sm:justify-center"
            :class="isMyTurn
                ? 'bg-slate-900/70 border-emerald-500/30 shadow-[0_0_25px_rgba(16,185,129,0.25)]'
                : 'bg-slate-900/60 border-slate-800 shadow-[0_0_20px_rgba(59,130,246,0.12)]'">
            <div v-for="slot in maxSlots" :key="slot" class="slot-wrap">
                <Transition name="hand-slot" mode="out-in">
                    <button v-if="hand[slot - 1]"
                        :key="hand[slot - 1].naipe + '-' + hand[slot - 1].valor"
                        :disabled="!isMyTurn || game.playCardLocked"
                        @click="playCard(slot - 1)"
                        class="relative group focus-visible:outline-none"
                        :class="isMyTurn && !game.playCardLocked
                            ? ''
                            : 'opacity-70 cursor-not-allowed'">
                        <CardImage :carta="hand[slot - 1]" size="hand"
                            :class="isMyTurn && !game.playCardLocked
                                ? ''
                                : 'shadow-[0_0_6px_rgba(59,130,246,0.15)]'" />
                    </button>
                    <div v-else :key="'empty-' + slot" class="slot-placeholder"></div>
                </Transition>
            </div>
        </div>

    </div>
</template>

<script setup>
import { useGameStore } from '@/stores/gameStore'
import { useAuthStore } from '@/stores/auth'
import { computed } from 'vue'
import CardImage from '@/components/gameplay/CardImage.vue'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'


const game = useGameStore();
const auth = useAuthStore()
const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')
const resolveAvatar = (src) => {
    const base = apiBase || window.location.origin
    const defaultAvatar = `${base}/api/player/avatar/photos_avatars/anonymous.png`
    if (!src) return defaultAvatar
    if (/^https?:\/\//i.test(src)) return src
    let normalized = src.startsWith('/') ? src.slice(1) : src
    if (!normalized.includes('/')) {
        normalized = `photos_avatars/${normalized}`
    }
    return `${base}/api/player/avatar/${normalized}`
}
const playerAvatar = computed(() => resolveAvatar(auth.user?.photo_avatar_filename))

const initials = computed(() => {
    if (!auth.user?.name) return "?"
    return auth.user.name.split(" ").map(w => w[0]).join("").toUpperCase()
})


const hand = computed(() => game.me?.hand ?? []);
const isMyTurn = computed(() => game.currentTurn === "me" && !game.botActing);
const maxSlots = computed(() => game.state?.type === "9" ? 9 : 3);

function playCard(index) {
    if (!isMyTurn.value) return;
    game.playCard(index);
}

</script>

<style scoped>
.slot-wrap {
    width: clamp(56px, 12vw, 90px);
    height: clamp(84px, 18vw, 136px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.slot-placeholder {
    width: clamp(56px, 12vw, 90px);
    height: clamp(84px, 18vw, 136px);
}

.hand-slot-enter-active,
.hand-slot-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}

.hand-slot-enter-from,
.hand-slot-leave-to {
    opacity: 0;
    transform: translateY(6px) scale(0.98);
}

</style>
