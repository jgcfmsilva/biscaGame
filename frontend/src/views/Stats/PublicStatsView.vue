<template>
    <DefaultLayout>
        <section class="mx-auto max-w-5xl px-4 py-10 space-y-6">
            <header class="space-y-1 text-center">
                <h1 class="text-3xl font-semibold text-slate-50">Estatísticas</h1>
            </header>

            <div v-if="errorMessage" class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100 text-center">
                {{ errorMessage }}
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                             <Users class="h-5 w-5" />
                        </div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Total de Jogadores</p>
                    </div>
                    <p class="text-3xl font-bold text-slate-50">{{ totalUsers }}</p>
                    <p class="mt-1 text-xs text-slate-500">Registados na plataforma desde o início</p>
                </div>

                <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                             <Activity class="h-5 w-5" />
                        </div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Total de Jogos</p>
                    </div>
                    <p class="text-3xl font-bold text-slate-50">{{ totalGamesAllTime }}</p>
                    <p class="mt-1 text-xs text-slate-500">Partidas realizadas (online + offline)</p>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Gráfico</p>
                        <p class="text-lg font-semibold text-slate-100">Jogos jogados por mês</p>
                    </div>
                    <p class="text-xs text-slate-500">Últimos 12 meses</p>
                </div>
                <div class="min-h-[280px]">
                    <Bar v-if="!loading" :data="chartData" :options="chartOptions" />
                    <div v-else class="flex h-40 items-center justify-center text-sm text-slate-400">A carregar...</div>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Jogos jogados</p>
                        <p class="text-lg font-semibold text-slate-100">Últimos 7 dias</p>
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-800">
                    <table class="w-full text-sm text-slate-200">
                        <thead class="bg-slate-900/60 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Dia</th>
                                <th class="px-3 py-2 text-right">Jogos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in gamesLast7DaysTable" :key="item.day"
                                class="border-t border-slate-800/70 odd:bg-slate-950/50 even:bg-slate-900/40">
                                <td class="px-3 py-2">{{ item.day }}</td>
                                <td class="px-3 py-2 text-right">{{ item.total }}</td>
                            </tr>
                            <tr v-if="!gamesLast7DaysTable.length">
                                <td colspan="2" class="px-3 py-3 text-center text-slate-500 text-xs">Sem dados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </DefaultLayout>
</template>

<script setup>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js'
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { Users, Activity } from 'lucide-vue-next'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const auth = useAuthStore()
const router = useRouter()

const loading = ref(true)
const gamesPerMonth = ref([])
const errorMessage = ref('')
const gamesLast7Days = ref([])
const gamesLast7DaysTable = computed(() =>
    [...gamesLast7Days.value].sort((a, b) => b.day.localeCompare(a.day))
)

const totalGames = computed(() =>
    gamesPerMonth.value.reduce((sum, item) => sum + (item.total || 0), 0)
)

const chartData = computed(() => ({
    labels: gamesPerMonth.value.map(item => item.month),
    datasets: [
        {
            label: 'Jogos',
            data: gamesPerMonth.value.map(item => item.total),
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }
    ]
}))

const chartOptions = {
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false },
    },
    scales: {
        x: { ticks: { color: '#cbd5e1' }, grid: { display: false } },
        y: { ticks: { color: '#cbd5e1', stepSize: 1 }, grid: { color: 'rgba(148,163,184,0.1)' } },
    }
}

// latência só usada internamente para pings; não mostramos agora
const apiLatencyLabel = computed(() => apiLatency.value != null ? `${apiLatency.value} ms` : '—')
const wsLatencyLabel = computed(() => wsLatency.value != null ? `${wsLatency.value} ms` : '—')

const totalUsers = ref(0)
const totalGamesAllTime = ref(0)

async function fetchStats() {
    loading.value = true
    errorMessage.value = ''
    try {
        const { data } = await api.get('/public/stats')
        totalUsers.value = data.total_users || 0
        totalGamesAllTime.value = data.total_games || 0
        gamesPerMonth.value = Array.isArray(data?.games_per_month) ? data.games_per_month : []
        gamesLast7Days.value = Array.isArray(data?.games_last_7_days) ? data.games_last_7_days : []
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || 'Não foi possível carregar as estatísticas.'
        if (errorMessage.value) {
            toast.error(errorMessage.value)
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchStats()
})


</script>
