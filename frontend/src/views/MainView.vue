<template>
    <DefaultLayout>
        <section class="mx-auto flex max-w-7xl flex-col gap-12 px-4 py-12 md:py-16">
            <div v-if="auth.isAdmin" class="space-y-8">
                <!-- 1. Compact Status Bar -->
                <div class="rounded-full border border-slate-800 bg-slate-950/80 px-6 py-3 flex flex-wrap items-center justify-between gap-4 shadow-lg backdrop-blur-md">
                   <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                             <span class="relative flex h-2.5 w-2.5">
                                <span :class="['absolute inline-flex h-full w-full animate-ping rounded-full opacity-75', serverStatusColor]"></span>
                                <span :class="['relative inline-flex h-2.5 w-2.5 rounded-full', serverStatusColor]"></span>
                            </span>
                            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Estado do Sistema</span>
                            <span :class="['text-xs font-bold', serverInfo.status === 'online' ? 'text-emerald-400' : 'text-rose-400']">
                                {{ serverInfo.status === 'online' ? 'ONLINE' : 'OFFLINE' }}
                            </span>
                        </div>
                        <div class="h-4 w-px bg-slate-800 hidden sm:block"></div>
                        <div class="hidden sm:flex items-center gap-4 text-xs text-slate-500">
                             <span v-for="service in serviceDefinitions" :key="service.key" class="flex items-center gap-1.5">
                                <component :is="service.icon" class="h-3 w-3" :class="getServiceColor(service.key)" />
                                <span :class="getServiceColor(service.key)">{{ service.label }}</span>
                             </span>
                        </div>
                   </div>

                   <div class="flex items-center gap-3">
                        <span v-if="adminLastUpdated" class="text-[10px] text-slate-600 font-mono tracking-tight">UPDATED {{ adminLastUpdated }}</span>
                        <Button 
                            size="sm" 
                            variant="ghost" 
                            class="relative h-9 px-4 gap-2 overflow-hidden text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 transition-all duration-300 group ring-1 ring-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.2)] hover:shadow-[0_0_30px_rgba(16,185,129,0.3)] hover:ring-emerald-500/50" 
                            :class="{ 'animate-pulse shadow-[0_0_25px_rgba(16,185,129,0.4)]': !showExtendedStats }"
                            @click="showExtendedStats = !showExtendedStats"
                        >
                             <div class="absolute inset-0 bg-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                             <Activity class="h-4 w-4 relative z-10" />
                             <span class="text-xs font-semibold uppercase tracking-wider relative z-10">System Pulse</span>
                        </Button>
                        <Button 
                            size="xs" 
                            variant="outline" 
                            class="h-9 px-3 border-slate-800 bg-slate-900/50 text-[10px] font-medium uppercase tracking-wider text-slate-400 hover:border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-400 transition-all duration-300" 
                            :disabled="adminStatusLoading" 
                            @click="fetchAdminStatus"
                        >
                             <RotateCw v-if="adminStatusLoading" class="mr-2 h-3.5 w-3.5 animate-spin" />
                             <RotateCw v-else class="mr-2 h-3.5 w-3.5" />
                             <span v-if="adminStatusLoading">Updating...</span>
                             <span v-else>Refresh</span>
                        </Button>
                   </div>
                </div>

                <!-- 1.5 Extended Stats Panel -->
                <div v-if="showExtendedStats" class="animate-fade-in-up rounded-2xl border border-slate-800 bg-slate-950/50 p-6 shadow-inner">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">
                        <Activity class="h-4 w-4 text-emerald-500" />
                        Métricas de Latência
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div v-for="item in latencyChart" :key="item.key" class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-2 text-slate-300 font-medium">
                                    <component :is="serviceDefinitions.find(s => s.key === item.key)?.icon" class="h-3 w-3 text-slate-500" />
                                    {{ item.label }}
                                </span>
                                <span class="font-mono text-emerald-400">{{ item.latency }}ms</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-900">
                                <div
                                    class="h-full rounded-full transition-all duration-1000 ease-out relative"
                                    :style="{ width: `${item.width}%`, background: item.gradient }"
                                >
                                    <div class="absolute right-0 top-0 bottom-0 w-px bg-white/50 shadow-[0_0_10px_rgba(255,255,255,0.5)]"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800/50 grid grid-cols-2 md:grid-cols-4 gap-4">
                         <div v-for="service in serviceDefinitions" :key="service.key" class="p-3 rounded-xl bg-slate-900/50 border border-slate-800 flex flex-col gap-2">
                              <component :is="service.icon" class="h-5 w-5" :class="getServiceColor(service.key)" />
                              <div>
                                  <p class="text-xs font-semibold text-slate-300">{{ service.label }}</p>
                                  <p class="text-[10px] text-slate-500">{{ service.status === 'online' ? 'Operacional' : 'Com problemas' }}</p>
                              </div>
                         </div>
                    </div>
                </div>

                <!-- 2. Mission Control Hero -->
                <div class="grid grid-cols-1 lg:grid-cols-[1.5fr_1fr] gap-6">
                    <!-- Welcome & Quick Stats -->
                    <div class="rounded-3xl border border-slate-800 bg-linear-to-br from-slate-900 via-slate-900 to-slate-950 p-8 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 -mt-10 -mr-10 h-64 w-64 rounded-full bg-emerald-500/5 blur-3xl group-hover:bg-emerald-500/10 transition-colors duration-1000"></div>
                        
                        <div class="relative z-10">
                            <h2 class="text-3xl font-bold text-white mb-2">Painel de Controlo</h2>
                            <p class="text-slate-400 mb-8 max-w-md">Bem-vindo de volta. Aqui está o resumo da atividade da plataforma em tempo real.</p>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-950/50 rounded-2xl p-4 border border-slate-800 hover:border-emerald-500/30 transition-colors">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="p-2 bg-emerald-500/10 rounded-lg text-emerald-400">
                                            <Users class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Online</span>
                                    </div>
                                    <p class="text-3xl font-bold text-emerald-400">{{ serverInfo.playersOnline }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Jogadores ligados agora</p>
                                </div>
                                <div class="bg-slate-950/50 rounded-2xl p-4 border border-slate-800 hover:border-blue-500/30 transition-colors">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400">
                                            <Gamepad2 class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ativos</span>
                                    </div>
                                    <p class="text-3xl font-bold text-blue-400">{{ serverInfo.activeGames }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Jogos a decorrer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Navigation Cards -->
                    <div class="grid grid-cols-1 gap-4">
                        <RouterLink :to="{ name: 'admin-dashboard' }" class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 p-5 hover:bg-slate-900 transition-all duration-300 hover:border-amber-500/30 hover:shadow-lg hover:shadow-amber-900/10">
                            <div class="flex items-center justify-between relative z-10">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 rounded-xl bg-amber-500/10 text-amber-400 group-hover:bg-amber-500/20 transition-colors">
                                        <Coins class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-200 group-hover:text-white">Gestão Financeira</h3>
                                        <p class="text-xs text-slate-500 group-hover:text-slate-400">Ver vendas, oferecer moedas</p>
                                    </div>
                                </div>
                                <ChevronRight class="h-5 w-5 text-slate-600 group-hover:text-amber-400 group-hover:translate-x-1 transition-all" />
                            </div>
                        </RouterLink>

                        <RouterLink :to="{ name: 'admin-users' }" class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 p-5 hover:bg-slate-900 transition-all duration-300 hover:border-violet-500/30 hover:shadow-lg hover:shadow-violet-900/10">
                            <div class="flex items-center justify-between relative z-10">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 rounded-xl bg-violet-500/10 text-violet-400 group-hover:bg-violet-500/20 transition-colors">
                                        <Users class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-200 group-hover:text-white">Utilizadores</h3>
                                        <p class="text-xs text-slate-500 group-hover:text-slate-400">Gerir contas, bloqueios</p>
                                    </div>
                                </div>
                                <ChevronRight class="h-5 w-5 text-slate-600 group-hover:text-violet-400 group-hover:translate-x-1 transition-all" />
                            </div>
                        </RouterLink>

                        <RouterLink :to="{ name: 'admin-games' }" class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 p-5 hover:bg-slate-900 transition-all duration-300 hover:border-emerald-500/30 hover:shadow-lg hover:shadow-emerald-900/10">
                            <div class="flex items-center justify-between relative z-10">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-500/20 transition-colors">
                                        <Gamepad2 class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-200 group-hover:text-white">Jogos Globais</h3>
                                        <p class="text-xs text-slate-500 group-hover:text-slate-400">Histórico de partidas</p>
                                    </div>
                                </div>
                                <ChevronRight class="h-5 w-5 text-slate-600 group-hover:text-emerald-400 group-hover:translate-x-1 transition-all" />
                            </div>
                        </RouterLink>
                    </div>
                </div>


            </div>

            <div v-else class="grid gap-10 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] md:items-center">
                <div class="space-y-6 animate-fade-in-up">
                    <div
                        @click="showStatusModal = true"
                        class="inline-flex items-center gap-2.5 rounded-full border border-slate-700 bg-slate-900/90 px-4 py-2 text-xs font-semibold text-slate-300 cursor-pointer hover:border-emerald-500/50 hover:bg-slate-900 transition-all duration-300 group relative overflow-hidden shadow-lg shadow-black/20 hover:shadow-emerald-900/20">
                        <span class="relative flex h-2.5 w-2.5">
                           <span :class="['absolute inline-flex h-full w-full animate-ping rounded-full opacity-75', serverStatusColor]"></span>
                           <span :class="['relative inline-flex h-2.5 w-2.5 rounded-full', serverStatusColor]"></span>
                        </span>
                        <span class="group-hover:text-emerald-400 transition-colors duration-300 tracking-wide uppercase">Estado do Servidor</span>
                    </div>

                    <h1 class="text-3xl font-semibold tracking-tight text-slate-50 md:text-4xl text-balance">
                        Joga Bisca online, <span class="text-emerald-400">como deve ser</span>.
                    </h1>

                    <p class="max-w-xl text-sm text-slate-400 md:text-base leading-relaxed">
                        Plataforma web para partidas de Bisca contra bots ou outros jogadores.
                        Sistema de moedas, partidas rápidas, matches competitivos, estatísticas e
                        leaderboards globais — tudo numa única aplicação.
                    </p>

                    <div v-if="!auth.isAuthenticated" class="flex flex-wrap items-center gap-3 pt-2">
                        <RouterLink to="/play">
                            <Button class="btn btn-primary px-6 py-3 text-sm shadow-lg shadow-brand-500/20 transition-all hover:scale-105 active:scale-95">
                                Jogar agora
                            </Button>
                        </RouterLink>
                        <span class="text-xs text-slate-500 block w-full sm:w-auto mt-2 sm:mt-0">
                            Como convidado podes jogar single-player offline.
                        </span>
                    </div>
                    <div v-else class="flex flex-wrap items-center gap-3 pt-2">
                        <RouterLink v-if="auth.isAdmin" :to="{ name: 'admin-dashboard' }">
                            <Button class="btn btn-primary px-6 py-3 text-sm shadow-lg shadow-brand-500/20 transition-all hover:scale-105 active:scale-95 inline-flex items-center gap-2">
                                <Shield class="h-4 w-4" />
                                Painel de administração
                            </Button>
                        </RouterLink>
                        <RouterLink v-else to="/play">
                            <Button class="btn btn-primary px-6 py-3 text-sm shadow-lg shadow-brand-500/20 transition-all hover:scale-105 active:scale-95">
                                Jogar agora
                            </Button>
                        </RouterLink>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-6 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <Gamepad2 class="h-4 w-4 text-brand-400" />
                            <span>Single-player &amp; multiplayer</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Coins class="h-4 w-4 text-amber-400" />
                            <span>Partidas com apostas em moedas</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Trophy class="h-4 w-4 text-emerald-400" />
                            <span>Histórico detalhado e leaderboards</span>
                        </div>
                    </div>
                </div>

                <div class="relative animate-fade-in-up delay-200">
                    <div
                        class="relative overflow-hidden rounded-2xl border border-slate-800 bg-linear-to-br from-slate-900 to-slate-950 p-4 shadow-[0_18px_45px_rgba(15,23,42,0.75)] hover:border-slate-700/50 transition-colors duration-500">
                        <div class="absolute -top-24 -right-24 w-48 h-48 bg-brand-500/10 rounded-full blur-3xl"></div>
                        
                        <div class="flex items-center justify-between text-xs text-slate-400 relative z-10">
                            <span>Pré-visualização · Bisca de 3</span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Live
                            </span>
                        </div>

                        <div
                            class="mt-5 rounded-2xl bg-linear-to-br from-emerald-900/40 via-emerald-900/10 to-slate-950 p-4 border border-emerald-800/60 relative z-10 backdrop-blur-sm">
                            <div class="flex justify-center gap-3">
                                <CardImage v-for="n in 3" :key="'op-' + n" :carta="CARTA_VIRADA" size="md" />
                            </div>

                            <div class="mt-6 flex items-center justify-between">
                                <div class="flex flex-col gap-1 text-[11px] text-slate-300">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">
                                        Trunfo
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <CardImage :carta="CARTAS['c1']" size="lg" />
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-1 text-[11px] text-slate-300">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">
                                        Pontos
                                    </span>
                                    <span>Eu 34</span>
                                    <span>Adversário 22</span>
                                </div>
                            </div>

                            <div class="flex justify-center gap-6">
                                <CardImage :carta="CARTAS['e12']" size="lg" />
                                <CardImage :carta="CARTAS['o6']" size="lg" />
                            </div>

                            <div class="mt-6 flex justify-center gap-3">
                                <CardImage :carta="CARTAS['c1']" size="md" />
                                <CardImage :carta="CARTAS['p7']" size="md" />
                                <CardImage :carta="CARTAS['o11']" size="md" />
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between text-xs text-slate-400 relative z-10">
                            <span>Tempo restante · 20s</span>
                            <span>Aposta: 3 moedas</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!auth.isAdmin" class="grid gap-5 md:grid-cols-3 animate-fade-in-up delay-300">
                <div class="group rounded-2xl border border-slate-800 bg-slate-950/60 p-5 flex flex-col gap-3 hover:bg-slate-900/80 hover:border-slate-700 transition-all duration-300">
                    <div class="h-10 w-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform duration-300">
                       <Cpu class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-50">
                            Single-player &amp; Bot
                        </h2>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Joga Bisca de 3 ou de 9 contra um bot simples que segue as regras
                            oficiais e tenta ganhar quando joga em segundo.
                        </p>
                    </div>
                </div>

                <div class="group rounded-2xl border border-slate-800 bg-slate-950/60 p-5 flex flex-col gap-3 hover:bg-slate-900/80 hover:border-slate-700 transition-all duration-300">
                     <div class="h-10 w-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform duration-300">
                       <Swords class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-50">
                            Multijogador &amp; Partidas
                        </h2>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Cria jogos standalone ou partidas com apostas em moedas. Sistema de
                            capote, bandeira e resignação com atribuição automática de pontos.
                        </p>
                    </div>
                </div>

                <div class="group rounded-2xl border border-slate-800 bg-slate-950/60 p-5 flex flex-col gap-3 hover:bg-slate-900/80 hover:border-slate-700 transition-all duration-300">
                     <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400 group-hover:scale-110 transition-transform duration-300">
                       <BarChart3 class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-50">
                            Estatísticas &amp; Leaderboards
                        </h2>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Acompanha o teu histórico, ganhos em moedas e desempenho em jogos
                            e partidas. Consulta rankings globais de jogadores.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <CoinPurchaseModal 
            :is-open="showPurchaseModal" 
            @close="showPurchaseModal = false"
            @success="handlePurchaseSuccess"
        />

        <ServerStatusModal 
            :is-open="showStatusModal"
            :players-online="serverInfo.playersOnline"
            :latency="serverInfo.latency"
            :last-checked="serverInfo.lastChecked"
            @close="showStatusModal = false"
        />
    </DefaultLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { RouterLink } from 'vue-router'
import { Button } from '@/components/ui/button'
import CardImage from '@/components/gameplay/CardImage.vue'
import { CARTAS, CARTA_VIRADA } from '@/constants/cards'
import { useAuthStore } from '@/stores/auth'
import CoinPurchaseModal from '@/components/accounting/CoinPurchaseModal.vue'
import ServerStatusModal from '@/components/ServerStatusModal.vue'
import api from '@/services/api'
import { Gamepad2, Coins, Trophy, Cpu, Swords, BarChart3, Server, Database, Layers, Radio, Mail, Shield, ChevronRight, Users, Menu, Activity, RotateCw } from 'lucide-vue-next'
import { useWsStore } from '@/stores/ws'



const auth = useAuthStore();
const wsStore = useWsStore();
const showPurchaseModal = ref(false);
const showStatusModal = ref(false);
const showExtendedStats = ref(false);
const adminStatus = ref(null)
const adminStatusLoading = ref(false)
const adminStatusError = ref('')

const serviceDefinitions = [
    {
        key: 'api',
        label: 'API',
        description: 'Laravel API',
        detail: 'Serve requests e endpoints REST',
        icon: Server,
        accentClass: 'bg-emerald-500/10 text-emerald-300',
        borderClass: 'hover:border-emerald-500/40'
    },
    {
        key: 'database',
        label: 'Base de dados',
        description: 'PostgreSQL',
        detail: 'Armazena dados persistentes',
        icon: Database,
        accentClass: 'bg-sky-500/10 text-sky-300',
        borderClass: 'hover:border-sky-500/40'
    },
    {
        key: 'redis',
        label: 'Redis',
        description: 'Cache & filas',
        detail: 'Sessões, cache e pub/sub',
        icon: Layers,
        accentClass: 'bg-red-500/10 text-red-300',
        borderClass: 'hover:border-red-500/40'
    },
    {
        key: 'websocket',
        label: 'WebSocket',
        description: 'Tempo real',
        detail: 'Salas, jogo e sinais de vídeo',
        icon: Radio,
        accentClass: 'bg-violet-500/10 text-violet-300',
        borderClass: 'hover:border-violet-500/40'
    },
    {
        key: 'queue_worker',
        label: 'Queue Worker',
        description: 'Tarefas assíncronas',
        detail: 'Envio de emails',
        icon: Mail,
        accentClass: 'bg-amber-500/10 text-amber-300',
        borderClass: 'hover:border-amber-500/40'
    }
]
const serverStatusColor = computed(() => 
    serverInfo.value.status === 'online' ? 'bg-emerald-400' : 'bg-rose-400'
)
const uniformBarGradient = 'linear-gradient(90deg, rgba(52,211,153,0.75) 0%, rgba(16,185,129,0.9) 100%)' // stronger emerald
const adminLastUpdated = computed(() => {
    const isoDate = adminStatus.value?.server_time
    if (!isoDate) return null
    const date = new Date(isoDate)
    return date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })
})
const latencyChart = computed(() => {
    if (!adminStatus.value?.services) return []
    const items = serviceDefinitions
        .map((service) => {
            const latency = getServiceLatency(service.key)
            return latency === null || latency === undefined
                ? null
                : { key: service.key, label: service.label, latency }
        })
        .filter(Boolean)
    const maxLatency = Math.max(...items.map((i) => i.latency), 1)
    return items.map((item) => ({
        ...item,
        width: Math.min(100, Math.round((item.latency / maxLatency) * 100)),
        gradient: uniformBarGradient
    }))
})

