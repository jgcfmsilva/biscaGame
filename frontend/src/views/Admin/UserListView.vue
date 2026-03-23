<template>
  <div class="space-y-6 mt-6">
    <div class="space-y-6 animate-fade-in-up">
      <div class="flex flex-wrap gap-4 justify-between items-center">
      <h2 class="text-3xl font-bold text-white">Gestão de Utilizadores</h2>
      <div class="flex flex-wrap gap-3 items-center bg-slate-900/70 border border-slate-800 rounded-xl p-3">
        <div class="relative w-64">
          <input 
            v-model="search" 
            @input="debouncedFilter"
            type="text" 
            placeholder="Pesquisar utilizador..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-slate-200 placeholder-slate-500"
          >
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
        <select 
          v-model="blockedFilter" 
          @change="applyFilters"
          class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
        >
          <option value="">Estado</option>
          <option value="false">Ativos</option>
          <option value="true">Bloqueados</option>
        </select>
        <select 
          v-model="deletedFilter" 
          @change="applyFilters"
          class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          title="Filtra por estado de eliminação (soft delete)"
        >
          <option value="">Todos (inclui eliminados)</option>
          <option value="false">Só não eliminados</option>
          <option value="true">Só eliminados</option>
        </select>
        <select 
          v-model="sortBy" 
          @change="applyFilters"
          class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
        >
          <option value="created_at">Criado em</option>
          <option value="name">Nome</option>
          <option value="nickname">Nickname</option>
          <option value="email">Email</option>
          <option value="id">ID</option>
        </select>
        <select 
          v-model="sortOrder" 
          @change="applyFilters"
          class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
        >
          <option value="desc">Desc</option>
          <option value="asc">Asc</option>
        </select>
        <select 
          v-model.number="perPage" 
          @change="applyFilters"
          class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
        >
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

    <!-- Mensagens de erro ou sucesso -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded relative">
      <span class="block sm:inline">{{ error }}</span>
    </div>
    <div v-if="successMsg" class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded relative">
      <span class="block sm:inline">{{ successMsg }}</span>
    </div>

    <!-- Tabela de Utilizadores -->
    <div class="bg-slate-900 shadow-md rounded-lg overflow-hidden border border-slate-800 overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-800">
        <thead class="bg-slate-950">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Utilizador</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Tipo</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">Estado</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-slate-900 divide-y divide-slate-800">
          <tr
            v-for="user in users"
            :key="user.id"
            class="hover:bg-slate-800/50 transition-colors duration-200 cursor-pointer"
            @click="openUserDetails(user)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">#{{ user.id }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold mr-3 overflow-hidden text-slate-300">
                   <img :src="avatarUrl(user)" class="h-full w-full object-cover" alt="">
                </div>
                <div>
                    <div class="text-sm font-medium text-slate-200">{{ user.name }}</div>
                    <div class="text-xs text-slate-500">{{ user.nickname }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 max-w-[150px] truncate">{{ user.email }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                 <span :class="user.type === 'A' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                     {{ user.type === 'A' ? 'Admin' : 'Jogador' }}
                 </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                 <span v-if="user.deleted_at" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-700/40 text-slate-300 border border-slate-600/40">Eliminado</span>
                 <span v-else-if="user.blocked" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500/10 text-red-400 border border-red-500/20">Bloqueado</span>
                 <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Ativo</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div v-if="user.id !== authStore.user.id" class="flex justify-end space-x-3">
                    <button
                      v-if="!user.deleted_at && !user.blocked"
                      @click.stop="toggleBlock(user)"
                      class="text-amber-500 hover:text-amber-400 p-1 hover:bg-amber-500/10 rounded transition-colors"
                      title="Bloquear"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </button>
                    <button
                      v-else-if="!user.deleted_at"
                      @click.stop="toggleBlock(user)"
                      class="text-emerald-500 hover:text-emerald-400 p-1 hover:bg-emerald-500/10 rounded transition-colors"
                      title="Desbloquear"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </button>
                    
                    <button
                      v-if="!user.deleted_at"
                      @click.stop="confirmDelete(user)"
                      class="text-red-500 hover:text-red-400 p-1 hover:bg-red-500/10 rounded transition-colors"
                      title="Eliminar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                    <span v-else class="text-slate-600 italic">Eliminado</span>
                </div>
                <span v-else class="text-slate-600 italic">É você</span>
            </td>
          </tr>
          <tr v-if="users.length === 0 && !loading">
             <td colspan="6" class="px-6 py-4 text-center text-slate-500">Nenhum utilizador encontrado.</td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Paginação -->
    <div class="flex justify-between items-center mt-4 pb-6">
        <button 
            @click="page > 1 && fetchUsers(page - 1)" 
            :disabled="page <= 1"
            class="px-4 py-2 border border-slate-700 rounded bg-slate-900 text-slate-300 disabled:opacity-50 hover:bg-slate-800 hover:text-white transition-colors"
        >
            Anterior
        </button>
        <span class="text-slate-500">Página {{ page }} de {{ lastPage }}</span>
         <button 
            @click="page < lastPage && fetchUsers(page + 1)" 
            :disabled="page >= lastPage"
            class="px-4 py-2 border border-slate-700 rounded bg-slate-900 text-slate-300 disabled:opacity-50 hover:bg-slate-800 hover:text-white transition-colors"
        >
            Seguinte
        </button>
    </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-60 p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-lg p-6 w-full max-w-md shadow-2xl transform transition-all">
            <h3 class="text-xl font-bold mb-4 text-white">{{ confirmModalData.title }}</h3>
            <p class="text-slate-300 mb-6">{{ confirmModalData.message }}</p>
            
            <div class="flex justify-end space-x-3">
                <button 
                    @click="showConfirmModal = false" 
                    class="px-4 py-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition-colors"
                >
                    Cancelar
                </button>
                <button 
                    @click="executeConfirm" 
                    :disabled="processingAction"
                    :class="{
                        'bg-red-600 hover:bg-red-500': confirmModalData.confirmType === 'danger',
                        'bg-amber-600 hover:bg-amber-500': confirmModalData.confirmType === 'warning',
                        'bg-emerald-600 hover:bg-emerald-500': confirmModalData.confirmType === 'success'
                    }"
                    class="px-4 py-2 text-white rounded transition-colors flex items-center"
                >
                    <span v-if="processingAction" class="mr-2 h-4 w-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                    {{ confirmModalData.confirmText }}
                </button>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div v-if="showUserModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-2xl shadow-2xl transform transition-all">
        <div class="flex items-start justify-between mb-6 pb-4 border-b border-slate-800">
          <div class="flex items-center gap-4">
            <div class="h-14 w-14 rounded-full bg-slate-800 overflow-hidden border border-blue-500/30 ring-2 ring-blue-500/10">
              <img v-if="selectedUser" :src="avatarUrl(selectedUser)" class="h-full w-full object-cover" alt="">
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Detalhes do Utilizador</h3>
              <p class="text-slate-400 text-sm">#{{ selectedUser?.id ?? '—' }}</p>
            </div>
          </div>
          <button
            @click="showUserModal = false"
            class="text-slate-400 hover:text-white hover:bg-slate-800 rounded p-2 transition-colors"
            aria-label="Fechar"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="loadingUser" class="flex items-center text-slate-300">
          <span class="mr-2 h-4 w-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
          A carregar detalhes...
        </div>
        <div v-else-if="userDetailsError" class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded">
          {{ userDetailsError }}
        </div>
        <div v-else class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-200">
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Nome:</span>
              <span class="text-right">{{ selectedUser?.name ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Nickname:</span>
              <span class="text-right">{{ selectedUser?.nickname ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Email:</span>
              <span class="text-right break-all max-w-[220px]">{{ selectedUser?.email ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Tipo:</span>
              <span class="text-right">{{ selectedUser?.type === 'A' ? 'Admin' : 'Jogador' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Estado:</span>
              <span class="text-right">
                <span v-if="selectedUser?.deleted_at" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-700/40 text-slate-300 border border-slate-600/40">Eliminado</span>
                <span v-else-if="selectedUser?.blocked" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500/10 text-red-400 border border-red-500/20">Bloqueado</span>
                <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Ativo</span>
              </span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Saldo:</span>
              <span class="text-right">{{ selectedUser?.coins_balance ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Email verificado:</span>
              <span class="text-right">{{ formatDate(selectedUser?.email_verified_at) }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Criado em:</span>
              <span class="text-right">{{ formatDate(selectedUser?.created_at) }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Atualizado em:</span>
              <span class="text-right">{{ formatDate(selectedUser?.updated_at) }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Removido em:</span>
              <span class="text-right">{{ formatDate(selectedUser?.deleted_at) }}</span>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 justify-end border-t border-slate-800 pt-4">
            <span v-if="selectedUser?.id === authStore.user.id" class="text-slate-500 text-sm italic">É você</span>
            <span v-else-if="selectedUser?.deleted_at" class="text-slate-500 text-sm italic">Utilizador eliminado</span>
            <template v-else>
              <button
                v-if="!selectedUser?.blocked"
                @click="toggleBlock(selectedUser)"
                class="text-amber-500 hover:text-amber-400 p-2 hover:bg-amber-500/10 rounded transition-colors flex items-center gap-2"
                title="Bloquear"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Bloquear
              </button>
              <button
                v-else
                @click="toggleBlock(selectedUser)"
                class="text-emerald-500 hover:text-emerald-400 p-2 hover:bg-emerald-500/10 rounded transition-colors flex items-center gap-2"
                title="Desbloquear"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
                Desbloquear
              </button>

              <button
                @click="confirmDelete(selectedUser)"
                class="text-red-500 hover:text-red-400 p-2 hover:bg-red-500/10 rounded transition-colors flex items-center gap-2"
                title="Eliminar"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Eliminar
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const users = ref([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const search = ref('')
const typeFilter = ref('P')
const sortBy = ref('created_at')
const sortOrder = ref('desc')
const perPage = ref(10)
const blockedFilter = ref('')
const deletedFilter = ref('')
const error = ref(null)
const successMsg = ref(null)
const showUserModal = ref(false)
const selectedUser = ref(null)
const loadingUser = ref(false)
const userDetailsError = ref(null)
const route = useRoute()

// Modal state
const showConfirmModal = ref(false)
const processingAction = ref(false)
const confirmModalData = ref({
    title: '',
    message: '',
    confirmText: 'Confirmar',
    confirmType: 'danger', // danger, warning, success
    onConfirm: null
})

const simpleDebounce = (fn, delay) => {
    let timeout
    return (...args) => {
        clearTimeout(timeout)
        timeout = setTimeout(() => fn(...args), delay)
    }
}

const fetchUsers = async (p = 1) => {
  loading.value = true
  error.value = null
  const normalizedBlocked = blockedFilter.value === 'true'
    ? 'true'
    : blockedFilter.value === 'false'
      ? 'false'
      : undefined
  const normalizedDeleted = deletedFilter.value === 'true'
    ? 'true'
    : deletedFilter.value === 'false'
      ? 'false'
      : undefined
  try {
    const response = await api.get('/admin/users', {
        params: {
            page: p,
            search: search.value,
            per_page: perPage.value,
            type: typeFilter.value || undefined,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
            blocked: normalizedBlocked,
            deleted: normalizedDeleted
        }
    })
    users.value = response.data.data
    page.value = response.data.current_page
    lastPage.value = response.data.last_page
  } catch (err) {
      console.error(err)
      error.value = "Falha ao carregar utilizadores."
  } finally {
      loading.value = false
  }
}

const debouncedFilter = simpleDebounce(() => {
    fetchUsers(1)
}, 500)
const applyFilters = () => {
    fetchUsers(1)
}
const clearFilters = () => {
    search.value = ''
    typeFilter.value = 'P'
    sortBy.value = 'created_at'
    sortOrder.value = 'desc'
    perPage.value = 10
    blockedFilter.value = ''
    deletedFilter.value = ''
    fetchUsers(1)
}

const avatarUrl = (user) => {
    return authStore.userPhotoUrl(user?.photo_avatar_filename || user?.avatar || '')
}
const openConfirmModal = (title, message, confirmText, confirmType, onConfirm) => {
    confirmModalData.value = {
        title,
        message,
        confirmText,
        confirmType,
        onConfirm
    }
    showConfirmModal.value = true
}

const executeConfirm = async () => {
    if (!confirmModalData.value.onConfirm) return
    
    processingAction.value = true
    try {
        await confirmModalData.value.onConfirm()
        showConfirmModal.value = false
    } finally {
        processingAction.value = false
    }
}

const toggleBlock = (user) => {
    const action = user.blocked ? 'unblock' : 'block'
    const actionPt = user.blocked ? 'desbloquear' : 'bloquear'
    
    openConfirmModal(
        user.blocked ? 'Desbloquear Utilizador' : 'Bloquear Utilizador',
        `Tem a certeza que deseja ${actionPt} o utilizador ${user.nickname}?`,
        user.blocked ? 'Desbloquear' : 'Bloquear',
        user.blocked ? 'success' : 'warning',
        async () => {
            try {
                await api.patch(`/admin/users/${user.id}/${action}`)
                user.blocked = !user.blocked
                if (blockedFilter.value !== '') {
                    users.value = users.value.filter((item) => item.id !== user.id)
                }
                successMsg.value = `Utilizador ${user.nickname} ${action === 'unblock' ? 'desbloqueado' : 'bloqueado'} com sucesso.`
                setTimeout(() => successMsg.value = null, 3000)
            } catch (err) {
                error.value = `Falha ao ${actionPt} utilizador.`
            }
        }
    )
}

const confirmDelete = (user) => {
     openConfirmModal(
        'Eliminar Utilizador',
        `Tem a certeza que deseja ELIMINAR ${user.nickname}? Esta ação é irreversível e apagará todos os dados associados.`,
        'Eliminar Permanentemente',
        'danger',
        async () => {
            try {
                await api.delete(`/admin/users/${user.id}`)
                successMsg.value = `Utilizador ${user.nickname} eliminado com sucesso.`
                fetchUsers(page.value) 
                setTimeout(() => successMsg.value = null, 3000)
             } catch (err) {
                 error.value = `Falha ao eliminar utilizador: ` + (err.response?.data?.message || err.message)
             }
        }
     )
}

const openUserDetails = async (user) => {
    showUserModal.value = true
    loadingUser.value = true
    userDetailsError.value = null
    selectedUser.value = null

    try {
        const res = await api.get(`/admin/users/${user.id}`)
        selectedUser.value = res.data
    } catch (err) {
        console.error(err)
        userDetailsError.value = 'Falha ao carregar detalhes do utilizador.'
        selectedUser.value = user
    } finally {
        loadingUser.value = false
    }
}

const formatDate = (value) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleString('pt-PT', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(async () => {
    await fetchUsers()
    const targetId = route.query.userId ? Number(route.query.userId) : null
    if (targetId) {
        openUserDetails({ id: targetId })
    }
})
</script>
