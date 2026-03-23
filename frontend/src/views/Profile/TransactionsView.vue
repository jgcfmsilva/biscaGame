<template>
    <DefaultLayout>
        <section class="mx-auto max-w-5xl px-4 py-8 space-y-8">

            <CoinPurchaseModal 
                :is-open="showPurchaseModal" 
                @close="showPurchaseModal = false"
                @success="handlePurchaseSuccess"
            />

            <section class="grid gap-4 md:grid-cols-3">
                <article
                    class="relative overflow-hidden rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-emerald-500/10 blur-xl"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-emerald-400/80">Saldo atual</p>
                            <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ formatCoins(coinsBalance) }}</p>
                            <p class="text-xs text-slate-500">Moedas disponíveis agora</p>
                            <Button
                                class="mt-3 rounded-xl border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-xs font-medium text-emerald-50 hover:bg-emerald-500/20"
                                @click="showPurchaseModal = true">
                                <PlusCircle class="h-4 w-4 mr-2" />
                                Comprar Moedas
                            </Button>
                        </div>
                        <div
                            class="h-10 w-10 rounded-2xl border border-emerald-600/40 bg-emerald-500/10 text-emerald-300 flex items-center justify-center">
                            <Wallet class="h-5 w-5" />
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Entradas</p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-200">
                                {{ formatCoins(totalCredits) }}
                            </p>
                            <p class="text-xs text-slate-500">Últimos 90 dias</p>
                        </div>
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-300">
                            <ArrowDownRight class="h-4 w-4" />
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Saídas</p>
                            <p class="mt-2 text-2xl font-semibold text-rose-200">
                                {{ formatCoins(totalDebits) }}
                            </p>
                            <p class="text-xs text-slate-500">Últimos 90 dias</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-300">
                            <ArrowUpRight class="h-4 w-4" />
                        </span>
                    </div>
                </article>
            </section>

            <section
                class="rounded-3xl border border-emerald-700/30 bg-slate-950/80 p-6 space-y-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div class="space-y-1">
                        <p class="text-xs uppercase tracking-[0.4em] text-emerald-400/80">Histórico</p>
                        <h2 class="text-2xl font-semibold text-slate-100">Últimos movimentos</h2>
                        <p class="text-sm text-slate-500">
                            Aplica filtros e consulta o detalhe de cada transação.
                        </p>
                    </div>
                    <div class="grid w-full gap-3 sm:grid-cols-2 md:grid-cols-3">
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">De</label>
                            <Input type="date" v-model="dateFrom" class="bg-slate-950/60 border-slate-800 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Até</label>
                            <Input type="date" v-model="dateTo" class="bg-slate-950/60 border-slate-800 text-sm" />
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <label class="block text-xs text-slate-500 mb-1">Tipo</label>
                                <select v-model="typeFilter"
                                    class="w-full rounded-lg bg-slate-950/60 border border-slate-800 text-sm text-slate-200 px-3 py-2">
                                    <option value="">Todos</option>
                                    <option value="credit">Créditos</option>
                                    <option value="debit">Débitos</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-slate-500 mb-1">Ordenar</label>
                                <select v-model="sortBy"
                                    class="w-full rounded-lg bg-slate-950/60 border border-slate-800 text-sm text-slate-200 px-3 py-2">
                                    <option value="date_desc">Data (mais recente)</option>
                                    <option value="date_asc">Data (mais antiga)</option>
                                    <option value="value_desc">Valor (maior)</option>
                                    <option value="value_asc">Valor (menor)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800/80 bg-slate-950/60">
                    <div v-if="isLoading" class="flex items-center justify-center gap-2 px-4 py-8 text-sm text-slate-400">
                        <Spinner class="text-emerald-300" />
                        A carregar movimentos...
                    </div>

                    <div v-else-if="errorMessage" class="space-y-3 px-4 py-8 text-center">
                        <p class="text-sm text-rose-300">{{ errorMessage }}</p>
                        <Button class="btn btn-primary mx-auto" size="sm" @click="loadTransactions">
                            Tentar novamente
                        </Button>
                    </div>

                    <div v-else-if="paginatedTransactions.length === 0" class="space-y-2 px-4 py-10 text-center">
                        <p class="text-base font-medium text-slate-100">Sem movimentos encontrados</p>
                        <p class="text-sm text-slate-500">
                            Ajusta os filtros ou cria novas transações.
                        </p>
                    </div>

                    <div v-else class="space-y-5">
                        <div class="hidden md:block">
                            <Table class="min-w-full text-sm">
                                <TableHeader>
                                    <TableRow class="border-slate-800 bg-slate-950/60 uppercase text-xs tracking-wide text-slate-400 hover:bg-transparent">
                                        <TableHead class="text-slate-400 text-left px-4">Data</TableHead>
                                        <TableHead class="text-slate-400 text-left px-4">Movimento</TableHead>
                                        <TableHead class="text-slate-400 text-left px-4">Referência</TableHead>
                                        <TableHead class="text-left text-slate-400 px-4">Valor</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="transaction in paginatedTransactions" :key="`table-${transaction.id}`"
                                        class="border-slate-900/60 hover:bg-slate-900/50 [&>td]:py-3">
                                        <TableCell class="align-top px-4 text-left">
                                            <p class="text-sm font-medium text-slate-100">
                                                {{ formatDate(transaction.transaction_datetime) }}
                                            </p>
                                        </TableCell>
                                        <TableCell class="align-top space-y-1 px-4 text-left">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-slate-100">
                                                    {{ resolveLabel(transaction) }}
                                                </p>
                                                <span :class="[
                                                    'rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                                                    isCredit(transaction)
                                                        ? 'border-emerald-700/50 bg-emerald-500/10 text-emerald-200'
                                                        : 'border-rose-700/50 bg-rose-500/10 text-rose-200'
                                                ]">
                                                    {{ transactionTypeLabel(transaction) }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500">{{ resolveDetails(transaction) }}</p>
                                        </TableCell>
                                        <TableCell class="align-top px-4 text-left">
                                            <p class="text-xs font-medium text-slate-300">
                                                {{ resolveReference(transaction) }}
                                            </p>
                                        </TableCell>
                                        <TableCell class="align-top px-4 text-left">
                                            <p :class="[
                                                'text-base font-semibold',
                                                isCredit(transaction) ? 'text-emerald-300' : 'text-rose-300'
                                            ]">
                                                {{ formatCoins(transaction.coins, true) }}
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div class="grid gap-3 md:hidden">
                            <article v-for="transaction in paginatedTransactions" :key="`card-${transaction.id}`"
                                class="rounded-2xl border border-slate-800/80 bg-slate-950/70 px-4 py-4 shadow-[0_12px_30px_rgba(2,6,23,0.6)]">
                                <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wide text-slate-500">
                                    <span>{{ formatDate(transaction.transaction_datetime) }}</span>
                                    <span :class="[
                                        'rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                                        isCredit(transaction)
                                            ? 'border-emerald-700/50 bg-emerald-500/10 text-emerald-200'
                                            : 'border-rose-700/50 bg-rose-500/10 text-rose-200'
                                    ]">
                                        {{ transactionTypeLabel(transaction) }}
                                    </span>
                                </div>
                                <div class="mt-2 space-y-1">
                                    <p class="text-sm font-semibold text-slate-100">{{ resolveLabel(transaction) }}</p>
                                    <p class="text-xs text-slate-500">{{ resolveDetails(transaction) }}</p>
                                </div>
                                <p class="mt-2 text-[11px] text-slate-500">
                                    {{ resolveReference(transaction) }}
                                </p>
                                <div class="mt-2 flex items-center justify-end text-xs text-slate-400">
                                    <p :class="[
                                        'text-base font-semibold',
                                        isCredit(transaction) ? 'text-emerald-300' : 'text-rose-300'
                                    ]">
                                        {{ formatCoins(transaction.coins, true) }}
                                    </p>
                                </div>
                            </article>
                        </div>

                        <div class="flex items-center justify-between px-2 pb-4">
                            <div class="text-xs text-slate-400">
                                {{ filteredTransactions.length }} movimentos filtrados
                            </div>
                            <div class="flex items-center gap-3">
                                <select v-model.number="perPage"
                                    class="rounded-lg bg-slate-900/80 border border-slate-700 text-sm text-slate-200 px-3 py-1.5">
                                    <option :value="10">10 / pág</option>
                                    <option :value="20">20 / pág</option>
                                    <option :value="50">50 / pág</option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <Button variant="outline" size="sm"
                                        class="border-slate-700 bg-slate-900/80 text-slate-200 hover:bg-slate-800"
                                        :disabled="currentPage <= 1" @click="currentPage = currentPage - 1">
                                        Anterior
                                    </Button>
                                    <span class="text-slate-300 text-sm">Página {{ currentPage }} de {{ totalPages }}</span>
                                    <Button variant="outline" size="sm"
                                        class="border-slate-700 bg-slate-900/80 text-slate-200 hover:bg-slate-800"
                                        :disabled="currentPage >= totalPages" @click="currentPage = currentPage + 1">
                                        Seguinte
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-emerald-700/30 bg-slate-950/80 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-400/80">Comparação mensal</p>
                        <h3 class="text-lg font-semibold text-slate-100">Créditos vs Débitos</h3>
                    </div>
                </div>
                <div class="w-full overflow-x-auto">
                    <div class="min-w-[320px] bg-slate-950/80 border border-emerald-700/30 rounded-2xl px-4 py-4 space-y-3">
                        <div v-if="chartDataset.labels.length === 0" class="text-sm text-slate-500 text-center py-6">
                            Sem dados suficientes para o gráfico.
                        </div>
                        <div v-else class="space-y-4">
                            <div class="flex items-center gap-4 text-xs text-slate-400">
                                <div class="flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Créditos
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-rose-400"></span> Débitos
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-end gap-4 h-64">
                                    <div v-for="(label, idx) in chartDataset.labels" :key="`chart-${label}`"
                                        class="flex-1 flex flex-col items-center gap-2">
                                        <div class="flex items-end gap-2 w-full h-48">
                                            <div class="flex-1 rounded-t-md bg-emerald-400/80 transition-all duration-300"
                                                :style="{ height: creditHeight(idx) + '%' }"></div>
                                            <div class="flex-1 rounded-t-md bg-rose-400/80 transition-all duration-300"
                                                :style="{ height: debitHeight(idx) + '%' }"></div>
                                        </div>
                                        <p class="text-xs font-medium text-slate-200">{{ label }}</p>
                                        <p class="text-[11px] text-slate-500">
                                            {{ chartDataset.datasets[0].data[idx] }} / {{ chartDataset.datasets[1].data[idx] }} moedas
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Spinner } from '@/components/ui/spinner'
import { ArrowDownRight, ArrowUpRight, RefreshCw, Wallet, PlusCircle } from 'lucide-vue-next'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import CoinPurchaseModal from '@/components/accounting/CoinPurchaseModal.vue'

