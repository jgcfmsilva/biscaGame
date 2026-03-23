import { defineStore } from "pinia";
import api from "@/services/api";
import { useAuthStore } from "@/stores/auth";

export const usePendingLobbyStore = defineStore("pendingLobby", {
  state: () => ({
    pendingGame: null,
    pendingMatch: null,
    loading: false,
    readyGameId: null,
  }),
  actions: {
    async refresh(isAuthenticated = true) {
      const auth = useAuthStore();
      if (!isAuthenticated || auth.isAdmin) {
        this.pendingGame = null;
        this.pendingMatch = null;
        return null;
      }
      if (this.loading) return this.pendingGame;
      this.loading = true;
      try {
        const { data } = await api.get("/games/pending-mine");
        this.pendingGame = data.game ? { ...data.game, kind: "game" } : null;
        return this.pendingGame;
      } catch (err) {
        // ignora 401/403 ou erros de rede breves
        return this.pendingGame;
      } finally {
        this.loading = false;
      }
    },
    setPending(game) {
      this.pendingGame = game ? { ...game, kind: "game" } : null;
      this.readyGameId = null;
    },
    setPendingMatch(match) {
      this.pendingMatch = match
        ? {
            ...match,
            kind: "match",
            matchId: match.matchId ?? match.id,
            ownerId: match.ownerId ?? match.player1_user_id ?? match.player1?.id ?? null,
          }
        : null;
    },
    clearMatch() {
      this.pendingMatch = null;
    },
    clear() {
      this.pendingGame = null;
      this.pendingMatch = null;
      this.readyGameId = null;
    },
    handleReady(game) {
      const targetId = game?.id ?? this.pendingGame?.id ?? null;
      if (targetId && this.pendingGame && this.pendingGame.id === targetId) {
        this.pendingGame = null;
        this.readyGameId = targetId;
      } else if (this.pendingMatch && this.pendingMatch.id === game?.matchId) {
        // Match continua pendente, não redirecionar automaticamente
      }
    },
  },
});
