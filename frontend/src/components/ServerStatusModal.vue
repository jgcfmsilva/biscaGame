<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in zoom-in duration-200"
        @click.self="close">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl relative">
            
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <h3 class="text-lg font-semibold text-slate-100 flex items-center gap-2">
                    <Activity class="h-5 w-5 text-emerald-400" />
                    Estado do Servidor
                </h3>
                <button @click="close" class="text-slate-400 hover:text-slate-100 transition">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Status Indicator -->
                <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="relative flex h-12 w-12 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-20"></span>
                        <div class="relative inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                            <Wifi class="h-5 w-5" />
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-200">Serviço Operacional</p>
                        <p class="text-xs text-slate-400">Todos os sistemas a funcionar normalmente.</p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-900/30 border border-slate-800/50 flex flex-col items-center justify-center text-center">
                        <Users class="h-5 w-5 text-blue-400 mb-2" />
                        <span class="text-2xl font-bold text-slate-100">{{ playersOnline }}</span>
                        <span class="text-xs text-slate-500 uppercase tracking-wide mt-1">Online Agora</span>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/30 border border-slate-800/50 flex flex-col items-center justify-center text-center">
                        <Clock class="h-5 w-5 text-amber-400 mb-2" />
                        <span class="text-2xl font-bold text-slate-100">{{ latency }}ms</span>
                        <span class="text-xs text-slate-500 uppercase tracking-wide mt-1">Latência</span>
                    </div>
                </div>

                <!-- Last Checked -->
                <div class="text-center">
                    <p class="text-[10px] text-slate-600 uppercase tracking-widest">
                        Última verificação: {{ lastChecked }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { Activity, X, Wifi, Users, Clock } from 'lucide-vue-next'

defineProps({
    isOpen: Boolean,
    playersOnline: {
        type: Number,
        default: 0
    },
    latency: {
        type: [Number, String],
        default: '—'
    },
    lastChecked: {
        type: String,
        default: 'Agora mesmo'
    }
})

const emit = defineEmits(['close'])

const close = () => {
    emit('close')
}
</script>