const getServiceStatus = (key) => {
    if (key === 'websocket') {
        return wsStore.connected ? 'online' : 'offline'
    }
    if (adminStatusLoading.value) return 'checking'
    return adminStatus.value?.services?.[key]?.status || adminStatus.value?.[key] || 'unknown'
}

const statusLabel = (key) => {
    const status = getServiceStatus(key)
    const labels = {
        online: 'Online',
        offline: 'Offline',
        checking: 'A verificar...',
        unknown: 'Indefinido'
    }
    return labels[status] || status
}

const statusPillClass = (key) => {
    const status = getServiceStatus(key)
    if (status === 'online') return 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/40 shadow-[0_0_12px_rgba(16,185,129,0.25)]'
    if (status === 'offline') return 'bg-rose-500/10 text-rose-300 border border-rose-500/30'
    if (status === 'checking') return 'bg-amber-500/10 text-amber-200 border border-amber-500/30 animate-pulse'
    return 'bg-slate-800 text-slate-300 border border-slate-700'
}

const getServiceLatency = (key) => {
    if (adminStatusLoading.value) return null
    const latency = adminStatus.value?.services?.[key]?.latency_ms
    if (latency === null || latency === undefined) return null
    return latency === 0 ? 1 : latency
}

const getServiceColor = (key) => {
    const status = getServiceStatus(key)
    if (status === 'online') return 'text-emerald-400'
    if (status === 'offline') return 'text-rose-400'
    if (status === 'checking') return 'text-amber-400 animate-pulse'
    return 'text-slate-500'
}

