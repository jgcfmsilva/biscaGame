<template>
    <img :src="src" :alt="alt" class="select-none pointer-events-none" :class="[
        'shadow-[0_4px_18px_rgba(0,0,0,0.45)] object-contain',
        sizeClass
    ]" draggable="false" />
</template>

<script setup>
import { computed } from "vue"
import { CARTA_VIRADA, cartaPath } from "@/constants/cards"

const props = defineProps({
    carta: { type: Object, default: null },
    size: { type: String, default: "md" },
})

const sizeClass = computed(() => ({
    sm: "h-14 w-10",
    md: "h-18 w-13",
    lg: "h-24 w-16",
    xl: "h-32 w-22",
    xxl: "h-40 w-28",
    hand: "h-20 w-14 sm:h-24 sm:w-16 md:h-28 md:w-20 lg:h-32 lg:w-22",
    "hand-opponent": "h-16 w-11 sm:h-20 sm:w-14 md:h-24 md:w-16 lg:h-28 lg:w-20",
    "hand-opponent-compact": "h-14 w-10 sm:h-18 sm:w-12 md:h-22 md:w-14 lg:h-24 lg:w-16",
    "hand-opponent-9": "h-12 w-8 sm:h-16 sm:w-11 md:h-20 md:w-13 lg:h-22 lg:w-15",
    table: "h-24 w-16 sm:h-28 sm:w-20 md:h-32 md:w-22"
}[props.size]))

const src = computed(() => {
    if (!props.carta || props.carta.valor == null || props.carta.naipe == null) {
        return CARTA_VIRADA.img
    }

    return cartaPath(props.carta.naipe, props.carta.valor)
})

const alt = computed(() => {
    if (!props.carta) return 'Carta virada'
    return `Carta ${props.carta.naipe}${props.carta.valor}`
})
</script>

<style scoped>
img {
    user-select: none;
}
</style>
