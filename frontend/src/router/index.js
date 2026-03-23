import { createRouter, createWebHistory } from 'vue-router'

import MainView from '@/views/MainView.vue'
import LoginView from '@/views/Auth/LoginView.vue'
import RegisterView from '@/views/Auth/RegisterView.vue'
import VerifyEmailView from '@/views/Auth/VerifyEmailView.vue'
import DeleteAccountConfirmView from '@/views/Auth/DeleteAccountConfirmView.vue'
import ForgotPasswordView from '@/views/Auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/Auth/ResetPasswordView.vue'
import PlayView from '@/views/Gameplay/PlayView.vue'
import OfflineGameView from '@/views/Gameplay/OfflineGameView.vue'
import MultiplayerGameView from '@/views/Gameplay/MultiplayerGameView.vue'
import MultiplayerLobbyView from '@/views/Gameplay/MultiplayerLobbyView.vue'
import LobbyView from '@/views/Lobby/LobbyView.vue'
import ProfileView from '@/views/Profile/ProfileView.vue'
import TransactionsView from '@/views/Profile/TransactionsView.vue'
import MatchHistoryView from '@/views/Profile/MatchesView.vue'
import MatchDetailView from '@/views/Profile/MatchDetailView.vue'
import PlayerGameDetailView from '@/views/Profile/GameDetailView.vue'
import PublicStatsView from '@/views/Stats/PublicStatsView.vue'
import PersonalStatsView from '@/views/Stats/PersonalStatsView.vue'
import PageNotFound from '@/views/PageNotFound.vue'
import { useGameStore } from '@/stores/gameStore'
import { useAuthStore } from '@/stores/auth'

import AdminLayout from '@/layouts/AdminLayout.vue'
import DashboardView from '@/views/Admin/DashboardView.vue'
import UserListView from '@/views/Admin/UserListView.vue'
import AdminListView from '@/views/Admin/AdminListView.vue'
import GlobalTransactionsView from '@/views/Admin/GlobalTransactionsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'main',
      component: MainView,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { requiresGuest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: ForgotPasswordView,
      meta: { requiresGuest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { requiresGuest: true },
    },
    {
      path: '/email/verify/:id/:hash',
      name: 'verify-email',
      component: VerifyEmailView,
    },
    {
      path: '/newAccVerifyEmail',
      name: 'verify-email-query',
      component: VerifyEmailView,
      meta: { legacy: true }
    },
    {
      path: '/verifyEmail',
      name: 'verify-email-link',
      component: VerifyEmailView,
    },
    {
      path: '/account/delete-confirm/:id',
      name: 'account-delete-confirm',
      component: DeleteAccountConfirmView,
    },
    {
      path: '/confirmAccountDelete/:id',
      name: 'account-delete-confirm-alt',
      component: DeleteAccountConfirmView,
    },
    {
      path: '/play',
      name: 'play',
      component: PlayView,
      meta: { requiresPlayer: true },
    },
    {
      path: '/game',
      name: 'game',
      component: OfflineGameView,
      meta: { requiresPlayer: true },
    },
    {
      path: '/game/:gameId',
      name: 'game-online',
      component: MultiplayerGameView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/game/:gameId/lobby',
      name: 'game-online-lobby',
      component: MultiplayerLobbyView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/match/:matchId',
      name: 'match-game',
      component: MultiplayerGameView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/match/:matchId/lobby',
      name: 'match-online-lobby',
      component: MultiplayerLobbyView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/lobby',
      name: 'lobby',
      component: LobbyView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/me',
      name: 'profile',
      component: ProfileView,
      meta: { requiresAuth: true },
    },
    {
      path: '/stats',
      name: 'stats',
      component: PublicStatsView,
    },
    {
      path: '/me/stats',
      name: 'personal-stats',
      component: PersonalStatsView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/me/matches',
      name: 'match-history',
      component: MatchHistoryView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/me/matches/:id',
      name: 'player-match-detail',
      component: MatchDetailView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/me/matches/:id/games/:gameId',
      name: 'player-game-detail',
      component: PlayerGameDetailView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/me/games/:gameId',
      name: 'player-standalone-game-detail',
      component: PlayerGameDetailView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/me/transactions',
      name: 'transactions',
      component: TransactionsView,
      meta: { requiresAuth: true, requiresPlayer: true },
    },
    {
      path: '/leaderboard',
      name: 'leaderboards',
      component: () => import('@/views/LeaderboardView.vue'),
    },
    {
      path: '/not-found',
      name: 'player-not-found',
      component: PageNotFound,
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: DashboardView
        },
        {
          path: 'users',
          name: 'admin-users',
          component: UserListView
        },
        {
          path: 'admins',
          name: 'admin-admins',
          component: AdminListView
        },
        {
          path: 'transactions',
          name: 'admin-transactions',
          component: GlobalTransactionsView
        },
        {
          path: 'matches',
          name: 'globalMatches',
          component: () => import('@/views/Admin/GlobalMatchesView.vue')
        },
        {
          path: 'matches/:id',
          name: 'globalMatchDetail',
          component: () => import('@/views/Admin/GlobalMatchDetailView.vue')
        },
        {
          path: 'matches/:id/games/:gameId',
          name: 'admin-game-detail',
          component: () => import('@/views/Admin/GameDetailView.vue')
        },
        {
          path: 'not-found',
          name: 'admin-not-found',
          component: () => import('@/views/Admin/PageNotFound.vue')
        },
        {
          path: ':pathMatch(.*)*',
          redirect: { name: 'admin-not-found' }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/not-found'
    }
  ],
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  const game = useGameStore()

  if (to.meta?.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta?.requiresAdmin && !auth.isAdmin) {
    return next({ name: 'main' }) // Acesso negado
  }

  if (to.meta?.requiresPlayer && auth.isAdmin) {
    return next({ name: 'admin-dashboard' })
  }

  // Restrict Admin from accessing Game/Play pages explicitly (extra safety)
  if (auth.isAdmin && (to.path.startsWith('/play') || to.path.startsWith('/game') || to.path.startsWith('/lobby'))) {
    return next({ name: 'admin-dashboard' })
  }

  if (to.meta?.requiresGuest && auth.isAuthenticated) {
    return next({ name: 'play' })
  }

  if (to.name === 'play') {
    const offlineActive =
      game.mode === 'offline' &&
      ((game.state && !game.gameEnded) ||
        (game.pendingOfflineState &&
          game.pendingOfflineState.status !== 'Ended' &&
          !game.pendingOfflineState.matchForfeited))

    if (offlineActive) {
      return next({ name: 'game' })
    }

    const onlineActive =
      game.mode === 'online' &&
      (game.gameId || game.matchId) &&
      (!game.state || (game.state?.status !== 'Ended' && !game.state?.matchForfeited))

    if (onlineActive) {
      if (game.sessionMode === 'match' && game.matchId) {
        const targetName = game.state?.status === 'Playing'
          ? 'match-game'
          : 'match-online-lobby'
        return next({ name: targetName, params: { matchId: game.matchId } })
      }
      if (game.gameId) {
        const targetName = game.state?.status === 'Playing'
          ? 'game-online'
          : 'game-online-lobby'
        return next({ name: targetName, params: { gameId: game.gameId } })
      }
    }
  }

  // Se sairmos de um jogo offline em curso, garantir que fica marcado para reconexão
  if (from.name === "game" && to.name !== "game") {
    if (game.mode === "offline" && game.state && !game.gameEnded) {
      game.offlineReconnectAvailable = true
      game.pendingOfflineState = game.state
    }
  }

  next()
})

export default router