const handlePurchaseSuccess = (data) => {
    if (data.coins_balance !== undefined) {
        auth.user.coins_balance = data.coins_balance
    }
}

const fetchAdminStatus = async () => {
    if (!auth.isAdmin) return
    adminStatusLoading.value = true
    adminStatusError.value = ''
    // Also refresh public stats when manually refreshing
    checkServerStatus()
    try {
        const { data } = await api.get('/admin/system/status')
        adminStatus.value = data
    } catch (e) {
        adminStatusError.value = 'Não foi possível obter o estado dos serviços.'
    } finally {
        adminStatusLoading.value = false
    }
}

const publicStats = ref(null)
const lastCheckedTime = ref('—')
const lastLatency = ref('—')

const serverInfo = computed(() => {
    // 1. Status: Preference to WS connection, fallback to API status
    // If we have no API data yet, but WS is connected, we are Online.
    // If neither, we are 'offline' (or 'checking' if loading?).
    const apiStatus = publicStats.value?.server?.status || 'offline'
    const status = wsStore.connected ? 'online' : apiStatus

    return {
        status,
        playersOnline: publicStats.value?.players_online || 0,
        activeGames: publicStats.value?.active_games || 0,
        latency: lastLatency.value,
        lastChecked: lastCheckedTime.value
    }
})

async function checkServerStatus() {
    try {
        const start = performance.now()
        const { data } = await api.get('/public/stats')
        const latency = Math.round(performance.now() - start)
        
        publicStats.value = data
        lastLatency.value = latency
        lastCheckedTime.value = new Date().toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })
    } catch (e) {
        // If API fails, we don't update publicStats, so it keeps old data or null
        // But we want to ensure we don't show "stale" online status from API if it failed?
        // Actually, if API fails, we probably shouldn't assume API status is 'offline' if we are ignoring it anyway.
        // We'll leave publicStats as is or reset if needed. 
        // For now, let's reset to null to indicate "lost connection" to API
        publicStats.value = null
    }
}

let statusInterval = null

onMounted(() => {
    checkServerStatus()
    if (auth.isAdmin) {
        fetchAdminStatus()
    }
    
    // Poll every 5 seconds for everyone
    statusInterval = setInterval(() => {
        checkServerStatus()
        if (auth.isAdmin) {
            fetchAdminStatus()
        }
    }, 5000)
})


onUnmounted(() => {
    if (statusInterval) clearInterval(statusInterval)
})

watch(() => auth.isAdmin, (isAdmin) => {
    if (isAdmin) {
        fetchAdminStatus()
    }
})
</script>
