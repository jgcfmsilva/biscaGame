<template>
  <div class="video-chat-container fixed bottom-24 right-6 z-40 flex flex-col items-end gap-2">
    <!-- Admin Selection List -->
    <div v-if="showAdminList && availableAdmins.length" class="bg-slate-900 border border-slate-800 p-4 rounded-xl shadow-2xl flex flex-col gap-3 w-80 mb-4 transition-opacity duration-150">
        <div class="flex justify-between items-center">
            <h3 class="font-bold text-sm text-slate-100">Escolhe um administrador</h3>
            <button @click="showAdminList = false" class="text-slate-400 hover:text-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="flex flex-col gap-2 max-h-60 overflow-y-auto">
            <button v-for="admin in availableAdmins" :key="admin.id"
                @click="selectAdmin(admin)"
                class="flex items-center gap-3 p-2 rounded-lg transition-colors text-left bg-slate-800/70 border border-slate-700/60 hover:border-indigo-500/60 hover:bg-slate-800"
            >
                <div class="h-12 w-12 rounded-full overflow-hidden border border-slate-700 bg-slate-800 flex items-center justify-center shrink-0">
                     <img
                        :src="adminAvatar(admin) || defaultAvatar"
                        @error="setFallback($event)"
                        class="w-full h-full object-cover text-transparent"
                        alt=""
                     >
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-100">{{ admin.nickname }}</span>
                    <span class="text-xs text-slate-400">{{ admin.name || 'Administrador' }}</span>
                </div>
            </button>
        </div>
    </div>

    <!-- Quick Call Button (Only if not in call) -->
    <button v-if="!isInCall && !incomingCall" 
        @click="startCall" 
        class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-3 shadow-lg transition-transform hover:scale-105"
        title="Ligar ao Suporte">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
    </button>

    <!-- Incoming Call Modal -->
    <div
        v-if="incomingCall"
        class="w-72 rounded-2xl border border-emerald-500/30 bg-slate-950/95 p-4 shadow-2xl shadow-emerald-900/40 animate-bounce-in"
    >
        <div class="flex items-start gap-3 relative">
            <div class="h-11 w-11 rounded-full bg-emerald-500/10 border border-emerald-400/30 flex items-center justify-center text-emerald-300">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-[11px] uppercase tracking-wide text-emerald-300/90 font-semibold">Pedido de Suporte</p>
                <p class="text-base font-semibold text-slate-50 mt-1">{{ incomingCall.senderName }}</p>
            </div>
            <button
                @click="toggleRingtone"
                class="absolute right-0 top-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-[11px] font-semibold transition bg-slate-900/90 text-slate-100 border-slate-700/70 backdrop-blur"
            >
                <svg v-if="isRingtoneMuted" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 9-5 5"/><path d="m9 5-3 3"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12"/><path d="M15 9v-1"/><path d="M19 11a7 7 0 0 1-7 7"/><path d="M5 10v2a7 7 0 0 0 7 7"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 13-2-2 2-2"/><path d="M6 8v8"/><path d="M20 18h-8"/><path d="M20 12h-8"/><path d="M20 6h-8"/></svg>
                <span>{{ isRingtoneMuted ? 'Som off' : 'Som on' }}</span>
            </button>
        </div>
        <div class="mt-3 rounded-xl border border-slate-800 bg-slate-900 overflow-hidden relative">
            <video
                ref="incomingPreviewVideo"
                autoplay
                muted
                playsinline
                class="w-full h-32 object-cover bg-slate-800"
            ></video>
            <div class="absolute left-2 bottom-2 flex gap-2">
                <button @click="toggleMic" :class="['flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-xs transition',
                    isMicMuted ? 'bg-amber-500/10 text-amber-200 border-amber-500/40' : 'bg-slate-900/80 text-slate-100 border-slate-600/60 backdrop-blur']">
                    <svg v-if="!isMicMuted" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v14"/><path d="M8 5a4 4 0 0 1 8 0v6a4 4 0 1 1-8 0Z"/><path d="M19 10v2a7 7 0 0 1-7 7 7 7 0 0 1-7-7v-2"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 9-5 5"/><path d="m9 5-3 3"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12"/><path d="M15 9v-1"/><path d="M19 11a7 7 0 0 1-7 7"/><path d="M5 10v2a7 7 0 0 0 7 7"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                </button>
                <button @click="toggleCamera" :class="['flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-xs transition',
                    isCameraOff ? 'bg-amber-500/10 text-amber-200 border-amber-500/40' : 'bg-slate-900/80 text-slate-100 border-slate-600/60 backdrop-blur']">
                    <svg v-if="!isCameraOff" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 5 3V5l-5 3"/><rect x="2" y="4" width="14" height="16" rx="2" ry="2"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 5 3V9"/><path d="m3 3 18 18"/><rect x="2" y="4" width="14" height="16" rx="2" ry="2"/></svg>
                </button>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button
                @click="acceptCall"
                class="flex-1 inline-flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white py-2 rounded-lg text-sm font-semibold shadow-lg shadow-emerald-900/40 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Aceitar
            </button>
            <button
                @click="rejectCall"
                class="flex-1 inline-flex items-center justify-center gap-2 bg-red-500 hover:bg-red-400 text-white py-2 rounded-lg text-sm font-semibold shadow-lg shadow-red-900/40 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/><line x1="22" x2="16" y1="16" y2="10"/><line x1="16" x2="22" y1="16" y2="10"/></svg>
                Recusar
            </button>
        </div>
    </div>

    <!-- Preview before calling -->
    <transition name="scale">
      <div v-if="showPreview" class="bg-slate-900 border border-slate-800 p-4 rounded-xl shadow-2xl flex flex-col gap-3 w-80 transition-opacity duration-150">
        <div class="text-sm font-semibold text-slate-100">Pré-visualização</div>
        <video ref="previewVideo" autoplay playsinline muted class="w-full h-48 object-cover rounded-lg bg-slate-800 border border-slate-700"></video>
        <div class="flex justify-center gap-3">
            <button @click="toggleMic" :class="['flex items-center gap-2 px-3 py-2 rounded-lg border text-sm transition',
                isMicMuted ? 'bg-amber-500/10 text-amber-200 border-amber-500/40' : 'bg-slate-800 text-slate-200 border-slate-700']">
                <svg v-if="!isMicMuted" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v14"/><path d="M8 5a4 4 0 0 1 8 0v6a4 4 0 1 1-8 0Z"/><path d="M19 10v2a7 7 0 0 1-7 7 7 7 0 0 1-7-7v-2"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 9-5 5"/><path d="m9 5-3 3"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12"/><path d="M15 9v-1"/><path d="M19 11a7 7 0 0 1-7 7"/><path d="M5 10v2a7 7 0 0 0 7 7"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                <span>{{ isMicMuted ? 'Micro desligado' : 'Micro ligado' }}</span>
            </button>
            <button @click="toggleCamera" :class="['flex items-center gap-2 px-3 py-2 rounded-lg border text-sm transition',
                isCameraOff ? 'bg-amber-500/10 text-amber-200 border-amber-500/40' : 'bg-slate-800 text-slate-200 border-slate-700']">
                <svg v-if="!isCameraOff" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 5 3V5l-5 3"/><rect x="2" y="4" width="14" height="16" rx="2" ry="2"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 5 3V9"/><path d="m3 3 18 18"/><rect x="2" y="4" width="14" height="16" rx="2" ry="2"/></svg>
                <span>{{ isCameraOff ? 'Câmara desligada' : 'Câmara ligada' }}</span>
            </button>
        </div>
        <div class="flex gap-2">
          <button @click="cancelPreview" class="flex-1 bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700 py-2 rounded-md text-sm transition-colors">Cancelar</button>
          <button @click="confirmCall" class="flex-1 bg-indigo-500 hover:bg-indigo-400 text-white border border-indigo-400 py-2 rounded-md text-sm transition-colors">Iniciar chamada</button>
        </div>
      </div>
    </transition>

    <!-- Active Call Interface -->
    <transition name="scale">
        <div v-show="isInCall" class="bg-black rounded-xl overflow-hidden shadow-2xl w-80 h-60 relative border border-zinc-700">
            <!-- Remote Video (Full) -->
            <video ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover bg-zinc-900"></video>
            
            <!-- Local Video (PiP) -->
            <video ref="localVideo" autoplay playsinline muted class="absolute bottom-2 right-2 w-20 h-20 object-cover rounded-lg border border-white/20 bg-zinc-800"></video>

            <!-- Controls -->
            <div class="absolute bottom-2 left-2 flex gap-2 z-50">
                <button @click="toggleMic" :class="['p-2 rounded-full shadow-lg cursor-pointer', isMicMuted ? 'bg-amber-500 text-white' : 'bg-white/15 text-white hover:bg-white/25']" title="Microfone">
                    <svg v-if="!isMicMuted" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v14"/><path d="M8 5a4 4 0 0 1 8 0v6a4 4 0 1 1-8 0Z"/><path d="M19 10v2a7 7 0 0 1-7 7 7 7 0 0 1-7-7v-2"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 9-5 5"/><path d="m9 5-3 3"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12"/><path d="M15 9v-1"/><path d="M19 11a7 7 0 0 1-7 7"/><path d="M5 10v2a7 7 0 0 0 7 7"/><line x1="12" x2="12" y1="19" y2="23"/></svg>
                </button>
                <button @click="toggleCamera" :class="['p-2 rounded-full shadow-lg', isCameraOff ? 'bg-amber-500 text-white' : 'bg-white/15 text-white']" title="Câmara">
                    <svg v-if="!isCameraOff" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 5 3V5l-5 3"/><rect x="2" y="4" width="14" height="16" rx="2" ry="2"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 5 3V9"/><path d="m3 3 18 18"/><rect x="2" y="4" width="14" height="16" rx="2" ry="2"/></svg>
                </button>
                <button @click="confirmEndCall" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67 2 2 0 0 1 2-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L9.91 8.09"/></svg>
                </button>
            </div>
            
            <div v-if="connectionState === 'connecting'" class="absolute inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm z-10">
                <span class="text-white text-xs animate-pulse">A ligar...</span>
            </div>
        </div>
    </transition>

    <!-- End call confirm -->
    <transition name="scale">
        <div v-if="showEndConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
            <div class="w-full max-w-sm bg-slate-950 border border-slate-800 rounded-2xl p-5 shadow-[0_20px_80px_rgba(0,0,0,0.55)]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-10 w-10 rounded-full bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67 2 2 0 0 1 2-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L9.91 8.09"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-100">Terminar chamada com {{ currentPeerName || 'o outro utilizador' }}?</p>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="showEndConfirm = false" class="flex-1 bg-slate-800 text-slate-200 border border-slate-700 rounded-lg py-2 text-sm hover:bg-slate-750">Cancelar</button>
                    <button @click="endCall(true, selfDisplayName, true)" class="flex-1 bg-red-500 hover:bg-red-400 text-white rounded-lg py-2 text-sm font-semibold shadow-lg shadow-red-900/40">
                        Terminar
                    </button>
                </div>
            </div>
        </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useWsStore } from '@/stores/ws'
