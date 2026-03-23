<template>
    <DefaultLayout>
        <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md items-center px-4 py-10">
            <div class="w-full rounded-2xl border border-slate-800 bg-slate-950/80 p-6 shadow-[0_18px_45px_rgba(15,23,42,0.8)]">
                <div class="mb-6 space-y-2 text-center">
                    <h1 class="text-xl font-semibold text-slate-50">Redefinir password</h1>
                </div>

                <div v-if="verifying" class="text-center text-sm text-slate-300">A validar o link...</div>
                <div v-else-if="!tokenValid" class="text-center text-sm text-rose-300">
                    {{ errorMessage || 'Token inválido ou expirado. Vais ser redirecionado para o login.' }}
                </div>

                <form v-else class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Password</label>
                        <div class="relative">
                            <Input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="••••••"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                @click="showPassword = !showPassword">
                                <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Confirmar password</label>
                        <div class="relative">
                            <Input v-model="passwordConfirmation" :type="showPasswordConfirm ? 'text' : 'password'" autocomplete="new-password" placeholder="••••••"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                @click="showPasswordConfirm = !showPasswordConfirm">
                                <component :is="showPasswordConfirm ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <Button type="submit" class="btn btn-primary w-full text-sm py-2.5" :disabled="loading">
                        {{ loading ? 'A atualizar...' : 'Redefinir password' }}
                    </Button>
                </form>

                <div class="mt-6 text-center text-xs text-slate-300">
                    <RouterLink to="/login" class="font-medium text-brand-400 hover:text-brand-300 underline underline-offset-2">
                        Voltar ao login
                    </RouterLink>
                </div>
            </div>
        </section>
    </DefaultLayout>
</template>

<script setup>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import { toast } from 'vue-sonner'
import { Eye, EyeOff } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const token = ref(route.query.token ?? '')
const email = ref(route.query.email ?? '')

const verifying = ref(true)
const tokenValid = ref(false)
const errorMessage = ref('')

const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const loading = ref(false)
const errors = ref({ password: '', password_confirmation: '' })

async function verifyToken() {
    if (!token.value || !email.value) {
        errorMessage.value = 'Link inválido ou incompleto.'
        verifying.value = false
        toast.error(errorMessage.value)
        setTimeout(() => router.push('/login'), 3000)
        return
    }

    try {
        const { data } = await api.get('/password/reset/verify', {
            params: { token: token.value, email: email.value }
        })
        tokenValid.value = data?.success !== false
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || 'Token inválido ou expirado.'
        tokenValid.value = false
        if (errorMessage.value) {
            toast.error(errorMessage.value)
        }
        setTimeout(() => router.push('/login'), 3000)
    } finally {
        verifying.value = false
    }
}

async function submit() {
    errors.value = { password: '', password_confirmation: '' }

    try {
        loading.value = true
        const { data } = await api.post('/password/reset', {
            token: token.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value
        })
        const message = data?.message ?? ''
        if (message) {
            toast.success(message)
        }
        setTimeout(() => router.push('/login'), 1200)
    } catch (err) {
        const apiErrors = err?.response?.data?.errors || {}
        errors.value.password = Array.isArray(apiErrors.password) ? apiErrors.password[0] : ''
        errors.value.password_confirmation = Array.isArray(apiErrors.password_confirmation)
            ? apiErrors.password_confirmation[0]
            : ''
        const apiMessage = err?.response?.data?.message
        if (apiMessage) {
            toast.error(apiMessage)
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    verifyToken()
})
</script>
