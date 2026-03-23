<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-10 space-y-8">
            <header class="space-y-4 text-center md:text-left">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-50">Leaderboards</h1>
                <p class="text-sm text-slate-400 max-w-2xl">
                    Descobre quem são os mestres da Bisca. Consulta os rankings de utilizadores com mais vitórias em jogos e partidas.
                </p>
            </header>

            <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-4">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"></div>
                <p class="text-sm text-slate-500 animate-pulse">A carregar rankings...</p>
            </div>

            <div v-else-if="error" class="rounded-xl border border-rose-900/50 bg-rose-950/30 p-8 text-center">
                <p class="text-rose-200 mb-4">{{ error }}</p>
                <Button @click="fetchLeaderboard" variant="outline" class="border-rose-800 text-rose-100 hover:bg-rose-900/50">
                    Tentar Novamente
                </Button>
            </div>

            <div v-else class="grid gap-8 md:grid-cols-2">
                <!-- Most Games Won -->
                <section class="space-y-4">
                    <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                                <Trophy class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-100">Top Jogos Vencidos</h2>
                                <p class="text-xs text-slate-500">Jogadores com mais vitórias em standalone games</p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div v-if="mostGamesWon.length === 0" class="text-center py-8 text-slate-500 text-sm">
                                Sem dados disponíveis.
                            </div>
                            <div v-for="(user, index) in mostGamesWon" :key="user.id"
                                class="flex items-center justify-between p-3 rounded-xl transition border border-transparent"
                                :class="[
                                    {'bg-gradient-to-r from-emerald-950/30 to-transparent border-emerald-900/30': index < 3},
                                    isSelf(user) ? 'cursor-default' : 'hover:bg-slate-900/50 hover:border-slate-800 cursor-pointer'
                                ]"
                                @click="openUser(user)">
                                <div class="flex items-center gap-4">
                                    <span class="flex h-6 w-6 items-center justify-center text-sm font-bold"
                                        :class="getRankClass(index)">
                                        {{ index + 1 }}
                                    </span>
                                    <div class="flex items-center gap-3">
                                         <Avatar class="h-8 w-8 border border-slate-700">
                                            <AvatarImage :src="formatAvatarPath(user.photo_avatar_filename)" />
                                            <AvatarFallback class="text-[10px]">{{ getInitials(user.nickname) }}</AvatarFallback>
                                        </Avatar>
                                        <span class="text-sm font-medium text-slate-200">{{ user.nickname }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-lg font-bold text-slate-100">{{ user.games_won_count }}</span>
                                    <span class="text-[10px] uppercase text-slate-500 font-medium">vitórias</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Most Matches Won -->
                <section class="space-y-4">
                     <div class="rounded-2xl border border-emerald-700/30 bg-slate-950/80 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 rounded-lg bg-amber-500/10 text-amber-400">
                                <Medal class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-100">Top Partidas Vencidas</h2>
                                <p class="text-xs text-slate-500">Jogadores com mais vitórias em matches completos</p>
                            </div>
                        </div>

                         <div class="space-y-1">
                            <div v-if="mostMatchesWon.length === 0" class="text-center py-8 text-slate-500 text-sm">
                                Sem dados disponíveis.
                            </div>
                            <div v-for="(user, index) in mostMatchesWon" :key="user.id"
                                class="flex items-center justify-between p-3 rounded-xl transition border border-transparent"
                                :class="[
                                    {'bg-gradient-to-r from-amber-950/30 to-transparent border-amber-900/30': index < 3},
                                    isSelf(user) ? 'cursor-default' : 'hover:bg-slate-900/50 hover:border-slate-800 cursor-pointer'
                                ]"
                                @click="openUser(user)">
                                <div class="flex items-center gap-4">
                                     <span class="flex h-6 w-6 items-center justify-center text-sm font-bold"
                                        :class="getRankClass(index)">
                                        {{ index + 1 }}
                                    </span>
                                    <div class="flex items-center gap-3">
                                         <Avatar class="h-8 w-8 border border-slate-700">
                                            <AvatarImage :src="formatAvatarPath(user.photo_avatar_filename)" />
                                            <AvatarFallback class="text-[10px]">{{ getInitials(user.nickname) }}</AvatarFallback>
                                        </Avatar>
                                        <span class="text-sm font-medium text-slate-200">{{ user.nickname }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-lg font-bold text-slate-100">{{ user.matches_won_count }}</span>
                                    <span class="text-[10px] uppercase text-slate-500 font-medium">vitórias</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- User modal -->
            <Teleport to="body">
                <div
                    v-if="selectedUser"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
                    @click.self="closeUserModal"
                >
                    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-950 to-slate-900/90 shadow-[0_20px_80px_rgba(0,0,0,0.55)] p-6 relative overflow-hidden">
                        <div class="absolute inset-0 opacity-60"
                            style="background: radial-gradient(circle at 20% 20%, rgba(16,185,129,0.15), transparent 35%), radial-gradient(circle at 80% 0%, rgba(234,179,8,0.12), transparent 30%);">
                        </div>
                        <div class="relative flex items-start gap-4">
                            <Avatar class="h-16 w-16 md:h-20 md:w-20 border-2 border-emerald-500/40 shadow-lg shadow-emerald-900/30">
                                <AvatarImage :src="selectedUserAvatar" />
                                <AvatarFallback class="text-base">{{ getInitials(selectedUser.nickname) }}</AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <p class="text-xl font-semibold text-slate-50">{{ selectedUser.nickname }}</p>
                                <p class="text-xs text-slate-400 mt-1">Membro desde {{ formatMemberSince(selectedUser.created_at) }}</p>
                            </div>
                            <Button v-if="auth.isAdmin" size="icon" variant="ghost" class="text-slate-300 hover:text-white hover:bg-emerald-500/10" @click="goToUserAdmin">
                                <Shield class="h-5 w-5" />
                            </Button>
                        </div>
                        <div v-if="auth.isAdmin" class="relative mt-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-100">
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Nome</span>
                                    <span class="font-medium text-right">{{ selectedUser?.name ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Nickname</span>
                                    <span class="font-medium text-right">{{ selectedUser?.nickname ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Email</span>
                                    <span class="font-medium text-right break-all max-w-[180px]">{{ selectedUser?.email ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Tipo</span>
                                    <span class="font-medium text-right">{{ selectedUser?.type === 'A' ? 'Admin' : 'Jogador' }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Estado</span>
                                    <span class="font-medium text-right">
                                        <span v-if="selectedUser?.deleted_at" class="text-slate-400">Eliminado</span>
                                        <span v-else-if="selectedUser?.blocked" class="text-rose-300">Bloqueado</span>
                                        <span v-else class="text-emerald-300">Ativo</span>
                                    </span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Coins</span>
                                    <span class="font-medium text-right">{{ selectedUser?.coins_balance ?? 0 }} 🪙</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Criado em</span>
                                    <span class="font-medium text-right">{{ formatMemberSince(selectedUser?.created_at) }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-400">Atualizado em</span>
                                    <span class="font-medium text-right">{{ formatMemberSince(selectedUser?.updated_at) }}</span>
                                </div>
                            </div>
                            <p v-if="selectedUser?.deleted_at" class="text-xs text-slate-400">Utilizador eliminado.</p>
                        </div>
                        <div class="relative mt-6 flex justify-end gap-2">
                            <Button variant="ghost" class="text-slate-300 hover:text-white" @click="closeUserModal">Fechar</Button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { ref, computed, onMounted, Teleport } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Trophy, Medal, Shield } from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { toast } from 'vue-sonner'

const loading = ref(true)
const error = ref(null)
const mostGamesWon = ref([])
const mostMatchesWon = ref([])
const selectedUser = ref(null)
const loadingUser = ref(false)
const auth = useAuthStore()
const router = useRouter()

const fetchLeaderboard = async () => {
    loading.value = true
    error.value = null
    try {
        const { data } = await api.get('/leaderboard/global')
        mostGamesWon.value = data.most_games_won
        mostMatchesWon.value = data.most_matches_won
    } catch (err) {
        error.value = 'Não foi possível carregar os rankings. Por favor tenta novamente.'
        console.error(err)
    } finally {
        loading.value = false
    }
}

function getRankClass(index) {
    if (index === 0) return 'text-amber-400'
    if (index === 1) return 'text-slate-300'
    if (index === 2) return 'text-amber-700'
    return 'text-slate-500'
}

function getInitials(nickname) {
    if (!nickname) return '??'
    return nickname.substring(0, 2).toUpperCase()
}

function formatAvatarPath(filename) {
     const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
        .replace(/\/+$/, '')
        .replace(/\/api$/, '')
    const base = apiBase || window.location.origin
    const defaultAvatar = `${base}/api/player/avatar/photos_avatars/anonymous.png`

    if (!filename) {
        return defaultAvatar
    }
    if (/^https?:\/\//i.test(filename)) {
        return filename
    }
    let normalized = filename.startsWith('/') ? filename.slice(1) : filename
    if (!normalized.includes('/')) {
        normalized = `photos_avatars/${normalized}`
    }
    return `${base}/api/player/avatar/${normalized}`
}

const selectedUserAvatar = computed(() =>
    formatAvatarPath(selectedUser.value?.photo_avatar_filename || selectedUser.value?.avatar)
)

function openUser(user) {
    if (!user || isSelf(user)) return
    // Set the selected user immediately so the modal shows the right person before the fetch finishes
    selectedUser.value = {
        ...user,
        avatar: user.photo_avatar_filename ?? user.avatar ?? null,
        photo_avatar_filename: user.photo_avatar_filename ?? user.avatar ?? null
    }
    fetchUser(user.id)
}

function closeUserModal() {
    selectedUser.value = null
}

function isSelf(user) {
    return auth?.user?.id && user?.id === auth.user.id
}

function formatMemberSince(date) {
    if (!date) return '—'
    const parsed = new Date(date)
    if (Number.isNaN(parsed.getTime())) return '—'
    return parsed.toLocaleDateString('pt-PT', { year: 'numeric', month: 'long' })
}

function goToUserAdmin() {
    if (!selectedUser.value?.id) return
    router.push({ name: 'admin-users', query: { userId: selectedUser.value.id } })
    closeUserModal()
}

async function fetchUser(id) {
    loadingUser.value = true
    try {
        if (auth.isAdmin) {
            const { data } = await api.get(`/admin/users/${id}`)
            selectedUser.value = {
                ...selectedUser.value,
                ...data,
                avatar: data.photo_avatar_filename ?? data.avatar ?? selectedUser.value?.avatar ?? null,
                photo_avatar_filename: data.photo_avatar_filename ?? data.avatar ?? selectedUser.value?.photo_avatar_filename ?? null
            }
        } else {
            const { data } = await api.get(`/player/profile/public/${id}`)
            selectedUser.value = {
                ...selectedUser.value,
                ...data,
                avatar: data.photo_avatar_filename ?? data.avatar ?? selectedUser.value?.avatar ?? null,
                photo_avatar_filename: data.photo_avatar_filename ?? data.avatar ?? selectedUser.value?.photo_avatar_filename ?? null
            }
        }
    } catch (err) {
        console.error(err)
        toast.error('Não foi possível carregar o perfil.')
    } finally {
        loadingUser.value = false
    }
}

onMounted(() => {
    fetchLeaderboard()
})
</script>