import { useAuthStore } from '@/stores/auth'
import { toast } from 'vue-sonner'
import api from '@/services/api'
import { User } from 'lucide-vue-next'

const wsStore = useWsStore()
const authStore = useAuthStore()

const getMediaStream = async (constraints) => {
    // Check if mediaDevices API exists (it requires HTTPS or localhost)
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error("O navegador não suporta acesso à câmara/microfone (HTTPS necessário).") // Likely the issue on cluster
    }
    try {
        return await navigator.mediaDevices.getUserMedia(constraints)
    } catch (err) {
        console.error("getMediaStream error:", err)
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            throw new Error("Permissão negada. Verifica se bloqueaste o acesso à câmara/microfone.")
        }
        if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            throw new Error("Câmara ou microfone não encontrados.")
        }
        if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
            throw new Error("A câmara/microfone já está a ser usada por outra app.")
        }
        throw err
    }
}

const localVideo = ref(null)
const remoteVideo = ref(null)
const previewVideo = ref(null)
const incomingPreviewVideo = ref(null)

const availableAdmins = ref([])
const showAdminList = ref(false)
const isLoadingAdmins = ref(false)
const showPreview = ref(false)
const previewStream = ref(null)
const targetAdminId = ref(null)
const targetAdminName = ref(null)
const isMicMuted = ref(false)
const isCameraOff = ref(false)

