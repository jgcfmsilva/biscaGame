<template>
  <DefaultLayout>
    <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-lg items-center px-4 py-12">
      <div class="w-full rounded-2xl border border-slate-800 bg-slate-950/80 p-8 shadow-[0_18px_45px_rgba(15,23,42,0.8)]">
        <div class="mb-6 flex items-start gap-3">
          <component :is="statusIcon" class="mt-1 h-8 w-8 flex-shrink-0" :class="statusColor" />
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Eliminar conta</p>
            <h1 class="text-2xl font-semibold text-slate-50">
              {{ heading }}
            </h1>
            <p class="text-sm text-slate-300">
              {{ description }}
            </p>
          </div>
        </div>

        <div class="rounded-xl border border-slate-800/80 bg-slate-900/70 p-5 space-y-4">
          <div v-if="status === 'loading'" class="flex items-center gap-3 text-slate-200">
            <Loader2 class="h-5 w-5 animate-spin text-brand-300" />
            <span>A validar o link de eliminação...</span>
          </div>

          <div v-else-if="status === 'error'" class="space-y-2 text-slate-200">
            <p class="text-base font-medium text-rose-200">
              {{ message }}
            </p>
            <p class="text-sm text-slate-400">
              O link pode ter expirado. Pede novo pedido de eliminação.
            </p>
          </div>

          <form v-else class="space-y-4" @submit.prevent="submitDeletion">
            <div class="space-y-1">
              <label class="text-xs font-medium text-slate-200">Password atual</label>
              <Input
                v-model="password"
                type="password"
                placeholder="••••••"
                autocomplete="current-password"
                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500"
                :disabled="submitting"
              />
              <p v-if="formError" class="text-xs text-rose-400">{{ formError }}</p>
            </div>
            <Button type="submit" class="btn btn-danger w-full text-sm py-2.5" :disabled="submitting">
              {{ submitting ? 'A eliminar...' : 'Eliminar conta definitivamente' }}
            </Button>
            <p v-if="message && !formError" class="text-xs text-amber-200">{{ message }}</p>
          </form>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import api from '@/services/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AlertTriangle, CheckCircle2, Loader2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const status = ref('loading')
const message = ref('')
const submitting = ref(false)
const password = ref('')
const formError = ref('')

const statusIcon = computed(() => {
  if (status.value === 'ready') return CheckCircle2
  if (status.value === 'error') return AlertTriangle
  return Loader2
})

const statusColor = computed(() => {
  if (status.value === 'ready') return 'text-emerald-300'
  if (status.value === 'error') return 'text-rose-300'
  return 'text-brand-300 animate-spin'
})

const heading = computed(() => {
  if (status.value === 'ready') return 'Confirmar eliminação'
  if (status.value === 'error') return 'Link inválido ou expirado'
  return 'A validar link'
})

const description = computed(() => {
  if (status.value === 'ready') {
    return 'Insere a tua password para confirmar a eliminação definitiva da conta.'
  }
  if (status.value === 'error') {
    return 'O pedido não é válido. Pede novo email de confirmação.'
  }
  return 'Estamos a verificar a validade do link de eliminação.'
})

function tokenParams() {
  const id = route.params.id || route.query.id
  const expires = route.query.expires
  const signature = route.query.signature
  return { id, expires, signature }
}

async function validateLink() {
  const { id, expires, signature } = tokenParams()
  if (!id || !expires || !signature) {
    status.value = 'error'
    message.value = 'Link incompleto. Pede novo pedido de eliminação.'
    return
  }

  try {
    const { data } = await api.get(`/player/profile/${id}/delete-confirm`, {
      params: { expires, signature },
    })
    message.value = data?.message || 'Link validado. Podes confirmar a eliminação.'
    status.value = 'ready'
  } catch (error) {
    status.value = 'error'
    message.value = error?.response?.data?.message || 'Não foi possível validar o link.'
  }
}

async function submitDeletion() {
  const { id, expires, signature } = tokenParams()
  if (!id || !expires || !signature) {
    message.value = 'Link incompleto. Pede novo pedido de eliminação.'
    status.value = 'error'
    return
  }
  submitting.value = true
  message.value = ''
  formError.value = ''
  try {
    const { data } = await api.post(
      `/player/profile/${id}/delete-confirm`,
      { password: password.value },
      { params: { expires, signature } }
    )
    message.value = data?.message || 'Conta eliminada com sucesso.'
    toast.success(message.value)
    auth.logout()
    setTimeout(() => {
      router.push({ name: 'login' })
    }, 2000)
  } catch (error) {
    const apiErrors = error?.response?.data?.errors || {}
    const firstError = Object.values(apiErrors).flat()?.[0]
    formError.value = firstError || error?.response?.data?.message || 'Não foi possível eliminar a conta.'
    message.value = formError.value
    toast.error(formError.value)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  validateLink()
})
</script>
