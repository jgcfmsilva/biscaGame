<template>
  <DefaultLayout>
    <section class="mx-auto max-w-6xl px-4 py-8 space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-white">As minhas partidas</h2>

        <div class="flex flex-wrap gap-3 items-center bg-slate-900/70 border border-slate-800 rounded-xl p-3">
          <div class="relative w-full md:w-64">
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Pesquisar por ID ou nickname..."
              class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500 placeholder-slate-500"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0 0114 0z" />
              </svg>
            </div>
          </div>

          <select v-model="statusFilter" @change="fetchItems(1)" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500">
            <option value="">Todos os estados</option>
            <option value="Ended">Terminado</option>
            <option value="Interrupted">Interrompido</option>
            <option value="Progress">A decorrer</option>
          </select>

          <select v-model="sortBy" @change="fetchItems(1)" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500">
            <option value="id">ID</option>
            <option value="created_at">Criado em</option>
            <option value="ended_at">Terminou em</option>
            <option value="stake">Stake</option>
          </select>

          <select v-model="sortDir" @change="fetchItems(1)" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500">
            <option value="desc">Desc</option>
            <option value="asc">Asc</option>
          </select>

          <select v-model.number="perPage" @change="fetchItems(1)" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500">
            <option :value="10">10 / pág</option>
            <option :value="20">20 / pág</option>
            <option :value="50">50 / pág</option>
          </select>

          <button
            @click="clearFilters"
            class="px-3 py-2 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 hover:bg-slate-800 hover:text-white transition-colors"
          >
            Limpar filtros
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-center py-4 text-slate-400">A carregar dados...</div>
      <div v-if="error" class="text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded">{{ error }}</div>

      <div class="bg-slate-900 shadow-md rounded-lg overflow-hidden border border-slate-800 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-800">
          <thead class="bg-slate-950">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Data</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Jogadores</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Vencedor</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Estado</th>
            </tr>
          </thead>
          <tbody class="bg-slate-900 divide-y divide-slate-800">
            <tr
              v-for="item in items"
              :key="item.id"
              class="hover:bg-slate-800/50 transition-colors cursor-pointer"
              @click="goToDetail(item.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">#{{ item.id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ formatDate(item.created_at) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-200">
                  <span class="font-bold">{{ item.player1?.nickname || 'Bot/Apagado' }}</span> vs
                  <span class="font-bold">{{ item.player2?.nickname || 'Bot/Apagado' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="item.winner" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  {{ item.winner.nickname }}
                </span>
                <span v-else class="text-slate-600">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <span :class="statusBadge(item.status).class" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
                  {{ statusBadge(item.status).label }}
                </span>
              </td>
            </tr>
            <tr v-if="!items.length && !loading">
              <td colspan="5" class="px-6 py-4 text-center text-slate-500">Nenhuma partida encontrada.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-between items-center mt-4 pb-6">
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
          :disabled="!hasMorePages"
          class="px-4 py-2 border border-slate-700 rounded bg-slate-900 text-slate-300 disabled:opacity-50 hover:bg-slate-800 hover:text-white transition-colors"
        >
          Próximo
        </button>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import DefaultLayout from '@/layouts/DefaultLayout.vue'

const items = ref([])
const loading = ref(true)
const error = ref(null)
const currentPage = ref(1)
const hasMorePages = ref(false)
const lastPage = ref(1)
const statusFilter = ref('')
const searchQuery = ref('')
const perPage = ref(10)
const sortBy = ref('id')
const sortDir = ref('desc')
let searchTimeout = null

const router = useRouter()

const statusBadge = (status) => {
  const value = (status || '').toString().toLowerCase()
  if (value === 'ended' || value === 'e') {
    return { label: 'Terminado', class: 'bg-slate-800 text-slate-200 border-slate-700' }
  }
  if (value === 'progress' || value === 'p') {
    return { label: 'A Decorrer', class: 'bg-blue-500/10 text-blue-300 border-blue-500/30' }
  }
  return { label: 'Interrompido', class: 'bg-red-500/10 text-red-400 border-red-500/30' }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchItems(1)
  }, 500)
}

const fetchItems = async (page = 1) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/player/matches', {
      params: {
        page: page,
        per_page: perPage.value,
        status: statusFilter.value,
        search: searchQuery.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value
      }
    })
    items.value = response.data.data
    currentPage.value = response.data.current_page
    lastPage.value = response.data.last_page ?? currentPage.value
    hasMorePages.value = currentPage.value < lastPage.value
  } catch (err) {
    error.value = 'Não foi possível carregar o histórico de partidas.'
  } finally {
    loading.value = false
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    fetchItems(currentPage.value - 1)
  }
}

const nextPage = () => {
  if (hasMorePages.value) {
    fetchItems(currentPage.value + 1)
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  perPage.value = 10
  sortBy.value = 'id'
  sortDir.value = 'desc'
  fetchItems(1)
}

const goToDetail = (id) => {
  if (!id) return
  router.push({ name: 'player-match-detail', params: { id } })
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('pt-PT')
}

onMounted(() => {
  fetchItems()
})
</script>