const isInCall = ref(false)
const incomingCall = ref(null) // { senderId, senderName, offer }
const connectionState = ref('idle')
const peerConnection = ref(null)
const localStream = ref(null)
const pendingCandidates = ref([])
const remoteDescriptionSet = ref(false)
const currentPeerId = ref(null)
const currentPeerName = ref('')
const showEndConfirm = ref(false)
const callTimeout = ref(null)
const incomingPreviewStream = ref(null)
const ringtoneAudio = ref(null)
const isRingtoneMuted = ref(false)
const audioCtx = ref(null)
const beepInterval = ref(null)
const shouldPlayRingtone = ref(false)
const audioUnlocked = ref(false)
const warmupCtx = ref(null)
const suppressRejectToast = ref(false)
const suppressRejectTimer = ref(null)
const resumeRingtoneHandler = ref(null)

const selfDisplayName = computed(() =>
    authStore.user?.nickname || authStore.user?.name || 'Utilizador'
)

const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')
const defaultAvatar = `${apiBase || window.location.origin}/api/player/avatar/photos_avatars/anonymous.png`

// Configuração ICE (STUN público + TURN opcional via env)
const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        ...(import.meta.env.VITE_TURN_URL
            ? [{
                urls: import.meta.env.VITE_TURN_URL,
                username: import.meta.env.VITE_TURN_USERNAME || undefined,
                credential: import.meta.env.VITE_TURN_PASSWORD || undefined
            }]
            : [])
    ]
}

