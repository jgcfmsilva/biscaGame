<template>
  <div>
    <h2 class="text-3xl font-bold text-white mb-6">Gestão Financeira</h2>

    <!-- A carregar -->
    <div v-if="loading" class="text-center py-10">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
      <p class="mt-4 text-slate-400">A carregar estatísticas...</p>
    </div>

    <!-- Deu erro -->
    <div v-else-if="error" class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded relative mb-6">
      <span class="block sm:inline">{{ error }}</span>
    </div>

    <div v-else class="space-y-8">
      
      <!-- Admin Actions Section (Now at the Top) -->
      <div class="bg-slate-950/80 p-6 rounded-2xl border border-emerald-700/30 relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mt-6 -mr-6 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all duration-700"></div>
        
        <div class="relative z-10">
            <h3 class="text-lg font-bold mb-2 text-white flex items-center gap-2">
                <span class="bg-emerald-500/20 p-1.5 rounded-lg text-emerald-300">🎁</span>
                Oferecer Moedas (Bónus)
            </h3>
            <p class="text-sm text-slate-400 mb-6 max-w-2xl">Utilize este formulário para creditar moedas diretamente na carteira de um utilizador. Esta ação é imediata e será registada nas transações globais.</p>
            
            <form @submit.prevent="handleGrantCoins" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full max-w-sm">
                    <label class="block text-xs font-medium text-slate-400 mb-1 ml-1">Email do Utilizador</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input v-model="grantForm.email" type="email" required placeholder="ex: user@example.com"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-emerald-700/50 rounded-lg text-slate-200 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 transition-all" />
                    </div>
                </div>
                <div class="w-full md:w-40">
                    <label class="block text-xs font-medium text-slate-400 mb-1 ml-1">Quantidade</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-500 font-bold">🪙</span>
                        </div>
                        <input v-model="grantForm.amount" type="number" min="1" max="10000" required
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-emerald-700/50 rounded-lg text-slate-200 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 transition-all font-mono" />
                    </div>
                </div>
                <button type="submit" :disabled="granting"
                    class="w-full md:w-auto px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-medium rounded-lg shadow-lg shadow-emerald-900/30 transition-all transform active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="granting" class="h-4 w-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                    <span v-else>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <span>Enviar Moedas</span>
                </button>
            </form>
        </div>
      </div>

      <!-- Cartões com totais -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
         <!-- Total Comprado -->
         <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800 hover:border-blue-500/30 transition-colors group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400 group-hover:bg-blue-500/20 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs font-bold px-2 py-1 rounded-full bg-slate-800 text-slate-400">EUR</span>
            </div>
            <h3 class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">Total Comprado</h3>
            <p class="text-2xl font-bold text-white">{{ formatCurrency(stats.total_purchased_euros) }}</p>
         </div>

         <!-- Moedas em Circulação -->
         <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800 hover:border-amber-500/30 transition-colors group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-amber-500/10 rounded-lg text-amber-400 group-hover:bg-amber-500/20 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xs font-bold px-2 py-1 rounded-full bg-slate-800 text-slate-400">Total</span>
            </div>
            <h3 class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">Moedas em Circulação</h3>
            <p class="text-2xl font-bold text-amber-400">{{ stats.total_coins_in_system }} 🪙</p>
         </div>

         <!-- Total de Transações -->
         <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800 hover:border-purple-500/30 transition-colors group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-purple-500/10 rounded-lg text-purple-400 group-hover:bg-purple-500/20 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <span class="text-xs font-bold px-2 py-1 rounded-full bg-slate-800 text-slate-400">Count</span>
            </div>
            <h3 class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">Total de Transações</h3>
            <p class="text-2xl font-bold text-white">{{ stats.total_transactions }}</p>
         </div>

         <!-- Total de Jogos -->
         <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800 hover:border-emerald-500/30 transition-colors group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-emerald-500/10 rounded-lg text-emerald-400 group-hover:bg-emerald-500/20 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs font-bold px-2 py-1 rounded-full bg-slate-800 text-slate-400">Global</span>
            </div>
            <h3 class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">Total de Jogos</h3>
            <p class="text-2xl font-bold text-white">{{ stats.total_games }}</p>
         </div>

         <!-- Total de Partidas -->
         <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800 hover:border-pink-500/30 transition-colors group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-pink-500/10 rounded-lg text-pink-400 group-hover:bg-pink-500/20 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="text-xs font-bold px-2 py-1 rounded-full bg-slate-800 text-slate-400">Matches</span>
            </div>
            <h3 class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">Total de Partidas</h3>
            <p class="text-2xl font-bold text-white">{{ stats.total_matches }}</p>
         </div>
      </div>

      <!-- Linha dos Gráficos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Purchases By Month Chart -->
        <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800">
          <h3 class="text-lg font-semibold mb-4 text-slate-100">Compras (Últimos 12 Meses)</h3>
          <div class="h-64">
             <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Top Users Table -->
        <div class="bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-800">
          <h3 class="text-lg font-semibold mb-4 text-slate-100">Top 10 Utilizadores Mais Ricos</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
              <thead>
                <tr>
                   <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Alcunha</th>
                   <th class="px-3 py-2 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Saldo</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="user in stats.balance_by_user" :key="user.nickname">
                  <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-300">{{ user.nickname || 'Desconhecido' }}</td>
                  <td class="px-3 py-2 whitespace-nowrap text-sm text-right font-medium text-amber-500">{{ user.coins_balance }} 🪙</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

// Chart.js components
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

import { toast } from 'vue-sonner'

const authStore = useAuthStore()

const stats = ref(null)
const loading = ref(true)
const error = ref(null)

const granting = ref(false)
const grantForm = ref({
    email: '',
    amount: 100
})

const fetchStats = async () => {
  try {
    const response = await api.get('/admin/statistics')
    stats.value = response.data
  } catch (err) {
    error.value = 'Falha ao carregar estatísticas: ' + (err.response?.data?.message || err.message)
  } finally {
    loading.value = false
  }
}

const handleGrantCoins = async () => {
    granting.value = true
    try {
        await api.post('/admin/transactions/grant', grantForm.value)
        toast.success(`Enviadas ${grantForm.value.amount} moedas para ${grantForm.value.email}`)
        grantForm.value = { email: '', amount: 100 } // Reset but keep default amount
        fetchStats() // Refresh stats to show new totals
    } catch (err) {
        toast.error(err.response?.data?.message || 'Erro ao enviar moedas.')
    } finally {
        granting.value = false
    }
}

const formatCurrency = (value) => {
// ... existing code
  return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value || 0)
}

// Preparar dados para o gráfico
const chartData = computed(() => {
  if (!stats.value || !stats.value.purchases_by_month) return null

  // Sort by month ascending for the chart
  const sortedData = [...stats.value.purchases_by_month].sort((a, b) => a.month.localeCompare(b.month))

  return {
    labels: sortedData.map(d => d.month),
    datasets: [
      {
        label: 'Vendas (€)',
        backgroundColor: '#3b82f6',
        data: sortedData.map(d => d.total)
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: '#cbd5e1' // slate-300
      }
    }
  },
  scales: {
    y: {
      grid: {
        color: '#1e293b' // slate-800
      },
      ticks: {
        color: '#94a3b8' // slate-400
      }
    },
    x: {
      grid: {
        color: '#1e293b' // slate-800
      },
      ticks: {
        color: '#94a3b8' // slate-400
      }
    }
  }
}

onMounted(() => {
  fetchStats()
})
</script>
