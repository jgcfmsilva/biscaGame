<template>
    <div class="flex flex-col items-center gap-4">
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <Avatar class="h-10 w-10">
                <AvatarImage :src="opponentAvatar" />
                <AvatarFallback class=" text-xs">{{ initials }}</AvatarFallback>
            </Avatar>
            <div class="flex flex-col leading-tight">
                <span class="text-[13px] text-slate-300">{{ opponentLabel }}</span>
                <span class="text-[12px] mt-1" :class="game.currentTurn === 'opponent'
                    ? 'text-red-300'
                    : 'text-slate-500'">
                    {{ game.currentTurn === 'opponent' ? 'A jogar...' : 'A aguardar...' }}
                </span>
            </div>
        </div>

        <div class="relative">
            <div :class="handLayoutClass">
                <div v-for="index in handCount" :key="index" class="relative group">
                    <CardImage :carta="CARTA_VIRADA" :size="opponentCardSize" class="shadow-[0_0_6px_rgba(0,0,0,0.35)]" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useGameStore } from '@/stores/gameStore';
import { CARTA_VIRADA } from '@/constants/cards'
import { computed } from 'vue';
import CardImage from '@/components/gameplay/CardImage.vue'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'

const game = useGameStore()

const handCount = computed(() => game.opponent?.handSize ?? game.lastOpponentHandSize ?? 0);
const isBisca9 = computed(() => game.state?.type === "9");
const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')
const formatAvatarPath = (src) => {
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
const opponentLabel = computed(() => {
    if (game.mode !== "online") return "Bot";
    return game.opponent?.nickname ?? game.playerNames?.opponent ?? "Adversário";
});
const opponentCardSize = computed(() => (game.state?.type === "9" ? "hand-opponent-9" : "hand-opponent"));
const handLayoutClass = computed(() =>
    isBisca9.value
        ? "flex flex-nowrap justify-start gap-1 overflow-x-auto md:grid md:grid-cols-9 md:justify-items-center md:gap-1 md:overflow-visible"
        : "flex flex-nowrap justify-center gap-2 overflow-x-auto md:grid md:grid-cols-3 md:gap-3 md:justify-items-center md:overflow-visible"
);
const opponentAvatar = computed(() =>
    formatAvatarPath(game.opponent?.photo_avatar_filename) ||
    formatAvatarPath(game.playerAvatars?.opponent) ||
    formatAvatarPath('')
);
const initials = computed(() => {
    const name = opponentLabel.value || "Adversário";
    const parts = name.split(' ').filter(Boolean);
    if (!parts.length) return "AD";
    const first = parts[0][0] ?? '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] ?? '' : '';
    return (first + last).toUpperCase() || "AD";
});
</script>
