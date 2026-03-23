<template>
    <Dialog :open="isOpen" @update:open="$emit('close')">
        <DialogContent class="sm:max-w-md bg-slate-950 border-slate-800">
            <DialogHeader>
                <DialogTitle class="text-slate-100 flex items-center gap-2">
                    <History class="h-5 w-5 text-brand-400" />
                    Histórico Recente
                </DialogTitle>
                <DialogDescription class="text-slate-400">
                    Os teus últimos 5 movimentos de moedas.
                </DialogDescription>
            </DialogHeader>

            <div class="py-4 space-y-4">
                <div v-if="isLoading" class="flex justify-center py-4">
                    <span class="animate-spin h-6 w-6 border-2 border-brand-500 border-t-transparent rounded-full"></span>
                </div>

                <div v-else-if="transactions.length === 0" class="text-center py-6">
                    <p class="text-slate-500 text-sm">Sem movimentos recentes.</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="tx in recentTransactions" :key="tx.id" 
                        class="flex items-center justify-between p-3 rounded-xl bg-slate-900/50 border border-slate-800/50 transition-colors hover:bg-slate-900 hover:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div :class="[
                                'h-8 w-8 rounded-lg flex items-center justify-center shrink-0',
                                isCredit(tx) ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'
                            ]">
                                <ArrowDownLeft v-if="isCredit(tx)" class="h-4 w-4" />
                                <ArrowUpRight v-else class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-200">{{ resolveLabel(tx) }}</p>
                                <p class="text-[10px] text-slate-500">{{ formatDate(tx.transaction_datetime) }}</p>
                            </div>
                        </div>
                        <span :class="[
                            'text-sm font-semibold',
                            isCredit(tx) ? 'text-emerald-300' : 'text-rose-300'
                        ]">
                            {{ formatCoins(tx.coins, true) }}
                        </span>
                    </div>
                </div>
            </div>

            <DialogFooter class="flex flex-col sm:flex-row items-center gap-2 border-t border-slate-800/60 pt-4 mt-2">
                <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
                    <Button variant="ghost" class="text-xs text-indigo-400 hover:text-indigo-300 hover:bg-indigo-500/10" as-child>
                        <RouterLink to="/me/transactions" @click="$emit('close')">
                            Ver histórico
                        </RouterLink>
                    </Button>
                    <Button variant="default" @click="$emit('close')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 hover:border-slate-600 transition-colors">
                        Fechar
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { computed, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { History, ArrowUpRight, ArrowDownLeft } from 'lucide-vue-next'
import api from '@/services/api'
import { ref } from 'vue'

const props = defineProps({
    isOpen: Boolean
})

const emit = defineEmits(['close'])

const transactions = ref([])
const isLoading = ref(false)

const recentTransactions = computed(() => {
    return transactions.value.slice(0, 5)
})

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        loadTransactions()
    }
})

async function loadTransactions() {
    isLoading.value = true
    try {
        const { data } = await api.get('/coins/transactions')
        transactions.value = Array.isArray(data) ? data : []
    } catch (error) {
        console.error("Failed to load transactions", error)
    } finally {
        isLoading.value = false
    }
}

// Helpers duplicated from TransactionsView to ensure standalone functionality
function isCredit(tx) {
    return coinValue(tx?.coins) > 0
}

function coinValue(value) {
    if (typeof value === 'number') return value
    if (typeof value === 'string') return Number(value) || 0
    return 0
}

function formatCoins(value, withSign = false) {
    const numeric = coinValue(value)
    const abs = Math.abs(numeric).toLocaleString('pt-PT')
    if (!withSign) return `${abs}`
    const sign = numeric > 0 ? '+' : ''
    return `${sign}${abs}`
}

function resolveLabel(tx) {
    if (tx?.custom) {
        if (typeof tx.custom === 'string' && /stake debit/i.test(tx.custom)) return "Aposta Match"
        if (typeof tx.custom === 'object' && tx.custom.description) return tx.custom.description
    }
    return tx?.type?.name ?? 'Transação'
}

function formatDate(date) {
    if (!date) return '—'
    // Simple relative time format or short date
    const d = new Date(date)
    const now = new Date()
    const diff = (now - d) / 1000 // seconds
    
    if (diff < 60) return 'Agora mesmo'
    if (diff < 3600) return `Há ${Math.floor(diff / 60)} min`
    if (diff < 86400) return `Há ${Math.floor(diff / 3600)} horas`
    
    return new Intl.DateTimeFormat('pt-PT', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }).format(d)
}
</script>
