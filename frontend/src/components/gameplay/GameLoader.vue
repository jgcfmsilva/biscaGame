<template>
    <div class="flex flex-col items-center justify-center py-16 text-slate-300">
        <div class="relative w-12 h-12">
            <Spinner class="absolute inset-0 w-12 h-12 text-emerald-500" />
        </div>

        <p class="mt-4 text-sm opacity-80">{{ message }}</p>
    </div>
</template>

<script setup>
import Spinner from '../ui/spinner/Spinner.vue';

defineProps({
    message: { type: String, default: "Ocorreu um erro! A tentar carregar a partida..." }
})
</script>

<style scoped>
</style>
