<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
    <!-- Chat Window -->
    <transition name="fade-slide">
      <div v-if="isOpen" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xl rounded-lg w-80 mb-4 overflow-hidden flex flex-col max-h-[500px]">
        <!-- Header -->
        <div class="bg-primary/95 text-primary-foreground p-3 flex justify-between items-center backdrop-blur supports-backdrop-filter:bg-primary/60">
            <div class="flex items-center gap-2">
                <span class="text-xl">🧙‍♂️</span>
                <span class="font-bold">Bisca Guru</span>
            </div>
            <button @click="toggleChat" class="hover:bg-white/20 rounded p-1 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-zinc-50 dark:bg-zinc-950/50" ref="messagesContainer">
             <div v-for="(msg, index) in messages" :key="index" 
                class="flex flex-col max-w-[85%]"
                :class="msg.isUser ? 'self-end items-end' : 'self-start items-start'">
                
                <div class="px-3 py-2 rounded-2xl text-sm shadow-sm relative group"
                    :class="msg.isUser 
                        ? 'bg-primary text-primary-foreground rounded-tr-none' 
                        : 'bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 rounded-tl-none text-zinc-800 dark:text-zinc-200'">
                    <p class="whitespace-pre-line">{{ msg.text }}</p>
                    
                    <!-- TTS Button (Only for Bot) -->
                    <button v-if="!msg.isUser" 
                        @click.stop="speak(msg.text)"
                        class="absolute -right-6 top-1 opacity-0 group-hover:opacity-100 transition-opacity text-zinc-400 hover:text-primary"
                        title="Ouvir">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                    </button>
                </div>
                <span class="text-[10px] text-muted-foreground mt-1 px-1">{{ msg.time }}</span>
            </div>
            <div v-if="isTyping" class="self-start bg-zinc-100 dark:bg-zinc-800 rounded-2xl rounded-tl-none px-3 py-2">
                <span class="animate-pulse">...</span>
            </div>
        </div>

        <!-- Suggestions -->
        <div v-if="suggestions.length > 0" class="p-2 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap gap-2">
            <button v-for="sugg in suggestions" :key="sugg" 
                @click="sendMessage(sugg)"
                class="text-xs bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-full px-3 py-1 transition-colors text-zinc-600 dark:text-zinc-300">
                {{ sugg }}
            </button>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
            <form @submit.prevent="handleSubmit" class="flex gap-2">
                <input 
                    v-model="inputMessage" 
                    type="text" 
                    placeholder="Pergunta sobre regras..." 
                    class="flex-1 text-sm bg-transparent border-none focus:ring-0 placeholder:text-muted-foreground text-zinc-800 dark:text-zinc-200"
                >
                <button type="submit" :disabled="!inputMessage.trim()" 
                    class="p-2 rounded-full bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                </button>
            </form>
        </div>
      </div>
    </transition>

    <!-- Floating Toggle Button -->
    <button 
        @click="toggleChat"
        class="h-14 w-14 rounded-full bg-primary shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all flex items-center justify-center text-primary-foreground z-50"
        :class="{ 'rotate-0': !isOpen, 'rotate-180': isOpen }"
    >
      <span v-if="!isOpen" class="text-2xl">🧙‍♂️</span>
      <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { ChatService } from '@/services/ChatService'

const isOpen = ref(false)
const inputMessage = ref('')
const messages = ref([])
const isTyping = ref(false)
const messagesContainer = ref(null)
const suggestions = ref(ChatService.getSuggestions())

const toggleChat = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && messages.value.length === 0) {
    // Initial greeting
    addBotMessage("Olá! Sou o Bisca Guru. 🧙‍♂️\nEstou aqui para tirar dúvidas sobre regras e pontuação. Pergunta-me algo!")
  }
  if (isOpen.value) {
      scrollToBottom()
  }
}

const scrollToBottom = async () => {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const addBotMessage = (text) => {
    messages.value.push({
        text,
        isUser: false,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    })
    scrollToBottom()
}

const addUserMessage = (text) => {
    messages.value.push({
        text,
        isUser: true,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    })
    scrollToBottom()
}

const sendMessage = async (text) => {
    const msg = text || inputMessage.value
    if (!msg.trim()) return

    addUserMessage(msg)
    inputMessage.value = ''
    suggestions.value = [] // clear suggestions after first interaction or change them

    isTyping.value = true
    await scrollToBottom()

    // Simular um atraso para parecer que está a pensar
    isTyping.value = true
    
    // 1. Tentar responder com o "cérebro" local primeiro (rápido e grátis)
    const localResponse = ChatService.getAnswer(msg)
    
    if (localResponse !== ChatService.DEFAULT_RESPONSE) {
        setTimeout(() => {
            isTyping.value = false
            addBotMessage(localResponse)
        }, 600)
    } else {
        // 2. Se não souber, pergunta ao servidor (AI)
        try {
            const aiResponse = await ChatService.askAI(msg)
            isTyping.value = false
            addBotMessage(aiResponse)
        } catch (error) {
            isTyping.value = false
            addBotMessage("Desculpa, não consegui ligar ao meu cérebro digital. Tenta perguntar 'regras'.")
        }
    }

    // Restaurar as sugestões...
    setTimeout(() => {
        if (suggestions.value.length === 0) {
             // Opcional
        }
    }, 1000)
}

const handleSubmit = () => {
    sendMessage()
}

// Funcionalidade de Text-to-Speech (Extra G7)
const speak = (text) => {
    if (!('speechSynthesis' in window)) return
    
    // Parar se já estiver a falar
    window.speechSynthesis.cancel()

    const utterance = new SpeechSynthesisUtterance(text)
    utterance.lang = 'pt-PT' // Português de Portugal
    utterance.rate = 1.1     // Um pouco mais rápido para não ser aborrecido
    utterance.pitch = 1.0

    window.speechSynthesis.speak(utterance)
}

onMounted(() => {
    // Podíamos abrir automaticamente para novos users, mas optei por não chatear.
})
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
