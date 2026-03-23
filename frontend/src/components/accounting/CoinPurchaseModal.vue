<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
      <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-xl shadow-2xl p-6 relative">
        <!-- Header -->
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-white">Comprar Moedas</h2>
          <p class="text-sm text-slate-400 mt-1">1€ = 10 moedas. Investe na tua banca.</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handlePurchase" class="space-y-5">
          
          <!-- Valor -->
          <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Valor em Euros (€)</label>
          <div class="relative">
             <input 
               v-model.number="form.value" 
               type="number"
               inputmode="decimal"
               step="1"
               min="1" 
               max="99" 
               class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 pr-16 py-2 text-white focus:border-emerald-500 focus:outline-none placeholder-slate-600 no-spin"
               placeholder="Ex: 5"
             />
             <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[13px] text-slate-300 bg-slate-800/70 border border-slate-700/70 rounded-md px-2 py-1 pointer-events-none">
               EUR
             </div>
          </div>
          <p class="text-xs text-emerald-400 mt-1" v-if="form.value && form.value > 0">
               Vais receber <span class="font-bold">{{ form.value * 10 }}</span> moedas
            </p>
          </div>

          <!-- Tipo de Pagamento -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Método de Pagamento</label>
            <div class="grid grid-cols-2 gap-2">
              <button 
                type="button" 
                v-for="type in paymentTypes" 
                :key="type.value"
                @click="form.type = type.value"
                :class="[
                  'border rounded-lg px-3 py-2 text-sm font-medium transition-all',
                  form.type === type.value 
                    ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' 
                    : 'bg-slate-950 border-slate-700 text-slate-400 hover:border-slate-500 hover:text-slate-200'
                ]"
              >
                {{ type.label }}
              </button>
            </div>
          </div>

          <!-- Referência -->
          <div>
             <label class="block text-sm font-medium text-slate-300 mb-1.5">Referência de Pagamento</label>
             <input 
               v-model="form.reference" 
               type="text" 
               :placeholder="placeholderReference"
               class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white focus:border-emerald-500 focus:outline-none placeholder-slate-600"
             />
             <p v-if="validationError" class="text-xs text-rose-400 mt-1">{{ validationError }}</p>
          </div>
          
          <!-- Botões -->
          <div class="flex items-center justify-end gap-3 mt-8">
             <button 
               type="button" 
               @click="close" 
               class="px-4 py-2 text-slate-400 hover:text-white transition-colors text-sm font-medium"
             >
               Cancelar
             </button>
             <button 
               type="submit" 
               :disabled="loading || !!validationError || !isFormValid"
               class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium shadow-lg shadow-emerald-900/20 transition-all"
             >
               <span v-if="loading">A processar...</span>
               <span v-else>Confirmar Pagamento</span>
             </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { Teleport } from 'vue'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'
import api from '@/services/api'

const props = defineProps({
  isOpen: Boolean
})

const emit = defineEmits(['close', 'success'])

const paymentTypes = [
  { value: 'MBWAY', label: 'MB WAY' },
  { value: 'VISA', label: 'Visa' },
  { value: 'PAYPAL', label: 'PayPal' },
  { value: 'MB', label: 'Multibanco' },
  { value: 'IBAN', label: 'Transferência' }
]

const form = ref({
  value: 5,
  type: 'MBWAY',
  reference: ''
})

const loading = ref(false)

const placeholderReference = computed(() => {
   switch(form.value.type) {
     case 'MBWAY': return '9xxxxxxxxx (9 dígitos)';
     case 'VISA': return '4xxxxxxxxxxxxxxx (16 dígitos)';
     case 'MB': return 'xxxxx-xxxxxxxxx (entidade-ref)';
     case 'PAYPAL': return 'email@exemplo.com';
     case 'IBAN': return 'PT50...';
     default: return 'Referência';
   }
})

const validationError = computed(() => {
  const { type, reference, value } = form.value
  
  // 1. Validar Valor (Limites)
  if (!value || value < 1 || value > 99) return 'Valor deve ser entre 1€ e 99€'
  
  if (type === 'MBWAY' && value > 5) return 'Limite MBWAY é 5€'
  if (type === 'PAYPAL' && value > 10) return 'Limite PayPal é 10€'
  if (type === 'MB' && value > 20) return 'Limite Multibanco é 20€'
  if (type === 'VISA' && value > 30) return 'Limite Visa é 30€'
  if (type === 'IBAN' && value > 50) return 'Limite IBAN é 50€'

  // 2. Validar Referência (Regex)
  if (!reference) return null // Ainda a escrever
  
  if (type === 'MBWAY') {
    if (!/^9\d{8}$/.test(reference)) return 'Deve ter 9 dígitos e começar por 9'
  }
  if (type === 'VISA') {
    if (!/^4\d{15}$/.test(reference)) return 'Deve ter 16 dígitos e começar por 4'
  }
  if (type === 'MB') {
    if (!/^\d{5}-\d{9}$/.test(reference)) return 'Formato xxxxx-xxxxxxxxx'
  }
  if (type === 'PAYPAL') {
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(reference)) return 'Email inválido'
  }
  if (type === 'IBAN') {
    if (!/^[A-Z]{2}\d{23}$/.test(reference)) return '2 letras + 23 dígitos'
  }

  return null
})

const isFormValid = computed(() => {
   return form.value.value && form.value.reference && !validationError.value
})

const close = () => {
  emit('close')
  // Reset form after close animation ideally, but ok for now
  setTimeout(() => {
     form.value = { value: 5, type: 'MBWAY', reference: '' }
  }, 200)
}

const handlePurchase = async () => {
  if (!isFormValid.value) return

  loading.value = true

  try {
    const response = await api.post('/transactions', {
       type: form.value.type,
       reference: form.value.reference,
       value: form.value.value
    })
    
    const successMessage = response.data?.message || 'Pagamento confirmado. Moedas adicionadas.'
    toast.success(successMessage, { id: 'coin-purchase-success' })
    emit('success', response.data)
    close()
  } catch (error) {
    const baseMessage = error.response?.data?.message || 'Erro ao processar pagamento.'
    const gatewayError = error.response?.data?.errors?.message
    const details = gatewayError ? ` (${gatewayError})` : ''
    toast.error(`${baseMessage}${details}`, { id: 'coin-purchase-error' })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.no-spin::-webkit-inner-spin-button,
.no-spin::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.no-spin {
  -moz-appearance: textfield;
}
</style>
