<template>
    <DefaultLayout>
        <section class="mx-auto max-w-4xl px-4 py-10 space-y-8">
            <header class="space-y-2">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-50">Meu Perfil</h1>
                <p class="text-sm text-slate-400">
                    Consulta os teus dados e mantém a conta atualizada de acordo com o enunciado.
                </p>
            </header>

            <section
                class="rounded-2xl border border-slate-800 bg-slate-950/80 p-6 shadow-[0_18px_45px_rgba(15,23,42,0.65)] flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="flex flex-col items-center gap-4 rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-900/20 via-slate-950/80 to-slate-950/80 px-6 py-5 shadow-[0_18px_40px_rgba(16,185,129,0.18)]">
                            <Avatar class="h-24 w-24 border border-emerald-400/60 ring-4 ring-emerald-500/20 shadow-lg">
                                <AvatarImage :src="displayedAvatar" alt="Avatar atual" />
                                <AvatarFallback class="text-lg font-semibold uppercase">
                                    {{ avatarInitials }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex flex-wrap justify-center gap-2 text-xs">
                                <Button type="button"
                                    class="border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-emerald-100 hover:bg-emerald-500/20"
                                    :disabled="!isEmailVerified || savingProfile" @click="triggerAvatarSelect">
                                Alterar foto
                            </Button>
                            <Button v-if="canRemoveAvatar" type="button"
                                class="border border-slate-700 bg-transparent px-3 py-1 text-slate-200 hover:bg-slate-800/60"
                                :disabled="!isEmailVerified || savingProfile" @click="removeAvatarSelection">
                                Remover
                            </Button>
                        </div>
                        <input ref="avatarFileInput" type="file" accept="image/*" class="hidden"
                            :disabled="!isEmailVerified || savingProfile"
                            @change="handleAvatarChange" />
                            <p class="text-[11px] text-slate-500">PNG ou JPG até 5MB.</p>
                        </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-2xl font-semibold text-slate-100">
                                {{ auth.user?.name ?? '—' }}
                            </p>
                            <p class="text-sm text-slate-400">
                                {{ auth.user?.email ?? 'email não definido' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span v-if="hasNickname" class="rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1 text-slate-300">
                                @{{ displayNickname }}
                            </span>
                            <span v-if="!auth.isAdmin" class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-emerald-200">
                                {{ auth.user?.coins_balance ?? 0 }} moedas
                            </span>
                            <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-amber-200">
                                {{ isEmailVerified ? 'Email verificado' : 'Email por verificar' }}
                            </span>
                            <span class="rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1 text-slate-300">
                                ID {{ auth.user?.id ?? '—' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Membro desde {{ formatJoinedAt(auth.user?.created_at) }}
                        </p>
                    </div>
                </div>

            </section>

            <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <section class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-5">
                    <header class="space-y-1">
                        <h2 class="text-lg font-semibold text-slate-100">Dados pessoais</h2>
                        <p class="text-xs text-slate-500">Nome, nickname e email usados na plataforma.</p>
                        <div v-if="!isEmailVerified" class="flex flex-col gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-100">
                            <p class="font-semibold text-amber-100">Email por verificar</p>
                            <p class="text-amber-200">{{ verificationRequiredMessage }}</p>
                            <div class="flex flex-wrap gap-2 text-[11px]">
                                <Button type="button" size="sm" variant="outline"
                                    class="border-amber-500/60 bg-amber-500/10 text-amber-50 hover:bg-amber-500/20"
                                    :disabled="resendLoading" @click="resendVerification">
                                    {{ resendLoading ? 'A enviar...' : 'Reenviar email de verificação' }}
                                </Button>
                                <span v-if="resendMessage" class="text-amber-200">{{ resendMessage }}</span>
                            </div>
                        </div>
                    </header>

                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-200">Nome</label>
                            <Input v-model="profileForm.name" type="text" placeholder="Nome completo"
                                :disabled="!isEmailVerified || savingProfile"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-200">Nickname</label>
                            <Input v-model="profileForm.nickname" type="text" placeholder="Nickname"
                                :disabled="!isEmailVerified || savingProfile"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-200">Email</label>
                            <Input v-model="profileForm.email" type="email" autocomplete="email"
                                placeholder="email@exemplo.com"
                                :disabled="!isEmailVerified || savingProfile"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        </div>
                    </div>

                    <Button class="btn profile-submit w-full text-sm py-2.5" :disabled="!isEmailVerified || savingProfile"
                        @click="saveProfile">
                        {{ savingProfile ? 'A guardar...' : 'Guardar alterações' }}
                    </Button>
                </section>

                <section class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-5">
                    <header class="space-y-1">
                        <h2 class="text-lg font-semibold text-slate-100">Password</h2>
                        <p class="text-xs text-slate-500">Define uma nova password para a tua conta.</p>
                        <p v-if="!isEmailVerified" class="text-xs text-amber-300">
                            {{ verificationRequiredMessage }}
                        </p>
                    </header>

                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-200">Password atual</label>
                            <Input v-model="passwordForm.current_password" type="password"
                                autocomplete="current-password" placeholder="••••••"
                                :disabled="!isEmailVerified || savingPassword"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-200">Nova password</label>
                            <Input v-model="passwordForm.password" type="password" autocomplete="new-password"
                                placeholder="••••••"
                                :disabled="!isEmailVerified || savingPassword"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-200">Confirmar nova password</label>
                            <Input v-model="passwordForm.password_confirmation" type="password"
                                autocomplete="new-password" placeholder="••••••"
                                :disabled="!isEmailVerified || savingPassword"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        </div>
                    </div>

                    <Button class="btn profile-submit w-full text-sm py-2.5" :disabled="!isEmailVerified || savingPassword"
                        @click="changePassword">
                        {{ savingPassword ? 'A atualizar...' : 'Alterar password' }}
                    </Button>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 space-y-5">
                <header class="space-y-1">
                    <h2 class="text-lg font-semibold text-slate-100">Sessão e segurança</h2>
                    <p class="text-xs text-slate-500">
                        {{ auth.isAdmin ? 'Termina sessão.' : 'Termina sessão ou pede a eliminação definitiva da conta.' }}
                    </p>
                </header>

                <div
                    class="flex flex-col gap-3 rounded-xl border border-slate-800/80 bg-slate-900/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1 text-sm text-slate-300">
                        <p class="font-semibold text-slate-100">Terminar sessão</p>
                        <p class="text-xs text-slate-500">Sai da tua conta de forma rápida e segura.</p>
                    </div>
                    <Button type="button" class="btn profile-logout w-full text-sm py-2.5 sm:w-auto"
                        @click="handleLogout">
                        Terminar sessão
                    </Button>
                </div>

                <div v-if="!auth.isAdmin" class="space-y-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4">
                    <div class="space-y-2 text-sm text-amber-50">
                        <p class="font-semibold">Pedido de eliminação</p>
                        <p class="text-xs text-amber-200">
                            Pedimos um email com link seguro. Ao confirmares esse link, a tua conta e dados serão removidos e serás desconectado.
                        </p>
                    </div>
                    <Button type="button"
                        class="w-full rounded-xl border border-amber-400/70 bg-amber-500/80 px-4 py-2 text-sm font-semibold text-amber-950 hover:bg-amber-400/90 disabled:opacity-60"
                        :disabled="deleteLoading" @click="requestAccountDeletion">
                        {{ deleteLoading ? 'A enviar email de eliminação...' : 'Pedir email para eliminar conta' }}
                    </Button>
                </div>
            </section>

            <!-- ...existing code for resumo final (se necessário manter) ... -->
        </section>
    </DefaultLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { useAuthStore } from '@/stores/auth'
import { toast } from "vue-sonner"
import api from '@/services/api'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'

const auth = useAuthStore()

const profileForm = ref({
    name: '',
    nickname: '',
    email: ''
})

const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: ''
})

const savingProfile = ref(false)
const savingPassword = ref(false)
const profileSuccess = ref('')
const profileError = ref('')
const passwordSuccess = ref('')
const passwordError = ref('')
const resendLoading = ref(false)
const resendMessage = ref('')
const logoutTimer = ref(null)
const logoutPasswordTimer = ref(null)

const myStats = ref({ games_won: 0, matches_won: 0, capotes_count: 0, bandeiras_count: 0 })
const statsLoading = ref(false)


const profileId = computed(() => auth.user?.id ?? null)
const hasNickname = computed(() => {
    const nickname = auth.user?.nickname
    if (!nickname) return false
    return `${nickname}`.trim().length > 0
})
const displayNickname = computed(() => {
    const nickname = auth.user?.nickname
    if (!nickname) return '—'
    const trimmed = `${nickname}`.trim()
    return trimmed || '—'
})
const coinsBalance = computed(() => auth.user?.coins_balance ?? 0)
const isEmailVerified = computed(() => !!auth.user?.email_verified_at)
const verificationRequiredMessage = 'Verifica o teu email para poderes alterar estes dados.'
const formatJoinedAt = (dateString) => {
    if (!dateString) return '—'
    const parsed = new Date(dateString)
    if (Number.isNaN(parsed.getTime())) return '—'
    return parsed.toLocaleDateString('pt-PT', { year: 'numeric', month: 'short', day: 'numeric' })
}

const apiBaseAvatar = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')
const defaultAvatar = `${apiBaseAvatar || window.location.origin}/api/player/avatar/photos_avatars/anonymous.png`
const avatarFileInput = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref('')
const lastSavedAvatarUrl = ref('')
const previewIsObjectUrl = ref(false)
const removeAvatar = ref(false)

const displayedAvatar = computed(() => avatarPreview.value || lastSavedAvatarUrl.value || defaultAvatar)
const canRemoveAvatar = computed(() => !!avatarPreview.value)

const avatarInitials = computed(() => {
    if (!auth.user?.name) return '??'
    return auth.user.name
        .split(' ')
        .filter(Boolean)
        .map(part => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
})

function formatAvatarPath(src) {
    const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
        .replace(/\/+$/, '')
        .replace(/\/api$/, '')
    const base = apiBase || window.location.origin
    const defaultAvatar = `${base}/api/player/avatar/photos_avatars/anonymous.png`
    if (!src) {
        return defaultAvatar
    }
    if (/^https?:\/\//i.test(src)) {
        return src
    }
    let normalized = src.startsWith('/') ? src.slice(1) : src
    if (!normalized.includes('/')) {
        normalized = `photos_avatars/${normalized}`
    }
    return `${base}/api/player/avatar/${normalized}`
}

function clearObjectUrlIfNeeded() {
    if (previewIsObjectUrl.value && avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value)
    }
    previewIsObjectUrl.value = false
}

function setAvatarPreview(value, isObjectUrl = false) {
    if (value === avatarPreview.value) {
        previewIsObjectUrl.value = isObjectUrl
        return
    }
    clearObjectUrlIfNeeded()
    avatarPreview.value = value
    previewIsObjectUrl.value = isObjectUrl
}

function setAvatarFromPayload(filename) {
    const resolved = formatAvatarPath(filename)
    const cacheBust = resolved ? `${resolved}?v=${Date.now()}` : ''
    lastSavedAvatarUrl.value = cacheBust
    avatarFile.value = null
    removeAvatar.value = false
    setAvatarPreview(cacheBust, false)
}

function resetAvatarSelectionToSaved() {
    avatarFile.value = null
    removeAvatar.value = false
    setAvatarPreview(lastSavedAvatarUrl.value, false)
    if (avatarFileInput.value) {
        avatarFileInput.value.value = ''
    }
}

function handleAvatarChange(event) {
    if (!ensureProfileUpdateAllowed()) {
        event.target.value = ''
        return
    }

    const file = event.target.files?.[0]
    if (!file) {
        return
    }

    avatarFile.value = file
    removeAvatar.value = false
    saveProfile({ avatarOnly: true })
}

async function resendVerification() {
    if (resendLoading.value) return
    resendMessage.value = ''
    resendLoading.value = true
    try {
        const { data } = await api.post('/email/resend')
        resendMessage.value = data?.message || 'Email de verificação enviado.'
        toast.success(resendMessage.value)
    } catch (error) {
        const message = error?.response?.data?.message || 'Não foi possível enviar o email de verificação.'
        resendMessage.value = message
        toast.error(message)
    } finally {
        resendLoading.value = false
    }
}

function triggerAvatarSelect() {
    if (!ensureProfileUpdateAllowed()) {
        return
    }
    avatarFileInput.value?.click()
}

function removeAvatarSelection() {
    if (!ensureProfileUpdateAllowed()) {
        return
    }
    avatarFile.value = null
    removeAvatar.value = true
    setAvatarPreview('', false)
    saveProfile({ avatarOnly: true })
}

const deleteLoading = ref(false)
const deleteSuccess = ref('')
const deleteError = ref('')

function ensureProfileUpdateAllowed() {
    if (!isEmailVerified.value) {
        profileError.value = verificationRequiredMessage
        toast.error(profileError.value)
        return false
    }
    return true
}

function ensurePasswordUpdateAllowed() {
    if (!isEmailVerified.value) {
        passwordError.value = verificationRequiredMessage
        toast.error(passwordError.value)
        return false
    }
    return true
}

async function loadProfile() {
    profileError.value = ''
    if (!profileId.value) {
        profileError.value = 'Não foi possível identificar o utilizador atual.'
        return
    }
    try {
        const { data } = await api.get(`/player/profile/${profileId.value}`)
        const payload = data?.user ?? data
        profileForm.value = {
            name: payload?.name ?? '',
            nickname: payload?.nickname ?? '',
            email: payload?.email ?? ''
        }
        if (payload) {
            auth.user = { ...(auth.user ?? {}), ...payload }
            setAvatarFromPayload(payload?.photo_avatar_filename ?? '')
        }
    } catch (error) {
        profileError.value =
            error.response?.data?.message ??
            'Não foi possível carregar os teus dados. Tenta novamente mais tarde.'
    }
}

async function saveProfile(options = {}) {
    const { avatarOnly = false } = options
    if (!avatarOnly) {
        profileSuccess.value = ''
        profileError.value = ''
    }
    if (!ensureProfileUpdateAllowed()) {
        return
    }
    savingProfile.value = true
    if (!profileId.value) {
        profileError.value = 'Não foi possível identificar o utilizador atual.'
        savingProfile.value = false
        return
    }
    try {
        const previousEmail = auth.user?.email
        const formData = new FormData()
        if (!avatarOnly) {
            formData.append('name', profileForm.value.name ?? '')
            formData.append('nickname', profileForm.value.nickname ?? '')
            formData.append('email', profileForm.value.email ?? '')
        }

        if (avatarFile.value) {
            formData.append('photo', avatarFile.value)
        } else if (removeAvatar.value) {
            formData.append('remove_avatar', '1')
        }

        const { data } = await api.put(
            `/player/profile/${profileId.value}`,
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' }
            }
        )
        const payload = data.user ?? data
        if (!avatarOnly && data?.message) {
            profileSuccess.value = data.message
            toast.success(profileSuccess.value)
        }
        if (payload) {
            auth.user = { ...(auth.user ?? {}), ...payload }
            if (!avatarOnly) {
                profileForm.value = {
                    name: payload?.name ?? '',
                    nickname: payload?.nickname ?? '',
                    email: payload?.email ?? ''
                }
            }
            setAvatarFromPayload(payload?.photo_avatar_filename ?? '')
        }
        if (!avatarOnly && previousEmail && payload?.email && previousEmail !== payload.email) {
            startLogoutCountdown()
        }
        if (avatarOnly) {
            const avatarMessage = data?.message
            if (avatarMessage) {
                toast.success(avatarMessage)
            }
        }
    } catch (error) {
        const apiMessage = error.response?.data?.message
        const message =
            apiMessage ??
            'Não foi possível guardar as alterações. Verifica os dados inseridos.'
        if (!avatarOnly) {
            const apiErrors = error.response?.data?.errors || {}
            const firstError = Object.values(apiErrors).flat()?.[0]
            profileError.value = firstError || message
            toast.error(profileError.value)
        } else {
            if (apiMessage) {
                toast.error(apiMessage)
            }
            resetAvatarSelectionToSaved()
        }
    } finally {
        savingProfile.value = false
    }
}

function startLogoutCountdown() {
    clearLogoutCountdown()
    logoutTimer.value = setTimeout(() => {
        auth.logout()
    }, 5000)
    toast.info('Email atualizado. Será terminada a sessão em 5 segundos.')
}

function clearLogoutCountdown() {
    if (logoutTimer.value) {
        clearTimeout(logoutTimer.value)
        logoutTimer.value = null
    }
}

function startLogoutPasswordCountdown() {
    if (logoutPasswordTimer.value) {
        clearTimeout(logoutPasswordTimer.value)
    }
    logoutPasswordTimer.value = setTimeout(() => {
        auth.logout()
    }, 3000)
}

function clearLogoutPasswordCountdown() {
    if (logoutPasswordTimer.value) {
        clearTimeout(logoutPasswordTimer.value)
        logoutPasswordTimer.value = null
    }
}

async function changePassword() {
    if (!ensurePasswordUpdateAllowed()) {
        return
    }
    savingPassword.value = true
    if (!profileId.value) {
        passwordError.value = 'Não foi possível identificar o utilizador atual.'
        toast.error(passwordError.value)
        savingPassword.value = false
        return
    }
    try {
        const { data } = await api.patch(`/player/profile/${profileId.value}/password`, {
            current_password: passwordForm.value.current_password,
            password: passwordForm.value.password,
            password_confirmation: passwordForm.value.password_confirmation
        })
        const message = data?.message
        if (message) {
            toast.success(message)
        }
        auth.logout()
        passwordForm.value = {
            current_password: '',
            password: '',
            password_confirmation: ''
        }
    } catch (error) {
        const apiErrors = error.response?.data?.errors || {}
        const firstError = Object.values(apiErrors).flat()?.[0]
        passwordError.value =
            firstError ||
            error.response?.data?.message ||
            'Não foi possível alterar a password. Confirma os dados introduzidos.'
        if (passwordError.value) {
            toast.error(passwordError.value)
        }
    } finally {
        savingPassword.value = false
    }
}

async function requestAccountDeletion() {
    if (!profileId.value) {
        deleteError.value = 'Não foi possível identificar o utilizador atual.'
        toast.error(deleteError.value)
        return
    }

    try {
        deleteLoading.value = true
        const { data } = await api.post(`/player/profile/${profileId.value}/delete-request`)
        deleteSuccess.value =
            data?.message ||
            'Enviámos um email com o link para confirmares a eliminação. Todas as moedas serão perdidas após confirmação.'
        if (deleteSuccess.value) {
            toast.success(deleteSuccess.value)
        }
        const delay = (data?.logout_in_seconds ?? 3) * 1000
        setTimeout(() => {
            auth.logout()
        }, delay)
    } catch (error) {
        deleteError.value =
            error.response?.data?.message ||
            'Não foi possível iniciar o processo de eliminação. Tenta novamente.'
        if (deleteError.value) {
            toast.error(deleteError.value)
        }
    } finally {
        deleteLoading.value = false
    }
}

function handleLogout() {
    auth.logout()
}

onMounted(() => {
    if (auth.isAuthenticated) {
        loadProfile()
        setAvatarFromPayload(auth.user?.photo_avatar_filename ?? '')
    }
})

onBeforeUnmount(() => {
    clearObjectUrlIfNeeded()
    clearLogoutCountdown()
    clearLogoutPasswordCountdown()
})
</script>
