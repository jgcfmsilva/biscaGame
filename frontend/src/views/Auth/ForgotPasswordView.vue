<template>
    <DefaultLayout>
        <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md items-center px-4 py-10">
            <div class="w-full rounded-2xl border border-slate-800 bg-slate-950/80 p-6 shadow-[0_18px_45px_rgba(15,23,42,0.8)]">
                <div class="mb-6 space-y-2 text-center">
                    <h1 class="text-xl font-semibold text-slate-50">Recuperar password</h1>
                    <p class="text-xs text-slate-400">
                        Introduz o teu email para receber o link de redefinição.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Email</label>
                        <Input v-model="email" type="email" placeholder="email@exemplo.com" autocomplete="email"
                            class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                    </div>

                    <Button type="submit" class="btn btn-primary w-full text-sm py-2.5" :disabled="loading">
                        {{ loading ? 'A enviar...' : 'Enviar link' }}
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
import { RouterLink } from 'vue-router'
import { ref } from 'vue'
import api from '@/services/api'
import { toast } from 'vue-sonner'

const email = ref('')
const loading = ref(false)
const errors = ref({ email: '' })

async function submit() {
    errors.value = { email: '' }

    try {
        loading.value = true
        const { data } = await api.post('/password/email', { email: email.value })
        if (data?.message) {
            toast.success(data.message)
        }
    } catch (err) {
        const apiErrors = err?.response?.data?.errors || {}
        errors.value.email = Array.isArray(apiErrors.email) ? apiErrors.email[0] : ''
        const message = err?.response?.data?.message
        if (message) {
            toast.error(message)
        }
    } finally {
        loading.value = false
    }
}
</script>
