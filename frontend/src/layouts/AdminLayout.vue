<template>
  <div class="h-screen overflow-hidden bg-slate-950 flex font-sans relative">
    
    <!-- Mobile Sidebar Toggle -->
    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden absolute top-4 left-4 z-50 p-2 bg-slate-800 rounded-lg text-white shadow-lg">
        <Menu v-if="!sidebarOpen" class="w-6 h-6" />
        <X v-else class="w-6 h-6" />
    </button>

    <!-- Overlay -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 md:hidden backdrop-blur-sm"></div>

    <!-- Menu Lateral -->
    <aside :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 text-slate-300 flex flex-col shrink-0 transition-transform duration-300 md:relative md:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
    ]">
      <div class="p-6 text-center border-b border-slate-800">
        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">Bisca Admin</h1>
      </div>
      
      <nav class="flex-1 p-4 space-y-2">
        <router-link 
          :to="{ name: 'admin-dashboard' }" 
          @click="sidebarOpen = false"
          class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200 hover:bg-slate-800 hover:translate-x-1 hover:text-white group"
          active-class="bg-blue-600/10 text-blue-400 font-medium"
        >
          <div class="p-1.5 rounded bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
             <LayoutDashboard class="w-4 h-4" />
          </div>
          <span>Gestão Financeira</span>
        </router-link>

        <router-link :to="{ name: 'admin-transactions' }" @click="sidebarOpen = false" class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200 hover:bg-slate-800 hover:translate-x-1 hover:text-white group" active-class="bg-blue-600/10 text-blue-400 font-medium">
          <div class="p-1.5 rounded bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
            <Receipt class="w-4 h-4" />
          </div>
          <span>Gerir Transações</span>
        </router-link>
        
        <router-link 
          :to="{ name: 'admin-users' }" 
          @click="sidebarOpen = false"
          class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200 hover:bg-slate-800 hover:translate-x-1 hover:text-white group"
          active-class="bg-blue-600/10 text-blue-400 font-medium"
        >
          <div class="p-1.5 rounded bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
            <Users class="w-4 h-4" />
          </div>
          <span>Gerir Utilizadores</span>
        </router-link>
        
        <router-link 
          :to="{ name: 'admin-admins' }" 
          @click="sidebarOpen = false"
          class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200 hover:bg-slate-800 hover:translate-x-1 hover:text-white group"
          active-class="bg-blue-600/10 text-blue-400 font-medium"
        >
          <div class="p-1.5 rounded bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
            <Shield class="w-4 h-4" />
          </div>
          <span>Gerir Administradores</span>
        </router-link>
        
        <router-link :to="{ name: 'globalMatches' }" @click="sidebarOpen = false" class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200 hover:bg-slate-800 hover:translate-x-1 hover:text-white group" active-class="bg-blue-600/10 text-blue-400 font-medium">
          <div class="p-1.5 rounded bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
            <Gamepad2 class="w-4 h-4" />
          </div>
          <span>Visualizar Partidas</span>
        </router-link>

      </nav>

      <div class="p-4 border-t border-slate-800 space-y-3">
        <router-link 
          to="/" 
          class="w-full flex items-center justify-center space-x-2 px-4 py-2 border border-slate-700 hover:bg-slate-800 text-slate-300 rounded transition-colors text-sm"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          <span>Ir para o Site</span>
        </router-link>

        <button 
          @click="logout" 
          class="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded transition-colors text-sm font-medium"
        >
          <span>Terminar Sessão</span>
        </button>
      </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="flex-1 p-8 pt-16 md:pt-8 overflow-y-auto max-h-screen text-slate-200 w-full">
      <router-view></router-view>
    </main>

    <VideoChat />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { LayoutDashboard, Users, Shield, Receipt, ChevronLeft, Gamepad2, Menu, X } from 'lucide-vue-next'
import VideoChat from '@/components/VideoChat.vue'

const authStore = useAuthStore()
const router = useRouter()
const sidebarOpen = ref(false)

const logout = async () => {
    await authStore.logout()
    router.push({ name: 'login' })
}
</script>
