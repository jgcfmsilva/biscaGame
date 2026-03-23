import { defineStore } from "pinia";
import { useGameStore } from "./gameStore";
import { useAuthStore } from "./auth";
import { usePendingLobbyStore } from "./pendingLobby";
import { toast } from "vue-sonner";
import router from "@/router";

export const useWsStore = defineStore("ws", {
  state: () => ({
    ws: null,
    connected: false,
    authenticated: false,
    token: null,

    reconnectInterval: 2000,
    maxReconnectInterval: 30000,

    messageQueue: [],

    heartbeatInterval: null,
    heartbeatTimeout: null,
    lastPingMs: null,
    lastPingAt: null,
    selfLeavingRoomIds: [],
  }),

  actions: {
    markSelfLeave(roomId) {
      if (roomId == null) return;
      const id = Number(roomId);
      if (!Number.isFinite(id)) return;
      if (!this.selfLeavingRoomIds.includes(id)) {
        this.selfLeavingRoomIds.push(id);
      }
    },
    consumeSelfLeave(roomId) {
      if (roomId == null) return false;
      const id = Number(roomId);
      if (!Number.isFinite(id)) return false;
      const idx = this.selfLeavingRoomIds.indexOf(id);
      if (idx === -1) return false;
      this.selfLeavingRoomIds.splice(idx, 1);
      return true;
    },

    connect(token) {
      this.token = token;
      if (!token) return;

      if (this.ws && this.ws.readyState === WebSocket.OPEN) return;

      const wsUrl =
        import.meta.env.VITE_WS_URL ||
        `${window.location.protocol === "https:" ? "wss" : "ws"}://${window.location.host}/ws`;

      try {
        this.ws = new WebSocket(wsUrl);
      } catch (e) {
        console.error("Falha a iniciar WebSocket:", e);
        toast.error("Erro a ligar ao servidor em tempo-real. Verifica o endereço WS.");
        return;
      }
      this.ws.binaryType = "arraybuffer";

      this.ws.onopen = () => {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
          console.warn("WS abriu mas ficou inválido/fechado imediatamente.");
          return;
        }
        this.connected = true;
        this.authenticated = false;

        // reset backoff
        this.reconnectInterval = 2000;

        const gameStore = useGameStore();
        gameStore.handleRealtimeReconnect?.();

        try {
          this.ws.send(JSON.stringify({ type: "auth", token }));
        } catch (err) {
          console.error("Erro ao enviar auth no WS:", err);
          return;
        }
        this.startHeartbeat();
      };

      this.ws.onmessage = (event) => {
        const msg = JSON.parse(event.data);
        const auth = useAuthStore();

        if (auth?.isLoggingOut) {
          return;
        }

        if (msg.type === "pong") {
          this.resetHeartbeatTimeout();
          if (msg.clientTs) {
            const rtt = Date.now() - msg.clientTs;
            if (Number.isFinite(rtt)) {
              this.lastPingMs = Math.max(0, Math.round(rtt));
              this.lastPingAt = Date.now();
            }
          }
          return;
        }

        if (msg.type === "auth_ok") {
          this.authenticated = true;
          const gameStore = useGameStore();
          if (gameStore.mode === "online" && gameStore.gameId) {
            this.send({
              type: "join_game",
              gameId: gameStore.gameId,
            });
          }
          if (gameStore.mode === "online" && gameStore.pendingNextGameId) {
            this.send({
              type: "join_game",
              gameId: gameStore.pendingNextGameId,
            });
          }
          this.flushQueue();
          return;
        }

        if (msg.type === "auth_error") {
          console.warn("🔴 WS Auth failed:", msg.message);
          this.stopHeartbeat();
          this.connected = false;
          this.authenticated = false;
          this.ws.close();
          if (auth.hasToken) {
            auth.fetchUser?.().catch(() => auth.logout());
          }
          return;
        }

        if (msg.type === "state_update") {
          const gameStore = useGameStore();
          gameStore.updateState(msg.state);
        }

        if (msg.type === "error") {
          console.warn("WS error:", msg.message || msg);
          if (msg.message) {
            toast.error(msg.message);
          }
          return;
        }

        if (msg.type === "ready_state") {
          const gameStore = useGameStore();
          // Usar gameId como roomId se disponível, caso contrário usar roomId
          const roomId = msg.gameId ?? msg.roomId;
          gameStore.applyReadyState?.(
            roomId,
            msg.userId,
            msg.ready,
            msg.matchId ?? null
          );
          return;
        }

        if (msg.type === "lobby_reset") {
          const gameStore = useGameStore();
          const pendingStore = usePendingLobbyStore();
          const selfMarked = this.consumeSelfLeave(msg.roomId ?? msg.gameId);
          if (msg.reason === "cancelled" || msg.reason === "insufficient_balance") {
            gameStore.resetGameState();
            pendingStore.clear();
            if (router.currentRoute.value?.name !== "play") {
              router.push({ name: "play" });
            }
            const message =
              msg.reason === "insufficient_balance"
                ? "Lobby cancelado por saldo insuficiente."
                : "Lobby cancelado pelo criador.";
            toast.info(message, { id: "lobby-cancelled" });
            return;
          }
          const hadOpponent = gameStore.handleLobbyReset(msg.roomId);
          const selfId = auth.user?.id ?? null;
          const actorId = msg.userId != null ? Number(msg.userId) : null;
          const isSelf = selfMarked || (selfId != null && actorId != null && Number(selfId) === actorId);
          if (isSelf) {
            // Se fomos nós a sair (ou disconnect auto), limpar o estado para não tentar reentrar
            pendingStore.clear();
            gameStore.resetGameState();
            if (router.currentRoute.value?.name !== "play") {
              router.push({ name: "play" });
            }
            return;
          }
          if (hadOpponent && !isSelf) {
            toast.info("O adversário saiu do lobby.");
          }
          if (!isSelf && gameStore.mode === "online" && gameStore.gameId && this.connected) {
            // Reafirma presença no lobby para garantir que o socket fica na sala (apenas para quem permanece).
            this.send({ type: "join_game", gameId: gameStore.gameId });
          }
        }

        if (msg.type === "lobby_log_append") {
          const gameStore = useGameStore();
          if (msg.entry) {
            gameStore.appendLobbyLog(msg.entry);
          }
        }

        if (msg.type === "lobby_kicked") {
          const auth = useAuthStore();
          const gameStore = useGameStore();
          const pendingStore = usePendingLobbyStore();
          const localMeId =
            auth.user?.id ?? gameStore.playerNames?.meId ?? gameStore.state?.me?.id ?? null;
          const isTargetUser =
            localMeId != null && Number(localMeId) === Number(msg.userId);
          if (isTargetUser) {
            pendingStore.clear();
            gameStore.resetGameState();
            toast.error("Foste expulso do lobby.", { id: "lobby-kicked" });
            if (router.currentRoute.value?.name !== "lobby") {
              router.push({ name: "lobby" });
            }
          }
          return;
        }

        if (msg.type === "pending_ready") {
          const pendingStore = usePendingLobbyStore();
          pendingStore.handleReady(msg.game ?? { id: msg.gameId });
        }

        if (msg.type === "match_next_game") {
          const gameStore = useGameStore();
          if (!msg.matchId) {
            return;
          }
          gameStore.sessionMode = "match";
          gameStore.matchId = msg.matchId;
          if (msg.gameId) {
            const wasUnset = !gameStore.pendingNextGameId;
            const changed = gameStore.pendingNextGameId !== msg.gameId;
            gameStore.pendingNextGameId = msg.gameId;
            this.send({
              type: "join_game",
              gameId: msg.gameId,
            });
            if ((wasUnset || changed) && gameStore.state) {
              gameStore.state = {
                ...gameStore.state,
                ready: { me: false, opponent: false },
                readyPlayers: [],
              };
            }
          }
          gameStore.pendingNextReady = { me: false, opponent: false };
          return;
        }

        if (msg.type === "lobby_active_update") {
          window.dispatchEvent(new CustomEvent("lobby_active_update"));
        }

        if (msg.type === "balance_update") {
          const auth = useAuthStore();
          if (msg.coins_balance !== undefined) {
            auth.updateBalance?.(msg.coins_balance);
          }
        }

        if (msg.type === 'video_signal') {
          // Dispatch global event for video chat components
          window.dispatchEvent(new CustomEvent('video-signal', {
            detail: msg // { type: 'video_signal', senderId, senderName, payload: {...} }
          }))
        }

        if (msg.type === 'video_signal_error') {
          // Apenas emite evento; o componente gere os toasts para evitar duplicados
          window.dispatchEvent(new CustomEvent('video-signal-error', {
            detail: msg
          }))
        }
      };

      this.ws.onclose = (ev) => {
        this.ws = null;
        this.connected = false;
        this.authenticated = false;
        this.stopHeartbeat();

        const gameStore = useGameStore();
        const reason =
          ev.code === 1006
            ? "Não foi possível ligar ao servidor em tempo-real. Verifica a tua ligação e tenta novamente."
            : "Ligação em tempo-real perdida. Estamos a tentar reconectar...";
        gameStore.handleRealtimeDisconnect?.(reason);

        if ([1008, 4000, 4001].includes(ev.code)) return;

        this.reconnect();
      };

      this.ws.onerror = () => this.ws.close();
    },

    reconnect() {
      if (this.connected || !this.token) return;

      setTimeout(() => {
        this.connect(this.token);
        this.reconnectInterval = Math.min(
          this.reconnectInterval * 1.5,
          this.maxReconnectInterval
        );
      }, this.reconnectInterval);
    },

    startHeartbeat() {
      this.heartbeatInterval = setInterval(() => {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) return;

        const clientTs = Date.now();
        try {
          this.ws.send(JSON.stringify({ type: "ping", clientTs }));
        } catch (err) {
          console.error("Erro ao enviar ping:", err);
          this.ws?.close();
          return;
        }

        this.heartbeatTimeout = setTimeout(() => {
          console.warn("💀 Heartbeat timeout — reconnecting...");
          this.ws?.close();
        }, 12000);
      }, 5000);
    },

    resetHeartbeatTimeout() {
      clearTimeout(this.heartbeatTimeout);
      this.heartbeatTimeout = null;
    },

    stopHeartbeat() {
      clearInterval(this.heartbeatInterval);
      clearTimeout(this.heartbeatTimeout);

      this.heartbeatInterval = null;
      this.heartbeatTimeout = null;
    },

    send(payload) {
      if (payload?.type !== "auth" && !this.authenticated) {
        this.messageQueue.push(payload);
        return;
      }

      if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
        this.messageQueue.push(payload);
        return;
      }

      this.ws.send(JSON.stringify(payload));
    },

    flushQueue() {
      while (this.messageQueue.length && this.ws.readyState === WebSocket.OPEN) {
        const msg = this.messageQueue.shift();
        this.ws.send(JSON.stringify(msg));
      }
    },
  },
});