// Estar atento a sinais de vídeo vindos do servidor
// Como não alterei muito o ws.js, usei um EventListener global para apanhar os sinais
// MELHORIA: Devia ter usado um event bus no Pinia, mas assim funciona bem.
const onSignal = async (event) => {
    const { type, senderId, senderName, payload } = event.detail
    
    // Ignore own signals
    if (senderId === authStore.user?.id) return

    if (payload.type === 'offer') {
        if (isInCall.value) {
            // Busy
            return
        }
        incomingCall.value = { senderId, senderName, offer: payload }
        currentPeerName.value = senderName || 'Utilizador'
        await setupIncomingPreview()
        shouldPlayRingtone.value = true
        startRingtone()
    } else if (payload.type === 'answer') {
        if (peerConnection.value) {
            await peerConnection.value.setRemoteDescription(new RTCSessionDescription(payload))
            remoteDescriptionSet.value = true
            flushPendingCandidates()
            clearCallTimeout()
        }
    } else if (payload.ended === true) {
        shouldPlayRingtone.value = false
        stopRingtone()
        const who = payload.by || currentPeerName.value || 'Outro utilizador'
        endCall(false, who, false, payload.timedOut === true)
    } else if (payload.reject === true) {
        shouldPlayRingtone.value = false
        stopRingtone()
        if (!suppressRejectToast.value) {
            toast.info('Chamada recusada pelo administrador.', { id: 'video-call-rejected' })
        }
        endCall(false, '', false, false, true) // skip extra toast
    } else if (payload.candidate) {
        if (peerConnection.value) {
            if (!remoteDescriptionSet.value) {
                pendingCandidates.value.push(payload.candidate)
            } else {
                await peerConnection.value.addIceCandidate(new RTCIceCandidate(payload.candidate))
            }
        }
    }
}

onMounted(() => {
    window.addEventListener('video-signal', onSignal)
    window.addEventListener('video-signal-error', onSignalError)
    setupAudioUnlockListeners()
})

onUnmounted(() => {
    window.removeEventListener('video-signal', onSignal)
    window.removeEventListener('video-signal-error', onSignalError)
    clearAudioUnlockListeners()
    endCall()
    stopRingtone()
})

const onSignalError = (event) => {
    const { message } = event.detail
    const normalized = (message || '').toLowerCase().trim()
    const isRejected = normalized.includes('chamada recusada pelo administrador')
    const id = isRejected ? 'video-call-rejected' : `video-call-error-${message || 'unknown'}`
    if (isRejected) {
        if (!suppressRejectToast.value) {
            toast.info('Chamada recusada pelo administrador.', { id })
        }
    } else {
        toast.error(`Erro na chamada: ${message}`, { id })
    }
    stopRingtone()
    endCall(false, '', false, false, true) // skip extra toast from endCall
}