const auth = useAuthStore()

const transactions = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const typeFilter = ref('')
const sortBy = ref('date_desc')
const dateFrom = ref('')
const dateTo = ref('')
const lastUpdated = ref(null)
const showPurchaseModal = ref(false)
const perPage = ref(10)
const currentPage = ref(1)

const coinsBalance = computed(() => auth.user?.coins_balance ?? 0)

const totalCredits = computed(() => transactions.value
    .map(tx => coinValue(tx?.coins))
    .filter(amount => amount > 0)
    .reduce((sum, amount) => sum + amount, 0))

const totalDebits = computed(() => Math.abs(transactions.value
    .map(tx => coinValue(tx?.coins))
    .filter(amount => amount < 0)
    .reduce((sum, amount) => sum + amount, 0)))

const lastUpdatedLabel = computed(() => {
    if (!lastUpdated.value) return ''
    return new Intl.DateTimeFormat('pt-PT', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(lastUpdated.value)
})

const filteredTransactions = computed(() => {
    let list = [...transactions.value]
    const from = dateFrom.value ? new Date(dateFrom.value) : null
    const to = dateTo.value ? new Date(dateTo.value) : null
    if (from) {
        list = list.filter(tx => new Date(tx.transaction_datetime) >= from)
    }
    if (to) {
        const end = new Date(to)
        end.setHours(23, 59, 59, 999)
        list = list.filter(tx => new Date(tx.transaction_datetime) <= end)
    }
    if (typeFilter.value === 'credit') {
        list = list.filter(tx => coinValue(tx?.coins) > 0)
    } else if (typeFilter.value === 'debit') {
        list = list.filter(tx => coinValue(tx?.coins) < 0)
    }
    list.sort((a, b) => {
        if (sortBy.value === 'date_desc') {
            return new Date(b.transaction_datetime) - new Date(a.transaction_datetime)
        }
        if (sortBy.value === 'date_asc') {
            return new Date(a.transaction_datetime) - new Date(b.transaction_datetime)
        }
        if (sortBy.value === 'value_desc') {
            return coinValue(b.coins) - coinValue(a.coins)
        }
        if (sortBy.value === 'value_asc') {
            return coinValue(a.coins) - coinValue(b.coins)
        }
        return 0
    })
    return list
})

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredTransactions.value.length / perPage.value))
})

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredTransactions.value.slice(start, start + perPage.value)
})

