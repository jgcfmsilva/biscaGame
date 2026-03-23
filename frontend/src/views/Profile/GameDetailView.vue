<template>
  <DefaultLayout>
    <section class="mx-auto max-w-6xl px-4 py-10 space-y-6 text-[15px] md:text-[17px]">
      <div class="flex items-center gap-3">
        <button
          class="px-3 py-2 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 hover:bg-slate-800 transition-colors"
          @click="$router.back()"
        >
          Voltar
        </button>
        <h2 class="text-3xl font-bold text-white">Jogo #{{ gameId }}</h2>
        <span v-if="game" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border"
          :class="statusBadge(game.status).class">
          {{ statusBadge(game.status).label }}
        </span>
      </div>

      <div v-if="loading" class="text-center py-6 text-slate-400">A carregar jogo...</div>
      <div v-if="error" class="text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded">{{ error }}</div>

      <div v-if="game" class="space-y-5">
        <div class="grid gap-4 lg:grid-cols-3">
          <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-5 space-y-4 shadow-[0_20px_50px_rgba(15,23,42,0.35)]">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-lg font-semibold text-slate-100">Visão geral</p>
                <p class="text-xs text-slate-500">Início {{ formatDate(game.began_at) }}</p>
              </div>
              <div class="flex items-center gap-2 text-xs text-slate-400">
                <span>ID #{{ game.id }}</span>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Match</p>
                <p class="text-lg font-semibold">{{ matchId ? `#${matchId}` : '—' }}</p>
              </div>
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Duração</p>
                <p class="text-lg font-semibold">{{ formatDuration(game.total_time) }}</p>
              </div>
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Estado</p>
                <p class="text-lg font-semibold">{{ statusBadge(game.status).label }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
            <h3 class="text-lg font-semibold text-slate-100">Adversário</h3>
            <div class="grid gap-3">
              <button
                type="button"
                class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 shadow-inner shadow-slate-900/40 text-left hover:border-emerald-500/40 hover:-translate-y-[1px] transition"
                @click.stop="opponentPlayer && openPlayer(opponentPlayer)"
              >
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold text-slate-100">{{ displayPlayer(opponentPlayer, opponentPlayer?.nickname) }}</p>
                  <span class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full border bg-slate-800 text-slate-300 border-slate-700">{{ opponentTag }}</span>
                </div>
                <div class="mt-3 flex items-center gap-4">
                  <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-800 border border-blue-500/30">
                    <img :src="playerAvatar(opponentPlayer)" alt="" class="h-full w-full object-cover">
                  </div>
                  <div class="flex-1">
                    <p class="text-xs text-slate-500 mb-1">Pontuação</p>
                    <p class="text-2xl font-semibold text-slate-50">{{ opponentScore }}</p>
                  </div>
                </div>
              </button>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-slate-400">Vencedor</span>
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border bg-emerald-500/10 text-emerald-400 border-emerald-500/30">
                {{ gameWinnerLabelFn(game) }}
              </span>
            </div>
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-5">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-lg font-semibold text-slate-100">Pontuação</h3>
                <p class="text-xs text-slate-500">Distribuição de pontos no jogo</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-slate-500">Total</p>
                <p class="text-2xl font-semibold text-slate-50">{{ totalPoints }}</p>
              </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 flex flex-col gap-3">
                <div class="flex items-center justify-between text-xs text-slate-400">
                  <span>Jogador 1</span>
                  <span class="rounded-full px-2 py-1 bg-emerald-500/10 text-emerald-300 border border-emerald-500/30">{{ player1Percent }}%</span>
                </div>
                <div class="flex items-baseline justify-between">
                  <div class="text-4xl font-bold text-slate-50">{{ player1Score }}</div>
                  <span class="text-xs text-slate-400">Δ {{ scoreDiffPlayer1 }}</span>
                </div>
                <div class="h-2 w-full bg-slate-800/70 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500/80 shadow-lg shadow-emerald-900/40" :style="{ width: player1Bar }"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-400">
                  <span>{{ displayPlayer(match?.player1 ?? game.player1, match?.player1_name) }}</span>
                  <span v-if="winnerId === resolvePlayerId(match?.player1 ?? game.player1, match?.player1_user_id ?? game.player1_user_id)" class="text-emerald-300 font-semibold">Vencedor</span>
                </div>
              </div>
              <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 flex flex-col gap-3">
                <div class="flex items-center justify-between text-xs text-slate-400">
                  <span>Jogador 2</span>
                  <span class="rounded-full px-2 py-1 bg-blue-500/10 text-blue-300 border border-blue-500/30">{{ player2Percent }}%</span>
                </div>
                <div class="flex items-baseline justify-between">
                  <div class="text-4xl font-bold text-slate-50">{{ player2Score }}</div>
                  <span class="text-xs text-slate-400">Δ {{ scoreDiffPlayer2 }}</span>
                </div>
                <div class="h-2 w-full bg-slate-800/70 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500/80 shadow-lg shadow-blue-900/40" :style="{ width: player2Bar }"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-400">
                  <span>{{ displayPlayer(match?.player2 ?? game.player2, match?.player2_name) }}</span>
                  <span v-if="winnerId === resolvePlayerId(match?.player2 ?? game.player2, match?.player2_user_id ?? game.player2_user_id)" class="text-blue-300 font-semibold">Vencedor</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-100">Descrição de turnos</p>
            <span class="text-xs text-slate-500">{{ parsedGameTurns.length }} turno(s)</span>
          </div>
          <div v-if="parsedGameTurns.length" class="space-y-3">
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-200">
              <span class="text-xs text-slate-400">Trunfo</span>
              <div class="text-base font-semibold">{{ formatTrumpSuit(parsedGameTurns) }}</div>
            </div>
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 items-start">
              <div
                v-for="(turn, idx) in parsedGameTurns"
                :key="turn.id || idx"
                class="border border-slate-800/60 rounded-lg overflow-hidden text-sm bg-slate-950/40"
              >
                <button
                  class="w-full flex items-center justify-between px-3 py-2 bg-slate-900/70 text-left text-slate-200 hover:bg-slate-900"
                  @click="toggleTurn(idx)"
                >
                  <div class="flex flex-col gap-1">
                    <span class="font-semibold">Turno {{ turn.turn_number ?? idx + 1 }}</span>
                    <span class="text-[11px] text-slate-400">Vencedor: {{ turnWinnerLabel(turn) }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-[11px] text-slate-400">
                    <span>P1: {{ turn.player1_card_points ?? 0 }} pts</span>
                    <span>P2: {{ turn.player2_card_points ?? 0 }} pts</span>
                    <span class="text-[11px] text-blue-300">{{ expandedTurns[idx] ? 'Esconder' : 'Ver' }}</span>
                  </div>
                </button>
                <transition name="fade">
                  <div v-if="expandedTurns[idx]" class="bg-slate-950/80 px-3 py-3 text-xs md:text-sm text-slate-200 border-t border-slate-800/60 space-y-3">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                      <span>Quem começa: {{ playerNameById(turn.lead_player_id) }}</span>
                      <span>Trunfo: {{ formatTrumpSuit(parsedGameTurns) }}</span>
                    </div>
                    <div class="space-y-2">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                          <span class="px-2 py-1 rounded-full border border-slate-800 bg-slate-900/60 text-[11px] text-slate-300">J1</span>
                          <span class="font-semibold text-slate-100">{{ playerNameById(player1Id) }}</span>
                        </div>
                        <span class="text-[11px] text-slate-400">Vale {{ turn.player1_card_points ?? 0 }} pts</span>
                      </div>
                      <div class="pl-8 text-slate-200">{{ formatCard(turn.player1_card) }}</div>
                    </div>
                    <div class="space-y-2">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                          <span class="px-2 py-1 rounded-full border border-slate-800 bg-slate-900/60 text-[11px] text-slate-300">J2</span>
                          <span class="font-semibold text-slate-100">{{ playerNameById(player2Id) }}</span>
                        </div>
                        <span class="text-[11px] text-slate-400">Vale {{ turn.player2_card_points ?? 0 }} pts</span>
                      </div>
                      <div class="pl-8 text-slate-200">{{ formatCard(turn.player2_card) }}</div>
                    </div>
                    <div class="pt-1 text-emerald-300 text-xs font-semibold">Vencedor: {{ turnWinnerLabel(turn) }}</div>
                  </div>
                </transition>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-slate-400">Sem informação de turnos neste jogo.</div>
        </div>
      </div>
    </section>

    <Teleport to="body">
      <div
        v-if="showPlayerModal && selectedPlayer"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
        @click.self="closeUserModal"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 shadow-[0_20px_80px_rgba(0,0,0,0.55)] px-6 py-5">
          <div class="flex items-center gap-4">
            <div class="h-14 w-14 rounded-full bg-slate-800 border-2 border-emerald-500/40 overflow-hidden">
              <img :src="selectedUserAvatar" alt="" class="h-full w-full object-cover">
            </div>
            <div class="flex-1">
              <p class="text-lg font-semibold text-white">{{ displayPlayer(selectedUser, selectedUser?.name) }}</p>
              <p class="text-xs text-slate-400">Membro desde {{ formatMemberSince(selectedUser?.created_at) }}</p>
            </div>
          </div>
          <div class="mt-6 flex justify-end">
            <button class="text-slate-200 hover:text-white px-3 py-2" @click="closeUserModal">Fechar</button>
          </div>
        </div>
      </div>
    </Teleport>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, ref, Teleport } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const matchId = computed(() => route.params.id || route.params.matchId || null)
const gameId = computed(() => route.params.gameId)

const loading = ref(false)
const error = ref('')
const match = ref(null)
const standaloneGame = ref(null)
const expandedTurns = ref({})
const showPlayerModal = ref(false)
const selectedPlayer = ref(null)
const selectedUser = computed(() => selectedPlayer.value)
const selectedUserAvatar = computed(() => playerAvatar(selectedPlayer.value))
const viewerId = computed(() => authStore.user?.id ?? null)
const player1Id = computed(() =>
  resolvePlayerId(match.value?.player1, match.value?.player1_user_id) ??
  (game.value ? game.value.player1_user_id : null)
)
const player2Id = computed(() =>
  resolvePlayerId(match.value?.player2, match.value?.player2_user_id) ??
  (game.value ? game.value.player2_user_id : null)
)
const games = computed(() => match.value?.games ?? (standaloneGame.value ? [standaloneGame.value] : []))
const game = computed(() => {
  const found = games.value.find(g => (g.id ?? '').toString() === (gameId.value ?? '').toString())
  return found || standaloneGame.value || null
})
const player1Score = computed(() => Number(game.value?.player1_points ?? 0))
const player2Score = computed(() => Number(game.value?.player2_points ?? 0))
const totalPoints = computed(() => player1Score.value + player2Score.value)
const winnerId = computed(() => resolvePlayerId(game.value?.winner, game.value?.winner_user_id))
const player1UserId = computed(() => match.value?.player1_user_id ?? game.value?.player1_user_id ?? null)
const player2UserId = computed(() => match.value?.player2_user_id ?? game.value?.player2_user_id ?? null)
const isViewerPlayer1 = computed(() => viewerId.value && Number(viewerId.value) === Number(player1UserId.value))
const isViewerPlayer2 = computed(() => viewerId.value && Number(viewerId.value) === Number(player2UserId.value))
const opponentPlayer = computed(() => {
  if (isViewerPlayer1.value) return match.value?.player2 ?? game.value?.player2
  if (isViewerPlayer2.value) return match.value?.player1 ?? game.value?.player1
  return match.value?.player2 ?? game.value?.player2 ?? match.value?.player1 ?? game.value?.player1 ?? null
})
const opponentScore = computed(() => {
  if (isViewerPlayer1.value) return game.value?.player2_points ?? '—'
  if (isViewerPlayer2.value) return game.value?.player1_points ?? '—'
  return game.value?.player2_points ?? game.value?.player1_points ?? '—'
})
const opponentTag = computed(() => (isViewerPlayer1.value ? 'J2' : 'J1'))

const loadData = async () => {
  if (!gameId.value) {
    router.replace({ name: 'player-not-found' })
    return
  }
  loading.value = true
  error.value = ''
  try {
    if (matchId.value) {
      await fetchMatchAndGame()
    } else {
      await fetchStandaloneGame()
    }
  } catch (err) {
    const status = err?.response?.status
    if (status === 403 || status === 404) {
      router.replace({ name: 'player-not-found' })
      return
    }
    error.value = 'Não foi possível carregar o jogo.'
  } finally {
    loading.value = false
  }
}

const fetchMatchAndGame = async () => {
  const { data } = await api.get(`/matches/${matchId.value}`)
  const payload = data?.match ?? data
  if (!payload || !isMyMatch(payload)) {
    router.replace({ name: 'player-not-found' })
    return
  }
  match.value = payload
  if (!game.value) {
    router.replace({ name: 'player-not-found' })
  }
}

const fetchStandaloneGame = async () => {
  const { data } = await api.get(`/games/${gameId.value}`)
  const payload = data?.game ?? data
  if (!payload || !isMyGame(payload)) {
    router.replace({ name: 'player-not-found' })
    return
  }
  standaloneGame.value = payload
  match.value = payload.match ?? null
}

onMounted(() => {
  loadData()
})

const statusBadge = (status) => {
  const value = (status || '').toString().toLowerCase()
  if (value === 'ended' || value === 'e') {
    return { label: 'Terminado', class: 'bg-slate-800 text-slate-200 border-slate-700' }
  }
  if (value === 'progress' || value === 'p' || value === 'playing') {
    return { label: 'A Decorrer', class: 'bg-blue-500/10 text-blue-300 border-blue-500/30' }
  }
  if (value === 'pending') {
    return { label: 'Pendente', class: 'bg-amber-500/10 text-amber-300 border-amber-500/30' }
  }
  return { label: 'Interrompido', class: 'bg-red-500/10 text-red-400 border-red-500/30' }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('pt-PT')
}

const formatDuration = (secondsLike) => {
  if (!secondsLike) return '—'
  const totalSeconds = Number(secondsLike)
  if (Number.isNaN(totalSeconds)) return secondsLike
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = Math.floor(totalSeconds % 60)
  const parts = [
    hours ? `${hours}h` : null,
    minutes ? `${minutes}m` : null,
    `${seconds}s`
  ].filter(Boolean)
  return parts.join(' ')
}

const displayPlayer = (playerObj, fallbackName) => {
  const viewer = viewerId.value
  const pid = resolvePlayerId(playerObj, null)
  if (viewer && pid && Number(viewer) === Number(pid)) return 'Tu'
  return playerObj?.nickname || fallbackName || '—'
}

const playerAvatar = (playerObj) => {
  if (!playerObj) return authStore.userPhotoUrl?.() || ''
  return authStore.userPhotoUrl?.(playerObj.photo_avatar_filename || playerObj.avatar) || authStore.userPhotoUrl?.()
}

const getInitials = (nicknameOrName) => {
  if (!nicknameOrName) return '??'
  return nicknameOrName.substring(0, 2).toUpperCase()
}

const formatMemberSince = (date) => {
  if (!date) return '—'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'
  return parsed.toLocaleDateString('pt-PT', { year: 'numeric', month: 'long' })
}

const resolvePlayerId = (playerObj, explicitId) => playerObj?.id || explicitId || null

const gameWinnerLabelFn = (g) => {
  if (!g) return '—'
  if (g.is_draw || !g.winner_user_id) return 'Empate'
  const p1Id = resolvePlayerId(match.value?.player1 ?? g.player1, match.value?.player1_user_id ?? g.player1_user_id)
  const p2Id = resolvePlayerId(match.value?.player2 ?? g.player2, match.value?.player2_user_id ?? g.player2_user_id)
  if (g.winner_user_id === p1Id) return displayPlayer(match.value?.player1 ?? g.player1, match.value?.player1_name)
  if (g.winner_user_id === p2Id) return displayPlayer(match.value?.player2 ?? g.player2, match.value?.player2_name)
  return displayPlayer(g.winner, g.winner?.nickname || g.winner?.name)
}

const parsedGameTurns = computed(() => {
  if (!game.value?.custom) return []
  try {
    const parsed = typeof game.value.custom === 'string'
      ? JSON.parse(game.value.custom)
      : game.value.custom
    if (Array.isArray(parsed)) return parsed
    if (Array.isArray(parsed?.turns)) return parsed.turns
    if (Array.isArray(parsed?.rounds)) return parsed.rounds
    return []
  } catch (e) {
    return []
  }
})

const toggleTurn = (idx) => {
  expandedTurns.value = { ...expandedTurns.value, [idx]: !expandedTurns.value[idx] }
}

const formatCard = (card) => {
  if (!card) return '—'
  const suit = (card.naipe || card.suit || '').toString().toLowerCase()
  const rawValue = card.valor ?? card.value ?? '?'
  const valueMap = { 1: 'Ás', 11: 'Valete', 12: 'Dama', 13: 'Rei' }
  const value = valueMap[Number(rawValue)] || rawValue
  const suitLabel = { o: 'Ouros', e: 'Espadas', c: 'Copas', p: 'Paus' }[suit] || suit.toUpperCase() || '—'
  return `${value} de ${suitLabel}`
}

const playerNameById = (id) => {
  if (!id) return 'Jogador'
  if (id === player1Id.value) return displayPlayer(match.value?.player1 ?? game.value?.player1, match.value?.player1_name || 'Jogador 1')
  if (id === player2Id.value) return displayPlayer(match.value?.player2 ?? game.value?.player2, match.value?.player2_name || 'Jogador 2')
  return `Jogador ${id}`
}

const formatTrumpSuit = (turns) => {
  const trump = turns?.[0]?.trump_suit || game.value?.trump_suit
  if (!trump) return '—'
  return { o: 'Ouros', e: 'Espadas', c: 'Copas', p: 'Paus' }[trump.toLowerCase()] || trump
}

const turnWinnerLabel = (turn) => {
  if (!turn) return '—'
  if (turn.is_draw || !turn.winner_user_id) return 'Empate'
  return playerNameById(turn.winner_user_id)
}

const closeUserModal = () => {
  showPlayerModal.value = false
}

const openPlayer = (playerObj) => {
  if (!playerObj) return
  selectedPlayer.value = playerObj
  showPlayerModal.value = true
  if (playerObj.id) {
    fetchPlayerDetails(playerObj.id, playerObj)
  }
}

const fetchPlayerDetails = async (id, fallback = {}) => {
  try {
    const { data } = await api.get(`/player/profile/public/${id}`)
    selectedPlayer.value = {
      ...fallback,
      ...data,
      nickname: data.nickname ?? fallback.nickname,
      avatar: data.photo_avatar_filename ?? data.avatar ?? fallback.photo_avatar_filename ?? fallback.avatar ?? null,
      photo_avatar_filename: data.photo_avatar_filename ?? data.avatar ?? fallback.photo_avatar_filename ?? fallback.avatar ?? null
    }
  } catch (e) {
    // ignore fetch error; keep fallback data
  }
}

const isMyMatch = (payload) => {
  const viewer = viewerId.value
  if (!payload || !viewer) return false
  return viewer === payload.player1_user_id || viewer === payload.player2_user_id
}

const isMyGame = (payload) => {
  const viewer = viewerId.value
  if (!payload || !viewer) return false
  return viewer === payload.player1_user_id || viewer === payload.player2_user_id
}

const player1Bar = computed(() => {
  const total = totalPoints.value || 1
  return `${Math.round((player1Score.value / total) * 100)}%`
})
const player2Bar = computed(() => {
  const total = totalPoints.value || 1
  return `${Math.round((player2Score.value / total) * 100)}%`
})
const player1Percent = computed(() => {
  const total = totalPoints.value || 1
  return Math.round((player1Score.value / total) * 100)
})
const player2Percent = computed(() => {
  const total = totalPoints.value || 1
  return Math.round((player2Score.value / total) * 100)
})
const scoreDiffPlayer1 = computed(() => {
  const diff = player1Score.value - player2Score.value
  if (!Number.isFinite(diff)) return '0'
  return diff > 0 ? `+${diff}` : `${diff}`
})
const scoreDiffPlayer2 = computed(() => {
  const diff = player2Score.value - player1Score.value
  if (!Number.isFinite(diff)) return '0'
  return diff > 0 ? `+${diff}` : `${diff}`
})
</script>
