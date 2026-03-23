<template>
  <div class="space-y-6 mt-6">
    <div class="space-y-6 animate-fade-in-up">
      <div class="flex flex-wrap gap-4 justify-between items-center">
      <h2 class="text-3xl font-bold text-white">Administradores</h2>
      <div class="flex flex-wrap gap-3 items-center bg-slate-900/70 border border-slate-800 rounded-xl p-3">
        <div class="relative w-64">
          <input 
            v-model="search" 
            @input="debouncedFilter"
            type="text" 
            placeholder="Pesquisar admin..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-slate-200 placeholder-slate-500"
          >
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
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
          v-model="passwordFilter" 
          @change="applyFilters"
          class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500"
          title="Admins com password pendente"
        >
          <option value="">Todos</option>
          <option value="true">Só pendentes</option>
          <option value="false">Só ativos</option>
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
        <button 
          @click="showCreateModal = true" 
          class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center space-x-2 shadow-lg shadow-blue-900/20"
        >
          <span>+ Novo Admin</span>
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

    <!-- Tabela de Administradores -->
    <div class="bg-slate-900 shadow-md rounded-lg overflow-hidden border border-slate-800 overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-800">
        <thead class="bg-slate-950">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Admin</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Estado</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-slate-900 divide-y divide-slate-800">
          <tr
            v-for="admin in admins"
            :key="admin.id"
            class="hover:bg-slate-800/50 transition-colors duration-200 cursor-pointer"
            @click="openAdminDetails(admin)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">#{{ admin.id }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold mr-3 overflow-hidden text-slate-300">
                   <img :src="avatarUrl(admin)" class="h-full w-full object-cover" alt="">
                </div>
                <div>
                    <div class="text-sm font-medium text-slate-200">{{ admin.name }}</div>
                    <div class="text-xs text-slate-500">{{ admin.nickname }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ admin.email }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span
                v-if="admin.custom?.must_change_password"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20"
              >
                Password pendente
              </span>
              <span v-else class="text-slate-500">Ativo</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <template v-if="admin.id !== authStore.user.id">
                    <button
                      v-if="admin.custom?.must_change_password"
                      @click.stop="confirmDelete(admin)"
                      class="text-red-500 hover:text-red-400"
                      title="Eliminar"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                </template>
                <span v-else class="text-slate-600 italic">É você</span>
            </td>
          </tr>
          <tr v-if="admins.length === 0 && !loading">
             <td colspan="5" class="px-6 py-4 text-center text-slate-500">Nenhum administrador encontrado.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <div class="flex justify-between items-center mt-4 pb-6">
        <button 
            @click="page > 1 && fetchAdmins(page - 1)" 
            :disabled="page <= 1"
            class="px-4 py-2 border border-slate-700 rounded bg-slate-900 text-slate-300 disabled:opacity-50 hover:bg-slate-800 hover:text-white transition-colors"
        >
            Anterior
        </button>
        <span class="text-slate-500">Página {{ page }} de {{ lastPage }}</span>
         <button 
            @click="page < lastPage && fetchAdmins(page + 1)" 
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

    <!-- Admin Details Modal -->
    <div v-if="showAdminModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-2xl shadow-2xl transform transition-all">
        <div class="flex items-start justify-between mb-6 pb-4 border-b border-slate-800">
          <div class="flex items-center gap-4">
            <div class="h-14 w-14 rounded-full bg-slate-800 overflow-hidden border border-blue-500/30 ring-2 ring-blue-500/10">
              <img v-if="selectedAdmin" :src="avatarUrl(selectedAdmin)" class="h-full w-full object-cover" alt="">
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Detalhes do Administrador</h3>
              <p class="text-slate-400 text-sm">#{{ selectedAdmin?.id ?? '—' }}</p>
            </div>
          </div>
          <button
            @click="showAdminModal = false"
            class="text-slate-400 hover:text-white hover:bg-slate-800 rounded p-2 transition-colors"
            aria-label="Fechar"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="loadingAdmin" class="flex items-center text-slate-300">
          <span class="mr-2 h-4 w-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
          A carregar detalhes...
        </div>
        <div v-else-if="adminDetailsError" class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded">
          {{ adminDetailsError }}
        </div>
        <div v-else class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-200">
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Nome:</span>
              <span class="text-right">{{ selectedAdmin?.name ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Nickname:</span>
              <span class="text-right">{{ selectedAdmin?.nickname ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Email:</span>
              <span class="text-right">{{ selectedAdmin?.email ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Tipo:</span>
              <span class="text-right">Admin</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Estado:</span>
              <span class="text-right">
                {{ selectedAdmin?.custom?.must_change_password ? 'Password pendente' : 'Ativo' }}
              </span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Saldo:</span>
              <span class="text-right">{{ selectedAdmin?.coins_balance ?? '—' }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Email verificado:</span>
              <span class="text-right">{{ formatDate(selectedAdmin?.email_verified_at) }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Criado em:</span>
              <span class="text-right">{{ formatDate(selectedAdmin?.created_at) }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Atualizado em:</span>
              <span class="text-right">{{ formatDate(selectedAdmin?.updated_at) }}</span>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-slate-400">Removido em:</span>
              <span class="text-right">{{ formatDate(selectedAdmin?.deleted_at) }}</span>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-800">
            <div class="text-xs text-slate-500">
              <span v-if="selectedAdmin?.custom?.must_change_password">Password pendente: pode eliminar.</span>
              <span v-else>Sem ações disponíveis.</span>
            </div>
            <div class="flex gap-2">
              <Button
                v-if="canDeleteSelectedAdmin"
                variant="destructive"
                size="sm"
                @click="confirmDelete(selectedAdmin)"
              >
                Eliminar
              </Button>
              <span v-else class="text-xs text-slate-600">
                {{ selectedAdmin?.id === authStore.user.id ? 'Não pode eliminar-se' : '' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Janela para criar novo admin -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-950/90 backdrop-blur-xl p-6 shadow-[0_18px_45px_rgba(15,23,42,0.8)]">
            <div class="mb-6 space-y-2 text-center">
                <h3 class="text-xl font-semibold text-slate-50">Criar Novo Administrador</h3>
                <p class="text-xs text-slate-400">
                  O admin terá de alterar a password no primeiro acesso.
                </p>
            </div>
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="createAdmin">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-200">Nome</label>
                    <Input v-model="newAdmin.name" type="text" placeholder="Nome e apelido"
                        class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                    <p v-if="errors.name" class="text-xs text-rose-400">{{ errors.name }}</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-200">Alcunha</label>
                    <Input v-model="newAdmin.nickname" type="text" placeholder="Nome visível"
                        class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                    <p v-if="errors.nickname" class="text-xs text-rose-400">{{ errors.nickname }}</p>
                </div>
                <div class="space-y-1.5 md:col-span-2">
                    <label class="text-xs font-medium text-slate-200">Email</label>
                    <Input v-model="newAdmin.email" type="email" placeholder="email@exemplo.com"
                        class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                    <p v-if="errors.email" class="text-xs text-rose-400">{{ errors.email }}</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-200">Password</label>
                    <div class="relative">
                        <Input v-model="newAdmin.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••"
                            class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                        <button type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                            @click="showPassword = !showPassword">
                            <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="errors.password" class="text-xs text-rose-400">{{ errors.password }}</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-200">Confirmar password</label>
                    <div class="relative">
                        <Input v-model="newAdmin.passwordConfirm" :type="showPasswordConfirm ? 'text' : 'password'" placeholder="••••••"
                            class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                        <button type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                            @click="showPasswordConfirm = !showPasswordConfirm">
                            <component :is="showPasswordConfirm ? EyeOff : Eye" class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="errors.passwordConfirm" class="text-xs text-rose-400">{{ errors.passwordConfirm }}</p>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="text-xs font-medium text-slate-200">Imagem (opcional)</label>
                    <Input type="file" accept="image/*" @change="handleFileChange" class="bg-slate-950 border-slate-700 text-sm
                        file:bg-slate-900 file:border-0 file:text-xs
                        file:text-slate-200 file:p-1 file:cursor-pointer" />
                    <Avatar class="mt-2 w-20 h-20 border border-slate-700 rounded-full overflow-hidden">
                        <AvatarImage :src="previewUrl || placeholder" class="object-cover" />
                        <AvatarFallback class="bg-slate-800 text-slate-300 text-xs flex items-center justify-center">
                            Pré-visualização
                        </AvatarFallback>
                    </Avatar>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3">
                    <Button type="button" variant="ghost" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-200">
                        Cancelar
                    </Button>
                    <Button type="submit" class="btn btn-primary">
                        {{ creating ? "A criar..." : "Criar admin" }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar"
import { Eye, EyeOff } from "lucide-vue-next";
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const admins = ref([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const search = ref('')
const sortBy = ref('created_at')
const sortOrder = ref('desc')
const perPage = ref(10)
const deletedFilter = ref('')
const passwordFilter = ref('')
const error = ref(null)
const successMsg = ref(null)
const showCreateModal = ref(false)
const showAdminModal = ref(false)
const selectedAdmin = ref(null)
const loadingAdmin = ref(false)
const adminDetailsError = ref(null)
const showConfirmModal = ref(false)
const processingAction = ref(false)
const confirmModalData = ref({
    title: '',
    message: '',
    confirmText: 'Confirmar',
    confirmType: 'danger',
    onConfirm: null
})
const creating = ref(false)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const avatarFile = ref(null)
const previewUrl = ref("")
const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')
const placeholder = `${apiBase || window.location.origin}/api/player/avatar/photos_avatars/anonymous.png`

const errors = ref({
    name: "",
    nickname: "",
    email: "",
    password: "",
    passwordConfirm: "",
});

const newAdmin = ref({
    name: '',
    nickname: '',
    email: '',
    password: '',
    passwordConfirm: ''
})

const canDeleteSelectedAdmin = computed(() => {
    if (!selectedAdmin.value) return false
    if (selectedAdmin.value.id === authStore.user.id) return false
    return !!selectedAdmin.value.custom?.must_change_password
})

function handleFileChange(e) {
    const file = e.target.files?.[0];
    avatarFile.value = file || null;
    previewUrl.value = file ? URL.createObjectURL(file) : "";
}

const simpleDebounce = (fn, delay) => {
    let timeout
    return (...args) => {
        clearTimeout(timeout)
        timeout = setTimeout(() => fn(...args), delay)
    }
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

const fetchAdmins = async (p = 1) => {
    loading.value = true
    try {
        const response = await api.get('/admin/users', {
            params: {
                type: 'A',
                page: p,
                search: search.value,
                per_page: perPage.value,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
                deleted: deletedFilter.value !== '' ? deletedFilter.value : undefined,
                must_change_password: passwordFilter.value !== '' ? passwordFilter.value : undefined
            }
        })
        admins.value = response.data.data
        page.value = response.data.current_page
        lastPage.value = response.data.last_page
    } catch (err) {
        error.value = 'Falha ao carregar administradores.'
    } finally {
        loading.value = false
    }
}

const debouncedFilter = simpleDebounce(() => {
    fetchAdmins(1)
}, 500)
const applyFilters = () => {
    fetchAdmins(1)
}
const clearFilters = () => {
    search.value = ''
    sortBy.value = 'created_at'
    sortOrder.value = 'desc'
    perPage.value = 10
    deletedFilter.value = ''
    passwordFilter.value = ''
    fetchAdmins(1)
}

const createAdmin = async () => {
    try {
        error.value = null;
        successMsg.value = null;
        errors.value = { name: "", nickname: "", email: "", password: "", passwordConfirm: "" };
        if (!newAdmin.value.nickname || !newAdmin.value.email || !newAdmin.value.password || !newAdmin.value.passwordConfirm) {
            toast.error('Preenche todos os campos obrigatórios.');
            return;
        }
        if (newAdmin.value.password !== newAdmin.value.passwordConfirm) {
            toast.error('As passwords não coincidem.');
            return;
        }

        const formData = new FormData();
        if (newAdmin.value.name) formData.append("name", newAdmin.value.name);
        formData.append("nickname", newAdmin.value.nickname);
        formData.append("email", newAdmin.value.email);
        formData.append("password", newAdmin.value.password);
        formData.append("password_confirmation", newAdmin.value.passwordConfirm);
        if (avatarFile.value) {
            formData.append("photo", avatarFile.value);
        }

        creating.value = true;
        await api.post('/admin/admins', formData, {
            headers: { "Content-Type": "multipart/form-data" },
        })
        toast.success('Administrador criado com sucesso.')
        showCreateModal.value = false
        newAdmin.value = { name: '', nickname: '', email: '', password: '', passwordConfirm: '' }
        avatarFile.value = null
        previewUrl.value = ""
        showPassword.value = false
        showPasswordConfirm.value = false
        fetchAdmins()
    } catch (err) {
        const message = err.response?.data?.message || err.message || 'Falha ao criar administrador.'
        toast.error(message)
    } finally {
        creating.value = false;
    }
}

const confirmDelete = (admin) => {
    openConfirmModal(
        'Eliminar Administrador',
        `Tem a certeza que deseja ELIMINAR ${admin.nickname || admin.name}? Esta ação é irreversível.`,
        'Eliminar Permanentemente',
        'danger',
        async () => {
            try {
                await api.delete(`/admin/admins/${admin.id}`)
                successMsg.value = 'Administrador apagado.'
                fetchAdmins()
                setTimeout(() => successMsg.value = null, 3000)
            } catch (err) {
                error.value = 'Falha ao apagar administrador.'
            }
        }
    )
}

const openAdminDetails = async (admin) => {
    showAdminModal.value = true
    loadingAdmin.value = true
    adminDetailsError.value = null
    selectedAdmin.value = null

    try {
        const res = await api.get(`/admin/users/${admin.id}`)
        selectedAdmin.value = res.data
    } catch (err) {
        console.error(err)
        adminDetailsError.value = 'Falha ao carregar detalhes do administrador.'
        selectedAdmin.value = admin
    } finally {
        loadingAdmin.value = false
    }
}

const avatarUrl = (admin) => {
    return authStore.userPhotoUrl(admin?.photo_avatar_filename || admin?.avatar || '')
}

const formatDate = (value) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleString('pt-PT', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(() => {
    fetchAdmins()
})
</script>