const chartDataset = computed(() => {
    const byMonth = {}
    filteredTransactions.value.forEach((tx) => {
        const date = new Date(tx.transaction_datetime)
        if (Number.isNaN(date.getTime())) return
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
        if (!byMonth[key]) {
            byMonth[key] = { credit: 0, debit: 0 }
        }
        const value = coinValue(tx.coins)
        if (value > 0) byMonth[key].credit += value
        if (value < 0) byMonth[key].debit += Math.abs(value)
    })

    const labels = Object.keys(byMonth).sort()
    const creditData = labels.map((k) => byMonth[k].credit)
    const debitData = labels.map((k) => byMonth[k].debit)

    return {
        labels,
        datasets: [
            { label: 'Créditos', data: creditData, backgroundColor: 'rgba(16,185,129,0.75)' },
            { label: 'Débitos', data: debitData, backgroundColor: 'rgba(239,68,68,0.75)' },
        ]
    }
})

const maxChartValue = computed(() => {
    const allValues = [
        ...(chartDataset.value.datasets[0]?.data || []),
        ...(chartDataset.value.datasets[1]?.data || [])
    ]
    const max = Math.max(...allValues, 0)
    return max > 0 ? max : 1
})

const creditHeight = (idx) => {
    const value = chartDataset.value.datasets[0]?.data[idx] || 0
    return Math.min(100, Math.round((value / maxChartValue.value) * 100))
}

