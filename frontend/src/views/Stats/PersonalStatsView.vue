<template>
    <DefaultLayout>
        <section class="mx-auto max-w-5xl px-4 py-10 space-y-6">
            <header class="space-y-1">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-50">Estatísticas pessoais</h1>
            </header>

            <div v-if="errorMessage"
                class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                {{ errorMessage }}
            </div>

            <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Highlights</p>
                        <h3 class="text-lg font-semibold text-slate-100">Top partidas e conquistas</h3>
                        <p class="text-xs text-slate-500">Top 3 partidas em destaque ou conquistas recentes.</p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <div v-for="item in highlightItems" :key="item.title"
                        class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-1">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>{{ item.title }}</span>
                            <span v-if="item.badge" class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold text-emerald-200">
                                {{ item.badge }}
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-50">{{ item.primary }}</p>
                        <p class="text-xs text-slate-500">{{ item.meta }}</p>
                    </div>
                </div>
                <div v-if="!highlightItems.length" class="text-xs text-slate-500">
                    Sem destaques disponíveis.
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Personal leaderboards</p>
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-800">
                    <table class="w-full text-sm text-slate-200">
                        <thead class="bg-slate-900/60 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Métrica</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in leaderboardRows" :key="row.label"
                                class="border-t border-slate-800/70 odd:bg-slate-950/50 even:bg-slate-900/40">
                                <td class="px-3 py-2">{{ row.label }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ row.value }}</td>
                            </tr>
                            <tr v-if="!leaderboardRows.length">
                                <td colspan="2" class="px-3 py-3 text-center text-slate-500 text-xs">Sem dados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Horas de jogo</p>
                    <p class="mt-2 text-3xl font-bold text-slate-50">{{ totalHoursLabel }}</p>
                    <p class="mt-1 text-xs text-slate-500">Tempo total acumulado em partidas concluídas</p>
                </div>
                <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Jogos (últimos 6 meses)</p>
                    <p class="mt-2 text-3xl font-bold text-slate-50">{{ gamesLastMonthsLabel }}</p>
                    <p class="mt-1 text-xs text-slate-500">Total de jogos concluídos no período</p>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Jogos por mês</p>
                        <p class="text-lg font-semibold text-slate-100">Distribuição recente</p>
                    </div>
                    <p class="text-xs text-slate-500">Últimos 6 meses</p>
                </div>
                <div class="min-h-[260px]">
                    <Bar v-if="!loading" :data="chartData" :options="chartOptions" />
                    <div v-else class="flex h-40 items-center justify-center text-sm text-slate-400">A carregar...</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5 space-y-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Melhor mês</p>
                    <p class="text-2xl font-bold text-slate-50">{{ bestMonthCard.title }}</p>
                    <p class="text-xs text-slate-500">{{ bestMonthCard.meta }}</p>
                    <p :class="['text-sm font-semibold', bestMonthCard.changeClass]">
                        {{ bestMonthCard.change }}
                    </p>
                </div>
                <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5 space-y-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Pior mês</p>
                    <p class="text-2xl font-bold text-slate-50">{{ worstMonthCard.title }}</p>
                    <p class="text-xs text-slate-500">{{ worstMonthCard.meta }}</p>
                    <p :class="['text-sm font-semibold', worstMonthCard.changeClass]">
                        {{ worstMonthCard.change }}
                    </p>
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
import { toast } from 'vue-sonner'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const loading = ref(true)
const errorMessage = ref('')
const gamesByMonth = ref([])
const leaderboard = ref(null)
const timePlayed = ref({ total_hours: 0 })
const topMatches = ref([])

const totalHoursLabel = computed(() => {
    const value = Number(timePlayed.value?.total_hours ?? 0)
    if (!value) return '0h'
    return `${value.toFixed(2)}h`
})

const leaderboardRows = computed(() => {
    if (!leaderboard.value) return []
    return [
        { label: 'Jogos ganhos', value: leaderboard.value.games_won ?? 0 },
        { label: 'Partidas ganhas', value: leaderboard.value.matches_won ?? 0 },
        { label: 'Capotes', value: leaderboard.value.capotes_count ?? 0 },
        { label: 'Bandeiras', value: leaderboard.value.bandeiras_count ?? 0 },
    ]
})

const gamesLastMonthsLabel = computed(() => {
    const total = gamesByMonth.value.reduce((sum, item) => sum + (item.total || 0), 0)
    return total.toLocaleString('pt-PT')
})

