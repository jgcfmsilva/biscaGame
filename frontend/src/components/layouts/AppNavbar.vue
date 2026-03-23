<template>
    <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-950/80 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <RouterLink to="/" class="flex items-center gap-4">
                <div class="text-xl font-bold text-emerald-400 leading-none">
                    B
                </div>
                <div class="flex flex-col leading-tight gap-0.5">
                    <span class="text-sm font-semibold tracking-tight text-slate-100">Bisca</span>
                    <span class="text-xs text-slate-400">Game Platform</span>
                </div>
            </RouterLink>

            <div class="hidden items-center gap-6 md:flex">
                <RouterLink to="/" class="group relative flex items-center gap-2 text-sm text-slate-400 transition hover:text-white py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-slate-500 transition-colors group-hover:text-indigo-400"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Início
                    <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-indigo-400 transition-all duration-300 group-hover:w-full"></span>
                </RouterLink>
                <RouterLink v-if="!auth.isAdmin" to="/play" class="group relative flex items-center gap-2 text-sm text-slate-400 transition hover:text-white py-2">
                    <Gamepad2 class="w-4 h-4" />
                    Jogar
                    <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-emerald-400 transition-all duration-300 group-hover:w-full"></span>
                </RouterLink>
                <RouterLink to="/leaderboard" class="group relative flex items-center gap-2 text-sm text-slate-400 transition hover:text-white py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-slate-500 transition-colors group-hover:text-amber-400"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17"/><path d="M14 14.66V17"/><path d="M18 2h-3a5 5 0 0 0-5 5v7.09a5 5 0 0 0 5 5h3"/><path d="M6 2h3a5 5 0 0 1 5 5v7.09a5 5 0 0 1-5 5H6"/></svg>
                    Leaderboards
                    <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-amber-400 transition-all duration-300 group-hover:w-full"></span>
                </RouterLink>
                <RouterLink to="/stats" class="group relative flex items-center gap-2 text-sm text-slate-400 transition hover:text-white py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-slate-500 transition-colors group-hover:text-sky-400"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    Estatísticas
                    <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-sky-400 transition-all duration-300 group-hover:w-full"></span>
                </RouterLink>

                <RouterLink v-if="auth.isAdmin" :to="{ name: 'admin-dashboard' }" class="group relative flex items-center gap-2 text-sm text-slate-400 transition hover:text-white py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-slate-500 transition-colors group-hover:text-rose-400"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                    Administração
                    <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-rose-400 transition-all duration-300 group-hover:w-full"></span>
                </RouterLink>
            </div>

            <div class="hidden items-center gap-3 md:flex">
                <template v-if="auth.isAuthenticated">
                    <div class="flex items-center gap-3">
                        <div v-if="!auth.isAdmin"
                            @click="showPurchaseModal = true"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 border border-slate-800 hover:border-amber-500/50 cursor-pointer group transition-all"
                        >
                            <span class="bg-amber-500/10 p-1 rounded-full text-amber-500 group-hover:bg-amber-500/20 group-hover:scale-110 transition-all">
                                <Coins class="w-3.5 h-3.5" />
                            </span>
                            <span class="group-hover:text-amber-200 transition-colors">{{ auth.user.coins_balance }} Moedas</span>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger class="focus-visible:outline-none">
                                <div class="flex items-center gap-2 cursor-pointer">
                                    <Avatar class="h-8 w-8 border border-slate-700 ring-1 ring-emerald-500/50">
                                        <AvatarImage
                                            :src="userAvatar" />
                                        <AvatarFallback class="bg-slate-800 text-slate-300 flex items-center justify-center h-full w-full text-xs">{{ initials }}</AvatarFallback>
                                    </Avatar>
                                </div>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent align="end"
                                class="w-60 bg-slate-900 border border-slate-800/80 rounded-xl shadow-2xl p-1 text-left">
                                <div class="px-3 py-2">
                                    <p class="text-xs text-slate-400">Sessão iniciada como</p>
                                    <p class="text-sm font-medium text-slate-200 truncate">
                                        {{ auth.user.nickname || auth.user.name || 'Admin' }}
                                    </p>
                                </div>

                                <DropdownMenuSeparator class="bg-slate-700/60" />

                                <DropdownMenuItem as-child>
                                    <RouterLink to="/me"
                                        class="flex items-center gap-2 px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/60 rounded-md cursor-pointer">
                                        <User class="w-4 h-4" />Perfil
                                    </RouterLink>
                                </DropdownMenuItem>

                                <template v-if="!auth.isAdmin">
                                    <DropdownMenuItem as-child>
                                        <RouterLink to="/me/stats"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-400 hover:bg-slate-800 hover:text-slate-200 rounded-md cursor-pointer transition-colors">
                                            <BarChart3 class="w-4 h-4 text-sky-300" /> Minhas Estatísticas
                                        </RouterLink>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <RouterLink to="/me/transactions"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-400 hover:bg-slate-800 hover:text-slate-200 rounded-md cursor-pointer transition-colors">
                                            <Receipt class="w-4 h-4 text-amber-300" /> Transações
                                        </RouterLink>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <RouterLink to="/me/matches"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-400 hover:bg-slate-800 hover:text-slate-200 rounded-md cursor-pointer transition-colors">
                                            <History class="w-4 h-4 text-emerald-300" /> Histórico Multiplayer
                                        </RouterLink>
                                    </DropdownMenuItem>
                                </template>

                                <DropdownMenuSeparator class="bg-slate-700/60" />

                                <DropdownMenuItem @click="logout"
                                    class="flex items-center gap-2 px-3 py-2 text-red-500 hover:bg-red-500/10 hover:text-red-300 rounded-md cursor-pointer">
                                    <LogOut class="w-4 h-4" />Terminar sessão
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                    </div>
                </template>
                <template v-else>
                    <RouterLink to="/login" class="group relative flex items-center gap-2 text-sm text-slate-300 transition hover:text-white py-2">
                        <LogIn class="w-4 h-4 text-emerald-400" />
                        Entrar
                        <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-emerald-400 transition-all duration-300 group-hover:w-full"></span>
                    </RouterLink>
                    <RouterLink to="/register" class="group relative flex items-center gap-2 text-sm text-slate-300 transition hover:text-white py-2">
                        <User class="w-4 h-4 text-amber-400" />
                        Criar conta
                        <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-amber-400 transition-all duration-300 group-hover:w-full"></span>
                    </RouterLink>
                </template>
            </div>

            <div class="flex items-center gap-2 md:hidden">
                <template v-if="auth.isAuthenticated">
                    <RouterLink to="/me">
                        <Avatar class="h-8 w-8 border border-slate-700">
                            <AvatarImage :src="userAvatar" alt="Avatar" />
                            <AvatarFallback class="bg-slate-800 text-slate-300 flex items-center justify-center h-full w-full text-xs">
                                {{ initials }}
                            </AvatarFallback>
                        </Avatar>
                    </RouterLink>
                </template>

                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon"
                            class="h-8 w-8 text-slate-200 hover:bg-slate-900/80 rounded-xl border border-slate-800">
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right"
                        class="w-72 bg-slate-950 border-slate-800/80 shadow-[0_20px_80px_rgba(0,0,0,0.45)] px-5">
                        <DialogTitle class="sr-only">Menu de navegação</DialogTitle>
                        <DialogDescription class="sr-only">Navegar entre páginas e aceder à conta</DialogDescription>
                        <div class="mt-4 flex flex-col gap-4 text-slate-100">
                            <div class="rounded-xl border border-slate-800/70 bg-slate-900/60 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Navegar</p>
                                <div class="mt-3 flex flex-col gap-2 text-sm font-medium text-slate-200">
                                    <RouterLink to="/" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                        Início
                                    </RouterLink>
                                    <RouterLink v-if="!auth.isAdmin" to="/play" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                        <Gamepad2 class="w-4 h-4 text-emerald-300" />
                                        Jogar
                                    </RouterLink>
                                    <RouterLink to="/leaderboard" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-300"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17"/><path d="M14 14.66V17"/><path d="M18 2h-3a5 5 0 0 0-5 5v7.09a5 5 0 0 0 5 5h3"/><path d="M6 2h3a5 5 0 0 1 5 5v7.09a5 5 0 0 1-5 5H6"/></svg>
                                        Leaderboards
                                    </RouterLink>
                                    <RouterLink to="/stats" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                        <BarChart3 class="w-4 h-4 text-sky-300" />
                                        Estatísticas
                                    </RouterLink>
                                    <RouterLink v-if="auth.isAdmin" :to="{ name: 'admin-dashboard' }" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-rose-300"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                                        Administração
                                    </RouterLink>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-800/70 bg-slate-900/60 p-4">
                                <template v-if="!auth.isAuthenticated">
                                    <p class="text-xs uppercase tracking-wide text-slate-400 mb-2">Conta</p>
                                    <div class="mt-3 flex flex-col gap-2 text-sm font-medium text-slate-200">
                                        <RouterLink to="/login" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                            <LogIn class="w-4 h-4 text-emerald-400" />
                                            <span class="relative inline-flex items-center gap-1">
                                                Entrar
                                                <span class="navbar-link-underline navbar-ghost-underline"></span>
                                            </span>
                                        </RouterLink>
                                        <RouterLink to="/register" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/70">
                                            <User class="w-4 h-4 text-amber-400" />
                                            <span class="relative inline-flex items-center gap-1">
                                                Criar conta
                                                <span class="navbar-link-underline navbar-primary-underline"></span>
                                            </span>
                                        </RouterLink>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="mb-3 flex items-center gap-3">
                                        <Avatar class="h-10 w-10 border border-slate-700">
                                            <AvatarImage :src="userAvatar"
                                                alt="Avatar" />
                                            <AvatarFallback class="bg-slate-800 text-slate-300 flex items-center justify-center h-full w-full text-xs">
                                                {{ initials }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="text-xs text-slate-400">
                                            <p>Sessão iniciada como <span class="text-slate-200 font-medium">{{ auth.user?.nickname || auth.user?.name || 'Utilizador' }}</span></p>
                                            <p>{{ auth.user.email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2 text-sm font-medium text-slate-200">
                                        <RouterLink to="/me" class="flex items-center gap-2 rounded-lg px-3 py-2 bg-slate-800/70 hover:bg-slate-800">
                                            <User class="w-4 h-4" />
                                            Perfil
                                        </RouterLink>
                                        <RouterLink v-if="!auth.isAdmin" to="/me/stats" class="flex items-center gap-2 rounded-lg px-3 py-2 bg-slate-800/70 hover:bg-slate-800">
                                            <BarChart3 class="w-4 h-4 text-sky-300" />
                                            Minhas Estatísticas
                                        </RouterLink>
                                        <RouterLink v-if="!auth.isAdmin" to="/me/transactions" class="flex items-center gap-2 rounded-lg px-3 py-2 bg-slate-800/70 hover:bg-slate-800">
                                            <Receipt class="w-4 h-4 text-amber-300" />
                                            Transações
                                        </RouterLink>
                                        <RouterLink v-if="!auth.isAdmin" to="/me/matches" class="flex items-center gap-2 rounded-lg px-3 py-2 bg-slate-800/70 hover:bg-slate-800">
                                            <History class="w-4 h-4 text-emerald-300" />
                                            Histórico Multiplayer
                                        </RouterLink>
                                        <div
                                            v-if="!auth.isAdmin"
                                            @click="showPurchaseModal = true"
                                            class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-xs text-slate-300 cursor-pointer hover:bg-slate-800 hover:border-slate-700 transition-colors">
                                            <span class="inline-flex h-3 w-3 rounded-full bg-amber-400"></span>
                                            <span class="font-semibold text-amber-200">{{ auth.user.coins_balance }} Moedas</span>
                                        </div>
                                        <Button class="w-full bg-rose-600 hover:bg-rose-500 text-white"
                                            @click="logout">
                                            Terminar Sessão
                                        </Button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <!-- Global Modals (Teleported) -->
            <CoinPurchaseModal 
                :is-open="showPurchaseModal" 
                @close="showPurchaseModal = false"
                @success="handlePurchaseSuccess"
            />
        </nav>
    </header>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { computed, defineAsyncComponent, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { DialogDescription, DialogTitle } from '@/components/ui/dialog'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { useAuthStore } from '@/stores/auth'
import { ArrowRightLeft, LogOut, User, Menu, LogIn, History, BarChart3, Coins, Gamepad2, Receipt } from 'lucide-vue-next'

const CoinPurchaseModal = defineAsyncComponent(() => import('@/components/accounting/CoinPurchaseModal.vue'))

const auth = useAuthStore()
const showPurchaseModal = ref(false)
const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')

const resolveAvatar = (src) => {
    const base = apiBase || window.location.origin
    const defaultAvatar = `${base}/api/player/avatar/photos_avatars/anonymous.png`
    if (!src) {
        return defaultAvatar // Let AvatarFallback handle it
    }
    if (/^https?:\/\//i.test(src)) return src
    let normalized = src.startsWith('/') ? src.slice(1) : src
    if (!normalized.includes('/')) {
        normalized = `photos_avatars/${normalized}`
    }
    return `${base}/api/player/avatar/${normalized}`
}

const userAvatar = computed(() => {
    const path = auth.user?.photo_avatar_filename || auth.user?.avatar_url
    return resolveAvatar(path)
})

const initials = computed(() => {
    if (!auth.user?.name) return "?"
    const parts = auth.user.name.trim().split(/\s+/)
    if (parts.length === 1) return parts[0][0].toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

function logout() {
    auth.logout()
}

const handlePurchaseSuccess = (data) => {
    if (data.coins_balance !== undefined) {
        auth.user.coins_balance = data.coins_balance
    }
}
</script>