const debitHeight = (idx) => {
    const value = chartDataset.value.datasets[1]?.data[idx] || 0
    return Math.min(100, Math.round((value / maxChartValue.value) * 100))
}

const dateFormatter = new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short'
})

function formatCoins(value, withSign = false) {
    const numeric = typeof value === 'number' ? value : Number(value) || 0
    const abs = Math.abs(numeric).toLocaleString('pt-PT')
    if (!withSign) {
        return `${abs} moedas`
    }
    const sign = numeric > 0 ? '+' : '-'
    return `${sign}${abs} moedas`
}

function isCredit(tx) {
    return coinValue(tx?.coins) > 0
}

function formatDate(date) {
    if (!date) return '—'
    return dateFormatter.format(new Date(date))
}

function resolveLabel(tx) {
    return tx?.type?.name ?? 'Transação'
}

function resolveDetails(tx) {
    if (!tx) return 'Movimento registado'
    const custom = tx.custom
    if (typeof custom === 'string' && custom.length > 0) {
        if (/stake debit/i.test(custom)) {
            return 'Debitado para criar match'
        }
        return custom
    }
    if (custom && typeof custom === 'object') {
        if (custom.description) return custom.description
        if (custom.payment_type) return `Pagamento via ${custom.payment_type}`
        if (custom.message) return custom.message
    }
    if (tx.match_id) return `Match #${tx.match_id}`
    if (tx.game_id) return `Jogo #${tx.game_id}`
    return 'Carteira'
}

function resolveReference(tx) {
    if (!tx) return '—'
    if (tx.match_id) return `Match #${tx.match_id}`
    if (tx.game_id) return `Jogo #${tx.game_id}`
    return `Transação #${tx.id}`
}

function transactionTypeLabel(tx) {
    return isCredit(tx) ? 'Crédito' : 'Débito'
}

function coinValue(value) {
    if (typeof value === 'number') {
        return value
    }
    if (typeof value === 'string') {
        const parsed = Number(value)
        return Number.isFinite(parsed) ? parsed : 0
    }
    if (value && typeof value === 'object' && 'coins' in value) {
        return coinValue(value.coins)
    }
    return 0
}

async function loadTransactions() {
    isLoading.value = true
    errorMessage.value = ''
    currentPage.value = 1
    try {
        const { data } = await api.get('/coins/transactions')
        transactions.value = Array.isArray(data) ? data : []
        lastUpdated.value = new Date()
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar as transações.'
    } finally {
        isLoading.value = false
    }
}

function handlePurchaseSuccess(data) {
    // Atualizar saldo no store se vier na resposta
    if (data.coins_balance !== undefined) {
        auth.user.coins_balance = data.coins_balance
    }
    // Recarregar transações para mostrar a nova compra
    loadTransactions()
}

onMounted(() => {
    loadTransactions()
})
</script>
