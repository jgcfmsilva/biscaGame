<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Database Status -->
        <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-800 flex items-center justify-between group hover:border-blue-500/30 transition-all">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20">
                    <Database class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold tracking-wider">Base de Dados</p>
                    <p class="text-sm font-medium text-slate-200">{{ dbStatusLabel }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="['h-2.5 w-2.5 rounded-full', dbStatusColor]"></span>
                <span v-if="loading" class="text-xs text-slate-500">...</span>
            </div>
        </div>

        <!-- Redis Status -->
        <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-800 flex items-center justify-between group hover:border-red-500/30 transition-all">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-red-500/10 text-red-400 group-hover:bg-red-500/20">
                    <Layers class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold tracking-wider">Redis</p>
                    <p class="text-sm font-medium text-slate-200">{{ redisStatusLabel }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="['h-2.5 w-2.5 rounded-full', redisStatusColor]"></span>
                 <span v-if="loading" class="text-xs text-slate-500">...</span>
            </div>
        </div>

        <!-- WebSocket Status -->
        <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-800 flex items-center justify-between group hover:border-emerald-500/30 transition-all">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-500/20">
                    <Activity class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold tracking-wider">WebSocket</p>
                    <p class="text-sm font-medium text-slate-200">{{ wsStatusLabel }}</p>
                </div>
            </div>
             <div class="flex items-center gap-2">
                <span :class="['h-2.5 w-2.5 rounded-full', wsStatusColor]"></span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Database, Layers, Activity } from 'lucide-vue-next'
import api from '@/services/api'
import { useWsStore } from '@/stores/ws'

const wsStore = useWsStore()
const loading = ref(true)
const systemStatus = ref({
    database: 'checking',
    redis: 'checking'
})

// Computed Properties for UI
const dbStatusLabel = computed(() => {
    if (loading.value) return 'A verificar...'
    return systemStatus.value.database === 'online' ? 'Online' : 'Offline'
})

const dbStatusColor = computed(() => {
    if (loading.value) return 'bg-amber-500 animate-pulse'
    return systemStatus.value.database === 'online' ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-red-500'
})

const redisStatusLabel = computed(() => {
    if (loading.value) return 'A verificar...'
    return systemStatus.value.redis === 'online' ? 'Online' : 'Offline'
})

const redisStatusColor = computed(() => {
    if (loading.value) return 'bg-amber-500 animate-pulse'
    return systemStatus.value.redis === 'online' ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-red-500'
})

const wsStatusLabel = computed(() => {
    return wsStore.connected ? 'Conectado' : 'Desconectado'
})

const wsStatusColor = computed(() => {
    return wsStore.connected ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-red-500'
})

// Fetch Status
const checkSystemStatus = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/admin/system/status')
        systemStatus.value = data
    } catch (e) {
        console.error('Failed to fetch system status', e)
        systemStatus.value = { database: 'error', redis: 'error' }
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    checkSystemStatus()
    // Optional: Refresh every 30s
    // setInterval(checkSystemStatus, 30000)
})
</script>
