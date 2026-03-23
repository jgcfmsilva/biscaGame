<template>
  <DefaultLayout>
    <section class="mx-auto max-w-6xl px-4 py-10 text-[15px] md:text-[17px] space-y-6">
      <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex items-center gap-3">
          <button
            class="px-3 py-2 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 hover:bg-slate-800 transition-colors"
            @click="$router.back()"
          >
            Voltar
          </button>
          <h2 class="text-3xl font-bold text-white">Partida #{{ matchId }}</h2>
          <span v-if="match" :class="statusBadge(match.status).class" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
            {{ statusBadge(match.status).label }}
          </span>
        </div>
        <div v-if="match" class="flex items-center gap-3 text-sm text-slate-400">
          <span>Início: <span class="text-slate-200 font-medium">{{ formatDate(match.created_at || match.began_at) }}</span></span>
          <span class="hidden md:inline">·</span>
          <span>Fim: <span class="text-slate-200 font-medium">{{ formatDate(match.ended_at) }}</span></span>
        </div>
      </div>

      <div v-if="loading" class="text-center py-6 text-slate-400">A carregar partida...</div>
      <div v-if="error" class="text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded">{{ error }}</div>

      <div v-if="match" class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-3">
          <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-5 space-y-4 shadow-[0_20px_50px_rgba(15,23,42,0.35)]">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-lg font-semibold text-slate-100">Visão geral</p>
                <p class="text-xs text-slate-500">Início {{ formatDate(match.began_at || match.created_at) }}</p>
              </div>
              <div class="flex items-center gap-2 text-xs text-slate-400">
                <span>ID #{{ match.id }}</span>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Stake</p>
                <p class="text-lg font-semibold">{{ match.stake ?? '—' }}</p>
              </div>
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Duração</p>
                <p class="text-lg font-semibold">{{ formatDuration(match.total_time) }}</p>
              </div>
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Estado</p>
                <p class="text-lg font-semibold">{{ statusBadge(match.status).label }}</p>
              </div>
              <div class="rounded-lg border border-slate-800/60 bg-slate-950/60 px-3 py-2">
                <p class="text-xs text-slate-500">Jogos nesta match</p>
                <p class="text-lg font-semibold">{{ gamesCount }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
            <h3 class="text-lg font-semibold text-slate-100">Adversário</h3>
            <div class="grid gap-3">
              <button
                type="button"
                class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 shadow-inner shadow-slate-900/40 text-left hover:border-emerald-500/40 hover:-translate-y-[1px] transition"
                @click="opponentPlayer && openPlayer(opponentPlayer)"
              >
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold text-slate-100">{{ displayPlayer(opponentPlayer, opponentPlayer?.nickname) }}</p>
                  <span class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full border bg-slate-800 text-slate-300 border-slate-700">
                    {{ opponentTag }}
                  </span>
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
                {{ displayPlayer(match.winner, match.winner_name) }}
              </span>
            </div>
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-5">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-lg font-semibold text-slate-100">Pontuação</h3>
                <p class="text-xs text-slate-500">Distribuição de pontos na partida</p>
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
                  <span>{{ displayPlayer(match.player1, match.player1_name) }}</span>
                  <span v-if="winnerId === resolvePlayerId(match.player1, match.player1_user_id)" class="text-emerald-300 font-semibold">Vencedor</span>
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
                  <span>{{ displayPlayer(match.player2, match.player2_name) }}</span>
                  <span v-if="winnerId === resolvePlayerId(match.player2, match.player2_user_id)" class="text-blue-300 font-semibold">Vencedor</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-100">Jogos desta match</h3>
            <span class="text-xs text-slate-500">{{ filteredGames.length ? `${filteredGames.length} jogos` : 'Sem jogos' }}</span>
          </div>
          <div class="flex flex-wrap items-center gap-3 bg-slate-950/40 border border-slate-800/60 rounded-lg p-3 text-base md:text-lg">
            <div class="relative w-full md:w-64">
              <input
                v-model="gameSearch"
                type="text"
                placeholder="Pesquisar por ID, vencedor..."
                class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500"
              />
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 text-xs">🔍</span>
            </div>
            <select v-model="gameStatusFilter" class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500">
              <option value="">Todos os estados</option>
              <option v-for="status in gameStatusOptions" :key="status" :value="status">{{ status }}</option>
            </select>
            <select v-model.number="gamePerPage" class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-blue-500">
              <option :value="5">5 / pág</option>
              <option :value="10">10 / pág</option>
              <option :value="20">20 / pág</option>
            </select>
            <button
              @click="clearGameFilters"
              class="px-3 py-2 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 hover:bg-slate-800 transition-colors"
            >
              Limpar filtros
            </button>
            <div class="text-xs text-slate-500 ml-auto">
              Página {{ gamePage }} de {{ gameLastPage }} · {{ filteredGames.length }} jogos encontrados
            </div>
          </div>
          <div v-if="paginatedGames.length" class="overflow-x-auto">
            <table class="min-w-full text-lg md:text-xl text-slate-200">
              <thead class="text-[13px] md:text-[14px] uppercase tracking-wide text-slate-300 bg-slate-950/40">
                <tr>
                  <th class="px-3 py-2 text-left">Jogo</th>
                  <th class="px-3 py-2 text-left">ID</th>
                  <th class="px-3 py-2 text-left">Pontuação</th>
                  <th class="px-3 py-2 text-left">Vencedor</th>
                  <th class="px-3 py-2 text-left">Status</th>
                  <th class="px-3 py-2 text-left">Data</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(game, gameIndex) in paginatedGames"
                  :key="game.id || gameIndex"
                  class="border-t border-slate-800/60 odd:bg-slate-900/40 hover:bg-slate-800/60 cursor-pointer transition-colors"
                  @click="goToGamePage(game)"
                >
                  <td class="px-3 py-3">
                    <div class="font-semibold text-slate-100">Jogo {{ (gamePage - 1) * gamePerPage + gameIndex + 1 }}</div>
                  </td>
                  <td class="px-3 py-3">
                    <div class="text-slate-100 font-semibold">#{{ game.id ?? '—' }}</div>
                  </td>
                  <td class="px-3 py-3">
                    <div class="text-slate-100 font-medium">{{ game.player1_points ?? 0 }} - {{ game.player2_points ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400">J1 · J2</div>
                  </td>
                  <td class="px-3 py-3">
                    <span :class="gameWinnerClassFn(game)" class="font-medium">
                      {{ gameWinnerLabelFn(game) }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-base md:text-lg">
                    <span class="px-2 py-1 text-[11px] rounded-full border border-slate-800 bg-slate-900/60 text-slate-300">
                      {{ game.status || '—' }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-slate-300 text-base md:text-lg">
                    <div class="text-sm md:text-base text-slate-200">{{ formatDate(game.began_at) }}</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-sm text-slate-400">Ainda não há jogos registados para esta partida.</div>
          <div class="flex justify-between items-center pt-2 text-sm text-slate-300" v-if="filteredGames.length">
            <button
              @click="prevGamePage"
              :disabled="gamePage <= 1"
              class="px-3 py-2 border border-slate-700 rounded bg-slate-900 disabled:opacity-50 hover:bg-slate-800"
            >
              Anterior
            </button>
            <span class="text-slate-500">Página {{ gamePage }} de {{ gameLastPage }}</span>
            <button
              @click="nextGamePage"
              :disabled="gamePage >= gameLastPage"
              class="px-3 py-2 border border-slate-700 rounded bg-slate-900 disabled:opacity-50 hover:bg-slate-800"
            >
              Próximo
            </button>
          </div>
        </div>
      </div>

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
              <Button variant="ghost" class="text-slate-200 hover:text-white" @click="closeUserModal">Fechar</Button>
            </div>
          </div>
        </div>
      </Teleport>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { onMounted, ref, computed, Teleport, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'

const route = useRoute()
const authStore = useAuthStore()
const router = useRouter()
const matchId = computed(() => route.params.id)

const loading = ref(false)
const error = ref('')
const match = ref(null)
const showPlayerModal = ref(false)
const selectedPlayer = ref(null)
const selectedUser = computed(() => selectedPlayer.value)
const selectedUserAvatar = computed(() => playerAvatar(selectedPlayer.value))
const player1Score = computed(() => Number(match.value?.player1_points ?? 0))
const player2Score = computed(() => Number(match.value?.player2_points ?? 0))
const totalPoints = computed(() => player1Score.value + player2Score.value)
const games = computed(() => match.value?.games ?? [])
const gamesCount = computed(() => games.value.length)
const winnerId = computed(() => resolvePlayerId(match.value?.winner, match.value?.winner_user_id))
const viewerId = computed(() => authStore.user?.id ?? null)
const isViewerPlayer1 = computed(() => viewerId.value && viewerId.value === match.value?.player1_user_id)
const isViewerPlayer2 = computed(() => viewerId.value && viewerId.value === match.value?.player2_user_id)
const opponentPlayer = computed(() => {
  if (isViewerPlayer1.value) return match.value?.player2
  if (isViewerPlayer2.value) return match.value?.player1
  return match.value?.player2 || match.value?.player1 || null
})
const opponentScore = computed(() => {
  if (isViewerPlayer1.value) return match.value?.player2_points ?? '—'
  if (isViewerPlayer2.value) return match.value?.player1_points ?? '—'
  return match.value?.player2_points ?? match.value?.player1_points ?? '—'
})
const opponentTag = computed(() => (isViewerPlayer1.value ? 'J2' : 'J1'))

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

const formatMemberSince = (date) => {
  if (!date) return '—'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'
  return parsed.toLocaleDateString('pt-PT', { year: 'numeric', month: 'long' })
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

const resolvePlayerId = (playerObj, explicitId) => playerObj?.id || explicitId || null
const gameSearch = ref('')
const gameStatusFilter = ref('')
const gamePerPage = ref(10)
const gamePage = ref(1)

const gameStatusOptions = ['Pending', 'Playing', 'Ended', 'Interrupted']

const filteredGames = computed(() => {
  const term = gameSearch.value.trim().toLowerCase()
  return games.value
    .filter((game) => {
      if (gameStatusFilter.value) {
        if ((game.status || '').toLowerCase() !== gameStatusFilter.value.toLowerCase()) {
          return false
        }
      }
      if (!term) return true
      const winnerLabel = gameWinnerLabelFn(game).toLowerCase()
      const idMatch = game.id?.toString().includes(term)
      const scoreMatch = `${game.player1_points ?? 0}-${game.player2_points ?? 0}`.includes(term)
      return winnerLabel.includes(term) || idMatch || scoreMatch
    })
})

const gameLastPage = computed(() => {
  return Math.max(1, Math.ceil(filteredGames.value.length / gamePerPage.value))
})

const paginatedGames = computed(() => {
  const start = (gamePage.value - 1) * gamePerPage.value
  return filteredGames.value.slice(start, start + gamePerPage.value)
})

const clearGameFilters = () => {
  gameSearch.value = ''
  gameStatusFilter.value = ''
  gamePerPage.value = 10
  gamePage.value = 1
}

const prevGamePage = () => {
  if (gamePage.value > 1) gamePage.value -= 1
}

const nextGamePage = () => {
  if (gamePage.value < gameLastPage.value) gamePage.value += 1
}

watch([gameSearch, gameStatusFilter, gamePerPage], () => {
  gamePage.value = 1
})

watch(gameLastPage, (newLast) => {
  if (gamePage.value > newLast) {
    gamePage.value = newLast
  }
})

const goToGamePage = (game) => {
  if (!game?.id) return
  router.push({ name: 'player-game-detail', params: { id: matchId.value, gameId: game.id } })
}

const gameWinnerLabelFn = (game) => {
  if (!game) return '—'
  if (game.is_draw || !game.winner_user_id) return 'Empate'
  const p1Id = resolvePlayerId(match.value?.player1, match.value?.player1_user_id)
  const p2Id = resolvePlayerId(match.value?.player2, match.value?.player2_user_id)
  if (game.winner_user_id === p1Id) return displayPlayer(match.value?.player1, match.value?.player1_name)
  if (game.winner_user_id === p2Id) return displayPlayer(match.value?.player2, match.value?.player2_name)
  return displayPlayer(game.winner, game.winner?.nickname || game.winner?.name)
}

const gameWinnerClassFn = (game) => {
  if (!game || game.is_draw || !game.winner_user_id) return 'text-slate-300'
  const p1Id = resolvePlayerId(match.value?.player1, match.value?.player1_user_id)
  const p2Id = resolvePlayerId(match.value?.player2, match.value?.player2_user_id)
  if (game.winner_user_id === p1Id) return 'text-emerald-300'
  if (game.winner_user_id === p2Id) return 'text-blue-300'
  return 'text-slate-100'
}

const playerAvatar = (playerObj) => {
  if (!playerObj) return authStore.userPhotoUrl?.() || ''
  return authStore.userPhotoUrl?.(playerObj.photo_avatar_filename || playerObj.avatar) || authStore.userPhotoUrl?.()
}

const getInitials = (nicknameOrName) => {
  if (!nicknameOrName) return '??'
  return nicknameOrName.substring(0, 2).toUpperCase()
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

const isMyMatch = (payload) => {
  const viewer = viewerId.value
  if (!payload || !viewer) return false
  return viewer === payload.player1_user_id || viewer === payload.player2_user_id
}

const loadMatch = async () => {
  if (!matchId.value) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/matches/${matchId.value}`)
    const payload = data?.match ?? data
    if (!payload || !isMyMatch(payload)) {
      router.replace({ name: 'player-not-found' })
      return
    }
    match.value = payload
  } catch (err) {
    const status = err?.response?.status
    if (status === 403 || status === 404) {
      router.replace({ name: 'player-not-found' })
      return
    }
    error.value = 'Não foi possível carregar a partida.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMatch()
})

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