const setupPeerConnection = (targetId) => {
    const pc = new RTCPeerConnection(rtcConfig)
    
    pc.onicecandidate = (event) => {
        if (event.candidate) {
            wsStore.send({
                type: 'video_signal',
                targetId: targetId,
                payload: { candidate: event.candidate }
            })
        }
    }

    pc.ontrack = (event) => {
        if (!remoteVideo.value) return
        const [stream] = event.streams
        if (stream) {
            remoteVideo.value.srcObject = stream
        } else {
            // Fallback: assemble stream manually when event.streams is empty
            const current = remoteVideo.value.srcObject instanceof MediaStream
                ? remoteVideo.value.srcObject
                : new MediaStream()
            current.addTrack(event.track)
            remoteVideo.value.srcObject = current
        }
    }

    pc.onconnectionstatechange = () => {
        connectionState.value = pc.connectionState
        if (pc.connectionState === 'disconnected' || pc.connectionState === 'closed') {
            endCall()
        }
    }

    return pc
}

const fetchAdmins = async () => {
    availableAdmins.value = []
    isLoadingAdmins.value = true
    try {
        const { data } = await api.get('/admin/active-admins')
        const list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : [])
        // Filter out self if I am an admin
        availableAdmins.value = list.filter(a => a.id !== authStore.user?.id)
        if (availableAdmins.value.length === 0) {
            toast.error("Nenhum administrador online no momento.")
        }
    } catch (e) {
        console.error("Failed to fetch admins", e)
        toast.error("Erro ao obter lista de administradores")
        availableAdmins.value = []
    } finally {
        isLoadingAdmins.value = false
    }
}

