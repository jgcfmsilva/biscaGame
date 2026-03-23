<template>
  <div class="space-y-6 mt-6">
    <div class="flex flex-wrap gap-4 justify-between items-center">
      <h2 class="text-3xl font-bold text-white">Transações Globais</h2>
    </div>
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
      <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Filtros</div>
      <div class="grid gap-3 md:grid-cols-12">
        <div class="relative md:col-span-4">
          <input
            v-model="search"
            @input="debouncedFilter"
            type="text"
            placeholder="Pesquisar user, email, ref..."
            class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-slate-200 placeholder-slate-500"
          >
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
        <select
          v-model="typeFilter"
          @change="applyFilters"
          class="md:col-span-2 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          title="Tipo de transação"
        >
          <option value="">Todos os tipos</option>
          <option v-for="type in transactionTypes" :key="type.id" :value="type.id">
            {{ type.name }}
          </option>
        </select>
        <select
          v-model="directionFilter"
          @change="applyFilters"
          class="md:col-span-2 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          title="Créditos ou débitos"
        >
          <option value="">Crédito/Débito</option>
          <option value="credit">Crédito</option>
          <option value="debit">Débito</option>
        </select>
        <select
          v-model="paymentTypeFilter"
          @change="applyFilters"
          class="md:col-span-2 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          title="Método de pagamento"
        >
          <option value="">Pagamento</option>
          <option v-for="type in paymentTypes" :key="type" :value="type">
            {{ type }}
          </option>
        </select>
        <div class="md:col-span-3 flex items-center gap-2 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2">
          <input
            v-model="dateFrom"
            @change="applyFilters"
            type="date"
            class="bg-transparent text-slate-200 focus:outline-none"
            title="Data inicial"
          >
          <span class="text-xs text-slate-500">até</span>
          <input
            v-model="dateTo"
            @change="applyFilters"
            type="date"
            class="bg-transparent text-slate-200 focus:outline-none"
            title="Data final"
          >
        </div>
        <div class="md:col-span-3 flex items-center bg-slate-950 border border-slate-700 rounded-lg px-2 py-2">
          <span class="text-xs text-slate-500 px-2">Coins</span>
          <input
            v-model="coinsMin"
            @change="applyFilters"
            type="number"
            min="0"
            placeholder="min"
            class="no-spin w-20 bg-transparent text-slate-200 placeholder:text-slate-500 focus:outline-none"
          >
          <span class="text-xs text-slate-600 px-1">—</span>
          <input
            v-model="coinsMax"
            @change="applyFilters"
            type="number"
            min="0"
            placeholder="max"
            class="no-spin w-20 bg-transparent text-slate-200 placeholder:text-slate-500 focus:outline-none"
          >
        </div>
        <div class="md:col-span-2 flex gap-2">
          <select
            v-model="sortBy"
            @change="applyFilters"
            class="flex-1 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          >
            <option value="transaction_datetime">Data</option>
            <option value="coins">Montante</option>
            <option value="id">ID</option>
          </select>
          <select
            v-model="sortOrder"
            @change="applyFilters"
            class="w-24 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          >
            <option value="desc">Desc</option>
            <option value="asc">Asc</option>
          </select>
        </div>
        <div class="md:col-span-2 flex gap-2">
          <select
            v-model.number="perPage"
            @change="applyFilters"
            class="flex-1 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          >
            <option :value="10">10 / pág</option>
            <option :value="20">20 / pág</option>
            <option :value="50">50 / pág</option>
          </select>
          <button
            @click="clearFilters"
            class="px-3 py-2 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 hover:bg-slate-800 hover:text-white transition-colors"
          >
            Limpar
          </button>
        </div>
      </div>
    </div>

    <!-- A carregar/Erro -->
    <div v-if="loading" class="text-center py-4 text-slate-400">A carregar transações...</div>
    <div v-if="error" class="text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded">{{ error }}</div>

    <!-- Tabela de Transações -->
    <div class="bg-slate-900 shadow-md rounded-lg overflow-hidden border border-slate-800 overflow-x-auto">
      <table class="min-w-[720px] w-full divide-y divide-slate-800">
        <thead class="bg-slate-950">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Data</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Utilizador</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Tipo</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Montante</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Referência</th>
          </tr>
        </thead>
        <tbody class="bg-slate-900 divide-y divide-slate-800">
          <tr v-for="tx in transactions" :key="tx.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ formatDate(tx.transaction_datetime) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="tx.user" class="text-sm font-medium text-slate-200">{{ tx.user.nickname }}</div>
                <div v-else class="text-sm text-slate-500 italic">Utilizador Apagado</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                {{ tx.type?.name || 'Desconhecido' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold">
                <span :class="tx.coins > 0 ? 'text-emerald-400' : 'text-red-400'">
                    {{ tx.coins > 0 ? '+' : '' }}{{ tx.coins }} 🪙
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                <span v-if="tx.purchase">{{ tx.purchase.payment_type }} #{{ tx.purchase.payment_reference }}</span>
                <span v-else class="text-slate-600">-</span>
            </td>
          </tr>
          <tr v-if="transactions.length === 0 && !loading">
            <td colspan="5" class="px-6 py-4 text-center text-slate-500">Nenhuma transação encontrada.</td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Botões de Paginação -->
    <div class="flex justify-between items-center mt-4">
        <button 
            @click="prevPage" 
            :disabled="currentPage <= 1"
            class="px-4 py-2 border border-slate-700 rounded bg-slate-900 text-slate-300 disabled:opacity-50 hover:bg-slate-800 hover:text-white transition-colors"
        >
            Anterior
        </button>
        <span class="text-slate-500">Página {{ currentPage }} de {{ lastPage }}</span>
         <button 
            @click="nextPage" 
            :disabled="currentPage >= lastPage"
            class="px-4 py-2 border border-slate-700 rounded bg-slate-900 text-slate-300 disabled:opacity-50 hover:bg-slate-800 hover:text-white transition-colors"
        >
            Seguinte
        </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const transactions = ref([])
const loading = ref(true)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const search = ref('')
const typeFilter = ref('')
const directionFilter = ref('')
const paymentTypeFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const coinsMin = ref('')
const coinsMax = ref('')
const sortBy = ref('transaction_datetime')
const sortOrder = ref('desc')
const perPage = ref(10)

const transactionTypes = [
  { id: 1, name: 'Bonus' },
  { id: 2, name: 'Coin purchase' },
  { id: 3, name: 'Game fee' },
  { id: 4, name: 'Match stake' },
  { id: 5, name: 'Game payout' },
  { id: 6, name: 'Match payout' }
]
const paymentTypes = ['MBWAY', 'PAYPAL', 'IBAN', 'MB', 'VISA']

const simpleDebounce = (fn, delay) => {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fn(...args), delay)
  }
}

