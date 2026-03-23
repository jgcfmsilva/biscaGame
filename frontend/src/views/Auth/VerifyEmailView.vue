<template>
  <DefaultLayout>
    <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-xl items-center px-4 py-12">
      <div class="w-full rounded-2xl border border-slate-800 bg-slate-950/80 p-8 shadow-[0_18px_45px_rgba(15,23,42,0.8)]">
        <div class="mb-6 flex items-start gap-3">
          <component
            :is="statusIcon"
            class="mt-1 h-8 w-8 flex-shrink-0"
            :class="statusColor"
          />
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">
              Confirmação de email
            </p>
            <h1 class="text-2xl font-semibold text-slate-50">
              {{ heading }}
            </h1>
            <p class="text-sm text-slate-300">
              {{ description }}
            </p>
          </div>
        </div>

        <div class="rounded-xl border border-slate-800/80 bg-slate-900/70 p-5">
          <div v-if="status === 'loading'" class="flex items-center gap-3 text-slate-200">
            <Loader2 class="h-5 w-5 animate-spin text-brand-300" />
            <span>A confirmar o teu email...</span>
          </div>

          <div v-else class="space-y-2 text-slate-200">
            <p class="text-base font-medium" :class="status === 'error' ? 'text-rose-200' : 'text-emerald-200'">
              {{ apiMessage }}
            </p>
            <p class="text-sm text-slate-400">
              {{ helperMessage }}
            </p>
          </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <Button type="button" class="btn btn-primary px-4" @click="goToLogin">
            Ir para login
          </Button>
          <Button type="button" variant="outline" class="border-slate-800 bg-slate-900/60 text-slate-200" @click="goHome">
            Voltar à página inicial
          </Button>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import api from '@/services/api'
import { Button } from '@/components/ui/button'
import { AlertTriangle, CheckCircle2, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const status = ref('loading')
const apiMessage = ref('A confirmar o teu email...')
const redirectTimer = ref(null)
const redirectDelaySeconds = 3

const statusIcon = computed(() => {
  if (status.value === 'success') return CheckCircle2
  if (status.value === 'error') return AlertTriangle
  return Loader2
})

const statusColor = computed(() => {
  if (status.value === 'success') return 'text-emerald-300'
  if (status.value === 'error') return 'text-rose-300'
  return 'text-brand-300 animate-spin'
})

const heading = computed(() => {
  if (status.value === 'success') return 'Email verificado'
  if (status.value === 'error') return 'Não foi possível validar'
  return 'A verificar email'
})

const description = computed(() => {
  if (status.value === 'success') {
    return 'A tua conta ficou confirmada. Já podes iniciar sessão e jogar.'
  }
  if (status.value === 'error') {
    return 'O link pode estar expirado ou já ter sido utilizado.'
  }
  return 'Estamos a confirmar a validade do token enviado para o teu email.'
})

const helperMessage = computed(() => {
  if (status.value === 'error') {
    return `Link inválido ou expirado. Vais ser redirecionado para o login em ${redirectDelaySeconds} segundos.`
  }
  return `Vais ser redirecionado para o login em ${redirectDelaySeconds} segundos.`
})

function extractTokenParts() {
  const id = route.params.id || route.query.id
  const hash = route.params.hash || route.query.hash
  const expires = route.query.expires
  const signature = route.query.signature

  return { id, hash, expires, signature }
}

async function verifyEmail() {
  const { id, hash, expires, signature } = extractTokenParts()

  if (!id || !hash || !signature || !expires) {
    status.value = 'error'
    apiMessage.value = 'Ligação incompleta. Gera um novo email de verificação.'
    startRedirectCountdown()
    return
  }

  try {
    const { data } = await api.get(`/email/verify/${id}/${hash}`, {
      params: { expires, signature },
    })

    status.value = 'success'
    apiMessage.value = data?.message || 'Email verificado com sucesso.'
    if (auth.user && String(auth.user.id) === String(id)) {
      auth.user = { ...auth.user, email_verified_at: new Date().toISOString() }
    }
    startRedirectCountdown()
  } catch (error) {
    status.value = 'error'
    apiMessage.value =
      error?.response?.data?.message ||
      'Não foi possível confirmar o email. O link pode estar inválido.'
    startRedirectCountdown()
  }
}

function goToLogin() {
  router.push({ name: 'login' })
}

function goHome() {
  router.push({ name: 'main' })
}

function startRedirectCountdown() {
  clearRedirect()
  redirectTimer.value = setTimeout(() => {
    goToLogin()
  }, redirectDelaySeconds * 1000)
}

function clearRedirect() {
  if (redirectTimer.value) {
    clearTimeout(redirectTimer.value)
    redirectTimer.value = null
  }
}

onMounted(() => {
  verifyEmail()
})

onUnmounted(() => {
  clearRedirect()
})
</script>