const chartData = computed(() => ({
    labels: gamesByMonth.value.map(item => item.month),
    datasets: [
        {
            label: 'Jogos',
            data: gamesByMonth.value.map(item => item.total),
            backgroundColor: 'rgba(56, 189, 248, 0.6)',
            borderColor: 'rgba(56, 189, 248, 1)',
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

const sortedMonths = computed(() => [...gamesByMonth.value])

const bestMonthCard = computed(() => buildMonthCard('best'))
const worstMonthCard = computed(() => buildMonthCard('worst'))

const highlightItems = computed(() => {
    if (Array.isArray(topMatches.value) && topMatches.value.length) {
        return topMatches.value.slice(0, 3).map((match, idx) => buildMatchHighlight(match, idx))
    }
    const lb = leaderboard.value || {}
    const items = [
        {
            title: 'Capotes',
            primary: formatHighlightNumber(lb.capotes_count),
            meta: 'Capotes conquistados',
            badge: 'Conquista'
        },
        {
            title: 'Bandeiras',
            primary: formatHighlightNumber(lb.bandeiras_count),
            meta: 'Bandeiras acumuladas',
            badge: 'Conquista'
        },
        {
            title: 'Partidas ganhas',
            primary: formatHighlightNumber(lb.matches_won),
            meta: 'Vitórias em matches',
            badge: 'Vitória'
        }
    ]
    return items.filter(item => item.primary !== '—')
})

async function fetchPersonalStats() {
    loading.value = true
    errorMessage.value = ''
    try {
        const { data } = await api.get('/player/stats/personal')
        gamesByMonth.value = Array.isArray(data?.games_by_month) ? data.games_by_month : []
        leaderboard.value = data?.leaderboard ?? null
        timePlayed.value = data?.time_played ?? { total_hours: 0 }
        topMatches.value = Array.isArray(data?.top_matches) ? data.top_matches : []
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
    fetchPersonalStats()
})

function formatHighlightNumber(value) {
    const numeric = Number(value)
    if (!Number.isFinite(numeric) || numeric <= 0) return '—'
    return numeric.toLocaleString('pt-PT')
}

function buildMatchHighlight(match, idx) {
    const title = match?.title || match?.label || `Partida #${match?.id ?? idx + 1}`
    const score = match?.score ?? match?.points ?? match?.coins
    const duration = match?.duration_minutes ?? match?.duration
    const metaParts = []
    if (score !== undefined) {
        metaParts.push(`${score} pts`)
    }
    if (duration !== undefined) {
        metaParts.push(`${duration} min`)
    }
    const meta = metaParts.join(' • ') || 'Partida em destaque'
    return {
        title,
        primary: formatHighlightNumber(score) !== '—' ? formatHighlightNumber(score) : '—',
        meta,
        badge: match?.type || 'Top 3'
    }
}

function buildMonthCard(kind) {
    if (!sortedMonths.value.length) {
        return {
            title: '—',
            meta: 'Sem dados',
            change: '—',
            changeClass: 'text-slate-500'
        }
    }
    const comparator = kind === 'best'
        ? (a, b) => (b.total ?? 0) - (a.total ?? 0)
        : (a, b) => (a.total ?? 0) - (b.total ?? 0)
    const sorted = [...sortedMonths.value].sort(comparator)
    const current = sorted[0]
    const indexInOriginal = sortedMonths.value.findIndex(m => m.month === current.month)
    const prev = indexInOriginal > 0 ? sortedMonths.value[indexInOriginal - 1] : null
    const changeInfo = computeChange(current?.total, prev?.total)
    return {
        title: `${current.month ?? '—'} · ${formatHighlightNumber(current?.total)}`,
        meta: prev ? `Vs ${prev.month}: ${formatHighlightNumber(prev.total)} jogos` : 'Sem mês anterior para comparar',
        change: changeInfo.label,
        changeClass: changeInfo.className
    }
}

function computeChange(current, previous) {
    const curr = Number(current ?? 0)
    const prev = Number(previous ?? 0)
    if (!Number.isFinite(curr)) {
        return { label: '—', className: 'text-slate-500' }
    }
    if (!Number.isFinite(prev) || prev === 0) {
        return { label: prev === 0 && curr > 0 ? '+∞% vs mês anterior' : '—', className: 'text-slate-500' }
    }
    const delta = ((curr - prev) / Math.abs(prev)) * 100
    const sign = delta > 0 ? '+' : ''
    const label = `${sign}${delta.toFixed(1)}% vs mês anterior`
    const className = delta > 0 ? 'text-emerald-300' : delta < 0 ? 'text-rose-300' : 'text-slate-400'
    return { label, className }
}
</script>