const fetchTransactions = async (page = 1) => {
    loading.value = true
    error.value = null
    try {
        const response = await api.get('/admin/transactions', {
          params: {
            page,
            search: search.value || undefined,
            type_id: typeFilter.value || undefined,
            direction: directionFilter.value || undefined,
            payment_type: paymentTypeFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            coins_min: coinsMin.value || undefined,
            coins_max: coinsMax.value || undefined,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
            per_page: perPage.value
          }
        })
        transactions.value = response.data.data
        currentPage.value = response.data.current_page
        lastPage.value = response.data.last_page
    } catch (err) {
        error.value = "Falha ao carregar transações."
    } finally {
        loading.value = false
    }
}

const debouncedFilter = simpleDebounce(() => {
  fetchTransactions(1)
}, 500)

const applyFilters = () => {
  fetchTransactions(1)
}

const clearFilters = () => {
  search.value = ''
  typeFilter.value = ''
  directionFilter.value = ''
  paymentTypeFilter.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  coinsMin.value = ''
  coinsMax.value = ''
  sortBy.value = 'transaction_datetime'
  sortOrder.value = 'desc'
  perPage.value = 10
  fetchTransactions(1)
}

const prevPage = () => {
    if (currentPage.value > 1) {
        fetchTransactions(currentPage.value - 1)
    }
}

const nextPage = () => {
    if (currentPage.value < lastPage.value) {
        fetchTransactions(currentPage.value + 1)
    }
}

const formatDate = (dateStr) => {
    const date = new Date(dateStr)
    if (Number.isNaN(date.getTime())) return dateStr
    return date.toLocaleString('pt-PT', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(() => {
    fetchTransactions()
})
</script>

<style scoped>
.no-spin::-webkit-outer-spin-button,
.no-spin::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.no-spin[type='number'] {
  -moz-appearance: textfield;
}
</style>
