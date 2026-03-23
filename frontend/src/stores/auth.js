import { defineStore } from "pinia";
import api from "@/services/api";
import { usePendingLobbyStore } from "@/stores/pendingLobby";
import { useGameStore } from "@/stores/gameStore";
import { useWsStore } from "@/stores/ws";

const STORAGE_KEY = "auth";

const readAuth = (storage) => {
  try {
    const raw = storage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

const writeAuth = (storage, payload) => {
  try {
    storage.setItem(STORAGE_KEY, JSON.stringify(payload));
  } catch {
    // ignore storage errors
  }
};

const clearAuth = (storage) => {
  try {
    storage.removeItem(STORAGE_KEY);
  } catch {
    // ignore storage errors
  }
};

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    token: null,
    storageScope: null,
    isLoggingOut: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    hasToken: (state) => !!state.token,
    getUser: (state) => state.user,
    isAdmin: (state) => state.user?.type === 'A',
  },
  actions: {
    async cleanupActiveLobby() {
      const pending = usePendingLobbyStore();
      const game = useGameStore();
      const ws = useWsStore();

      const isPlaying = game.state?.status === "Playing";
      const activeGameId =
        (game.mode === "online" && game.gameId && !isPlaying && game.gameId) ||
        pending.pendingGame?.id ||
        pending.readyGameId ||
        null;

      const activeMatchId =
        game.matchId ??
        pending.pendingMatch?.matchId ??
        pending.pendingMatch?.id ??
        null;

      const userId = this.user?.id ?? null;
      const ownerId =
        game.ownerId ??
        pending.pendingMatch?.ownerId ??
        null;
      const isOwner =
        ownerId != null && userId != null && Number(ownerId) === Number(userId);

      const tasks = [];

      if (activeGameId) {
        ws.markSelfLeave(activeGameId);
        tasks.push(
          api.post(`/games/${activeGameId}/leave-lobby`).catch(() => {})
        );
      } else if (activeMatchId && isOwner) {
        tasks.push(
          api.delete(`/matches/${activeMatchId}`).catch(() => {})
        );
      }

      if (tasks.length) {
        await Promise.all(tasks);
      }
    },
    init() {
      const localPayload = readAuth(window.localStorage);
      const sessionPayload = readAuth(window.sessionStorage);
      const payload = localPayload?.token ? { data: localPayload, scope: "local" } :
        sessionPayload?.token ? { data: sessionPayload, scope: "session" } :
        null;

      if (payload) {
        this.token = payload.data.token ?? null;
        this.user = payload.data.user ?? null;
        this.storageScope = payload.scope;
      }

      if (this.token) {
        api.defaults.headers.Authorization = `Bearer ${this.token}`;
      }
    },
    persistSession(scope) {
      if (!this.token || !this.user) return;

      if (scope === "local") {
        writeAuth(window.localStorage, { token: this.token, user: this.user });
        clearAuth(window.sessionStorage);
      } else if (scope === "session") {
        // Guardar em sessionStorage para respeitar o modo "lembrar apenas nesta sessão",
        // mas também espelhar em localStorage para novas abas partilharem o login.
        writeAuth(window.sessionStorage, { token: this.token, user: this.user });
        writeAuth(window.localStorage, { token: this.token, user: this.user });
      }
    },
    async login(email, password, remember = false) {
      try {
        const res = await api.post("/login", { email, password, remember });
        this.token = res.data.token;
        this.user = res.data.user;
        this.storageScope = remember ? "local" : "session";

        api.defaults.headers.Authorization = `Bearer ${this.token}`;
        this.persistSession(this.storageScope);

        return res.data;
      } catch (err) {
        this.token = null;
        this.user = null;
        this.storageScope = null;

        if (err.response) {
          throw new Error(err.response.data?.message || "Credenciais inválidas");
        }

        if (err.request) {
          throw new Error("Erro ao comunicar com o servidor. Por favor, tenta mais tarde.");
        }

        throw new Error("Erro inesperado ao iniciar sessão.");
      }
    },
    async logout() {
      const pending = usePendingLobbyStore();
      const game = useGameStore();
      const ws = useWsStore();

      if (this.isLoggingOut) return;
      this.isLoggingOut = true;

      try {
        if (this.token) {
          await this.cleanupActiveLobby();
          await api.post("/logout");
        }
      } catch {
        // ignorar erros de logout
      } finally {
        ws.ws?.close?.();
        pending.clear?.();
        game.resetGameState?.();
        this.user = null;
        this.token = null;
        this.storageScope = null;
        api.defaults.headers.Authorization = null;
        clearAuth(window.localStorage);
        clearAuth(window.sessionStorage);

        try {
          const { default: router } = await import("@/router");
          router?.push?.({ name: "main" });
        } catch (e) {
          window.location.href = "/";
        }
        this.isLoggingOut = false;
      }
    },
    async fetchUser() {
      if (!this.token) return;

      try {
        const res = await api.get("/profile/self");
        this.user = res.data;
        if (this.storageScope) {
          this.persistSession(this.storageScope);
        }
      } catch (err) {
        if (err?.response?.status === 401) {
          this.logout();
        }
      }
    },
    async refreshBalance() {
      if (!this.token || !this.user) return;
      try {
        const res = await api.get("/coins/balance");
        if (res.data?.coins_balance !== undefined) {
          this.user = { ...(this.user ?? {}), coins_balance: res.data.coins_balance };
          if (this.storageScope) {
            this.persistSession(this.storageScope);
          }
        }
      } catch {
        // ignore balance refresh errors
      }
    },
    updateBalance(newBalance) {
      const numeric = typeof newBalance === "number" ? newBalance : Number(newBalance);
      if (!Number.isFinite(numeric)) return;
      if (!this.user) return;
      // Atualiza o saldo localmente e persiste para manter a UI alinhada ao evento do socket
      this.user = { ...this.user, coins_balance: numeric };
      if (this.storageScope) {
        this.persistSession(this.storageScope);
      }
    },
    async register(formData) {
      const res = await api.post("/register", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      const { token, user } = res.data || {};

      if (token && user) {
        this.token = token;
        this.user = user;
        api.defaults.headers.Authorization = `Bearer ${this.token}`;
      } else {
        this.token = null;
        this.user = null;
        api.defaults.headers.Authorization = null;
      }

      return res.data;
    },
    userPhotoUrl(filename) {
      const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
        .replace(/\/+$/, '')
        .replace(/\/api$/, '')
      const base = apiBase || window.location.origin
      const defaultAvatar = `${base}/api/player/avatar/photos_avatars/anonymous.png`

      if (!filename) return defaultAvatar
      if (/^https?:\/\//i.test(filename)) return filename
      let normalized = filename.startsWith('/') ? filename.slice(1) : filename
      if (!normalized.includes('/')) {
        normalized = `photos_avatars/${normalized}`
      }
      return `${base}/api/player/avatar/${normalized}`
    }
  },
});