const adminAvatar = (admin) => {
    const path = (admin?.photo_avatar_filename || admin?.avatar || '').trim()
    if (!path) return defaultAvatar
    if (/^https?:\/\//i.test(path)) return path
    let normalized = path.startsWith('/') ? path.slice(1) : path
    // Backend expects avatars to live under photos_avatars/ or avatars/
    if (!normalized.includes('/')) {
        normalized = `photos_avatars/${normalized}`
    }
    return `${apiBase}/api/player/avatar/${normalized}`
}

const setFallback = (event) => {
    const img = event?.target
    if (img && img.src !== defaultAvatar) {
        img.onerror = null
        img.src = defaultAvatar
    }
}

const preparePreview = async (admin) => {
    targetAdminId.value = admin?.id ?? null
    targetAdminName.value = admin?.nickname ?? ''
    try {
        previewStream.value = await getMediaStream({ video: true, audio: true })
        showPreview.value = true
        await nextTick()
        if (previewVideo.value) previewVideo.value.srcObject = previewStream.value
        applyMediaState()
    } catch (err) {
        console.error("Erro no preparePreview:", err)
        toast.error(err.message || "Não foi possível aceder à câmara/microfone.")
        targetAdminId.value = null
    }
}

const cancelPreview = () => {
    showPreview.value = false
    stopStream(previewStream.value)
    previewStream.value = null
    targetAdminId.value = null
}

const confirmCall = () => {
    if (!targetAdminId.value) return
    const stream = previewStream.value
    showPreview.value = false
    previewStream.value = null
    performCall(targetAdminId.value, stream)
}

const startCall = async () => {
    if (!authStore.isAuthenticated) {
        toast.info("Precisas de ter conta criada e sessão iniciada para contactar o suporte.")
        return
    }

    // Admins cannot call support (logic decision)
    if (authStore.user?.type === 'A') {
        toast.warning("Administradores não podem iniciar chamadas de suporte (apenas receber).")
        return
    }

    await fetchAdmins()

    if (availableAdmins.value.length === 0) {
        return
    }

    if (availableAdmins.value.length === 1) {
        // Only one admin, call directly
        preparePreview(availableAdmins.value[0])
    } else {
        // Multiple admins, show list (force re-render to avoid flash)
        showAdminList.value = false
        await nextTick()
        showAdminList.value = true
    }
}

const selectAdmin = (admin) => {
    showAdminList.value = false
    preparePreview(admin)
}

const performCall = async (targetId, streamOverride = null) => {
    
    if (targetId === authStore.user?.id) {
        toast.error("Não podes ligar para ti mesmo!")
        return
    }

    isInCall.value = true
    connectionState.value = 'connecting'
    remoteDescriptionSet.value = false
    pendingCandidates.value = []
    currentPeerId.value = targetId
    currentPeerName.value = targetAdminName.value || 'Administrador'
    
    try {
    if (streamOverride) {
        localStream.value = streamOverride
    } else {
        localStream.value = await getMediaStream({ video: true, audio: true })
    }
    if (localVideo.value) localVideo.value.srcObject = localStream.value
    applyMediaState()
        
        peerConnection.value = setupPeerConnection(targetId)
        
        localStream.value.getTracks().forEach(track => {
            peerConnection.value.addTrack(track, localStream.value)
        })

        const offer = await peerConnection.value.createOffer()
        await peerConnection.value.setLocalDescription(offer)
        
        if (wsStore.connected) {
            wsStore.send({
                type: 'video_signal',
                targetId: targetId,
                payload: offer
            })
            // Timeout if admin does not answer
            clearCallTimeout()
            callTimeout.value = setTimeout(() => {
                stopRingtone()
                endCall(true, '', false, true)
            }, 15000)
        } else {
             toast.error("Erro: Sem ligação ao servidor.");
             endCall();
        }
        
    } catch (err) {
        console.error("Call failed", err)
        toast.error(err.message || "Erro ao iniciar chamada.")
        endCall()
    }
}

const acceptCall = async () => {
    stopRingtone() // <--- Fix: Stop immediately
    if (!incomingCall.value) {
        toast.error("Pedido de chamada expirou.")
        return
    }

    const { senderId, offer } = incomingCall.value
    incomingCall.value = null
    shouldPlayRingtone.value = false
    // stopRingtone() was here
    isInCall.value = true
    connectionState.value = 'connecting'
    remoteDescriptionSet.value = false
    pendingCandidates.value = []
    currentPeerId.value = senderId
    currentPeerName.value = currentPeerName.value || 'Utilizador'

    try {
        const constraints = { video: { facingMode: "user" }, audio: true }
        try {
            if (incomingPreviewStream.value) {
                localStream.value = incomingPreviewStream.value
                incomingPreviewStream.value = null
            } else {
                localStream.value = await getMediaStream(constraints)
            }
        } catch (err) {
            // Try audio-only as a fallback to at least allow acceptance
            localStream.value = await getMediaStream({ audio: true })
        }

        if (localVideo.value) localVideo.value.srcObject = localStream.value

        peerConnection.value = setupPeerConnection(senderId)
        
        localStream.value.getTracks().forEach(track => {
            peerConnection.value.addTrack(track, localStream.value)
        })
        applyMediaState()

        await peerConnection.value.setRemoteDescription(new RTCSessionDescription(offer))
        remoteDescriptionSet.value = true
        flushPendingCandidates()
        const answer = await peerConnection.value.createAnswer()
        await peerConnection.value.setLocalDescription(answer)

        wsStore.send({
            type: 'video_signal',
            targetId: senderId,
            payload: answer
        })

    } catch (err) {
        console.error("Accept failed", err)
        const reason = err?.name === 'NotAllowedError'
            ? 'Permissões da câmara/micro recusadas.'
            : err?.message || 'Confirma câmara/micro.'
        toast.error(`Erro ao aceitar a chamada. ${reason}`)
        endCall()
    }
}

const rejectCall = () => {
    // Tell caller the call was rejected
    if (incomingCall.value) {
        wsStore.send({
            type: 'video_signal',
            targetId: incomingCall.value.senderId,
            payload: { reject: true }
        })
    }
    incomingCall.value = null
    if (incomingPreviewStream.value) {
        stopStream(incomingPreviewStream.value)
        incomingPreviewStream.value = null
    }
    shouldPlayRingtone.value = false
    stopRingtone()
    toast.info('Chamada cancelada por si.', { id: 'video-call-self-rejected' })
    suppressRejectToastsFor(4000)
    endCall(false, '', true, false, true) // skip duplicate toast
}

const toggleMic = () => {
    isMicMuted.value = !isMicMuted.value
    applyMediaState()
}

const toggleCamera = () => {
    isCameraOff.value = !isCameraOff.value
    applyMediaState()
}

const confirmEndCall = () => {
    showEndConfirm.value = true
}

const endCall = (notifyPeer = false, endedBy = '', endedBySelf = false, timedOut = false, skipToast = false) => {
    if (!isInCall.value && !incomingCall.value && connectionState.value === 'idle') {
        return
    }
    clearCallTimeout()
    shouldPlayRingtone.value = false
    if (notifyPeer && currentPeerId.value) {
        wsStore.send({
            type: 'video_signal',
            targetId: currentPeerId.value,
            payload: { ended: true, by: selfDisplayName.value, timedOut }
        })
    }

    if (peerConnection.value) {
        peerConnection.value.close()
        peerConnection.value = null
    }
    if (localStream.value) {
        localStream.value.getTracks().forEach(t => t.stop())
        localStream.value = null
    }
    if (previewStream.value) {
        stopStream(previewStream.value)
        previewStream.value = null
    }
    isInCall.value = false
    showPreview.value = false
    targetAdminId.value = null
    connectionState.value = 'idle'
    remoteDescriptionSet.value = false
    pendingCandidates.value = []
    currentPeerId.value = null
    showEndConfirm.value = false
    incomingCall.value = null
    stopRingtone()
    if (incomingPreviewStream.value) {
        stopStream(incomingPreviewStream.value)
        incomingPreviewStream.value = null
    }

    if (!skipToast) {
        let message = ''
        if (timedOut) {
            message = 'Tempo esgotado'
        } else if (endedBySelf) {
            message = 'Chamada cancelada por si.'
        } else if (endedBy) {
            message = `Chamada terminada por ${endedBy}.`
        }

        if (message) {
            toast.info(message, { duration: 3500 })
        }
    }
}

const clearCallTimeout = () => {
    if (callTimeout.value) {
        clearTimeout(callTimeout.value)
        callTimeout.value = null
    }
}

const stopStream = (stream) => {
    if (!stream) return
    stream.getTracks().forEach(t => t.stop())
}

const applyMediaState = () => {
    const micOn = !isMicMuted.value
    const camOn = !isCameraOff.value
    ;[localStream.value, previewStream.value, incomingPreviewStream.value].forEach(stream => {
        if (!stream) return
        stream.getAudioTracks().forEach(track => { track.enabled = micOn })
        stream.getVideoTracks().forEach(track => { track.enabled = camOn })
    })
}

const flushPendingCandidates = async () => {
    if (!peerConnection.value || !remoteDescriptionSet.value) return
    while (pendingCandidates.value.length) {
        const candidate = pendingCandidates.value.shift()
        try {
            await peerConnection.value.addIceCandidate(new RTCIceCandidate(candidate))
        } catch (err) {
            console.error("Failed to add queued ICE candidate", err)
        }
    }
}

const setupIncomingPreview = async () => {
    if (incomingPreviewStream.value) return
    try {
        incomingPreviewStream.value = await getMediaStream({ video: true, audio: true })
        await nextTick()
        if (incomingPreviewVideo.value) {
            incomingPreviewVideo.value.srcObject = incomingPreviewStream.value
        }
    } catch (err) {
        console.error("Erro a iniciar preview do admin", err)
        // Opcional: mostrar toast aqui também se desejado
    }
}

async function startRingtone() {
    if (isRingtoneMuted.value) return
    // Intenção de tocar ringtone
    shouldPlayRingtone.value = true
    stopRingtone(true)

    // Prefer WebAudio to avoid media resource issues with <audio> data URIs
    try {
        const Ctor = window.AudioContext || window.webkitAudioContext
        if (!audioUnlocked.value) {
            setupAudioUnlockListeners()
        }
        const ctx = new Ctor()
        audioCtx.value = ctx

        const beep = () => {
            const osc = ctx.createOscillator()
            const gain = ctx.createGain()
            osc.type = 'sine'
            osc.frequency.value = 660
            gain.gain.value = 0.08
            osc.connect(gain).connect(ctx.destination)
            osc.start()
            osc.stop(ctx.currentTime + 0.25)
        }

        // Some browsers start suspended until user interaction; retry on resume
        if (ctx.state === 'suspended') {
            setupRingtoneResumeOnInteraction(() => {
                if (!shouldPlayRingtone.value) return
                return ctx.resume().then(() => {
                    if (!shouldPlayRingtone.value) return
                    beep()
                }).catch(() => {})
            })
        } else {
            beep()
        }
        beepInterval.value = setInterval(() => {
            if (ctx.state === 'suspended' || !shouldPlayRingtone.value) return
            beep()
        }, 1000)
        return
    } catch (audioErr) {
        console.error("Falha no WebAudio, a tentar elemento de áudio", audioErr)
    }

    // Last resort: simple audio element (may still be blocked by autoplay policies)
    try {
        const tone = new Audio('data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQgAAAAA//8AAP//AAD//wAA//8AAP//AAD//wAA')
        tone.loop = true
        tone.volume = 0.35
        await tone.play()
        ringtoneAudio.value = tone
    } catch (err) {
        console.error("Erro a tocar ringtone com elemento de áudio", err)
        setupRingtoneResumeOnInteraction()
    }
}

function stopRingtone(keepIntent = false) {
    clearRingtoneResumeOnInteraction()
    if (!keepIntent) {
        shouldPlayRingtone.value = false
    }
    if (beepInterval.value) {
        clearInterval(beepInterval.value)
        beepInterval.value = null
    }
    if (ringtoneAudio.value) {
        ringtoneAudio.value.pause()
        ringtoneAudio.value.currentTime = 0
        ringtoneAudio.value = null
    }
    if (audioCtx.value) {
        audioCtx.value.close().catch(() => {})
        audioCtx.value = null
    }
}

async function toggleRingtone() {
    isRingtoneMuted.value = !isRingtoneMuted.value
    if (isRingtoneMuted.value) {
        stopRingtone()
    } else if (incomingCall.value) {
        await startRingtone()
    }
}

function setupRingtoneResumeOnInteraction(resumeFn) {
    if (resumeRingtoneHandler.value) return
    const handler = async () => {
        clearRingtoneResumeOnInteraction()
        if (!incomingCall.value || isRingtoneMuted.value || !shouldPlayRingtone.value) return
        if (typeof resumeFn === 'function') {
            try { await resumeFn() } catch (_) { /* ignore */ }
        } else {
            await startRingtone()
        }
    }
    resumeRingtoneHandler.value = handler
    ;['pointerdown', 'keydown', 'visibilitychange', 'focus'].forEach(event =>
        window.addEventListener(event, handler, { once: true })
    )
}

function clearRingtoneResumeOnInteraction() {
    if (!resumeRingtoneHandler.value) return
    ;['pointerdown', 'keydown', 'visibilitychange', 'focus'].forEach(event =>
        window.removeEventListener(event, resumeRingtoneHandler.value)
    )
    resumeRingtoneHandler.value = null
}

function suppressRejectToastsFor(ms) {
    suppressRejectToast.value = true
    if (suppressRejectTimer.value) {
        clearTimeout(suppressRejectTimer.value)
    }
    suppressRejectTimer.value = setTimeout(() => {
        suppressRejectToast.value = false
        suppressRejectTimer.value = null
    }, ms)
}

function unlockAudioContextOnce() {
    if (audioUnlocked.value) return
    try {
        const Ctor = window.AudioContext || window.webkitAudioContext
        const ctx = new Ctor()
        warmupCtx.value = ctx
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        gain.gain.value = 0.0001
        osc.connect(gain).connect(ctx.destination)
        osc.start()
        osc.stop(ctx.currentTime + 0.05)
        ctx.resume().catch(() => {})
        setTimeout(() => {
            ctx.close().catch(() => {})
            warmupCtx.value = null
        }, 200)
        audioUnlocked.value = true
        clearAudioUnlockListeners()
    } catch (err) {
        console.error("Falha a desbloquear áudio antecipadamente", err)
    }
}

function setupAudioUnlockListeners() {
    if (audioUnlocked.value) return
    clearAudioUnlockListeners()
    ;['pointerdown', 'keydown'].forEach(event =>
        window.addEventListener(event, unlockAudioContextOnce, { once: true })
    )
}

function clearAudioUnlockListeners() {
    ['pointerdown', 'keydown'].forEach(event =>
        window.removeEventListener(event, unlockAudioContextOnce)
    )
}

</script>

<style scoped>
.scale-enter-active,
.scale-leave-active {
  transition: all 0.18s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.96);
}
</style>
