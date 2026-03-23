import { defineStore } from "pinia";
import api from "@/services/api";
import { useWsStore } from "./ws";
import { useAuthStore } from "./auth";
import { toast } from "vue-sonner";

const WAITING_PLAYERS_STATUS = "WaitingPlayers";
const WAITING_READY_STATUS = "WaitingReady";

const DEFAULT_PLAYER_NAMES = Object.freeze({
  me: null,
  opponent: null,
  meId: null,
  opponentId: null,
});

const DEFAULT_MATCH_MARKS = Object.freeze({ me: 0, opponent: 0 });
const DEFAULT_LAST_PLAYED = Object.freeze({ player1: null, player2: null });
const MIN_TURN_DURATION_MS = 20000;

const clampMarks = (value) => {
  const n = Number(value);
  if (!Number.isFinite(n)) return 0;
  return Math.max(0, Math.min(4, n));
};

const marksForPoints = (points) => {
  if (!Number.isFinite(points)) return 0;
  if (points >= 120) return 3;
  if (points >= 91) return 2;
  if (points >= 61) return 1;
  return 0;
};

const resultModalFromGameRecord = (game, viewerId, gameNumber = null) => {
  if (!game || !viewerId) return null;
  const p1Id = game.player1_user_id ?? game.player1?.id ?? null;
  const p2Id = game.player2_user_id ?? game.player2?.id ?? null;
  if (!p1Id || !p2Id) return null;
  const meIsP1 = Number(viewerId) === Number(p1Id);
  const meId = meIsP1 ? p1Id : p2Id;
  const opponentId = meIsP1 ? p2Id : p1Id;
  const p1Points = game.player1_points ?? 0;
  const p2Points = game.player2_points ?? 0;
  const isDraw = Number(p1Points) === Number(p2Points);
  const winner =
    isDraw ? null : (p1Points > p2Points ? (meIsP1 ? "me" : "opponent") : (meIsP1 ? "opponent" : "me"));
  const loser =
    isDraw ? null : (winner === "me" ? "opponent" : "me");

  const marksAwardedRaw = game.custom?.marks_awarded;
  const p1Marks = Array.isArray(marksAwardedRaw)
    ? Number(marksAwardedRaw[0] ?? 0)
    : Number(marksAwardedRaw?.p1 ?? marksAwardedRaw?.me ?? 0);
  const p2Marks = Array.isArray(marksAwardedRaw)
    ? Number(marksAwardedRaw[1] ?? 0)
    : Number(marksAwardedRaw?.p2 ?? marksAwardedRaw?.opponent ?? 0);

  const computedP1Marks = Number.isFinite(p1Marks) && Number.isFinite(p2Marks)
    ? p1Marks
    : marksForPoints(p1Points);
  const computedP2Marks = Number.isFinite(p1Marks) && Number.isFinite(p2Marks)
    ? p2Marks
    : marksForPoints(p2Points);

  const mePoints = meIsP1 ? p1Points : p2Points;
  const opponentPoints = meIsP1 ? p2Points : p1Points;
  const meMarks = meIsP1 ? computedP1Marks : computedP2Marks;
  const opponentMarks = meIsP1 ? computedP2Marks : computedP1Marks;

  return {
    winner,
    loser,
    isDraw,
    me: { id: meId, score: mePoints, marks: meMarks },
    opponent: { id: opponentId, score: opponentPoints, marks: opponentMarks },
    mePoints,
    opponentPoints,
    marks: { me: meMarks, opponent: opponentMarks },
    gameNumber, // Guardar o número do jogo no modal
  };
};

const createInitialState = () => ({
  mode: null, // "offline" | "online"
  hydrationComplete: false,
  gameId: null, // multiplayer ID
  matchId: null,
  matchStake: null,
  ownerId: null,
  entryStake: 2,
  serverStatus: null,
  state: null, // publicState vindo do backend
  loading: false,
  waitingForOpponent: false,
  playerNames: { ...DEFAULT_PLAYER_NAMES },
  playerAvatars: { me: null, opponent: null },
  sessionMode: "game", // "game" (único) | "match" (série)
  matchGameNumber: 1,
  currentVariant: null, // "3" | "9" último tipo escolhido
  matchMarks: { ...DEFAULT_MATCH_MARKS },
  lastPlayedCards: { ...DEFAULT_LAST_PLAYED },
  pendingScoreUpdate: null,
  pendingScoreTimeout: null,
  lastPlayedClearTimeout: null,
  botActing: false,
  lastOpponentHandSize: 0,
  turnTimerRound: null,
  turnTimerStartedAt: null,
  turnTimerDurationMs: 20000,
  turnTimerFrozen: false,
  turnTimerOverride: false,
  turnTimerResolutionFrozen: false,
  turnTimerUnfreezeTimeout: null,
  trumpCardSnapshot: null,
  resultModal: null,
  pendingNextGameId: null,
  pendingNextReady: { me: false, opponent: false },
  offlineReconnectAvailable: false,
  pendingOfflineState: null,
  offlineReconnectChecked: false,
  realtimeError: null,
  lobbyLog: [],
});

const isMatchSessionState = (state) =>
  state?.sessionMode === "match" || Boolean(state?.matchId) || state?.matchGameNumber != null;

const normalizeReadyState = (incomingState, meId, opponentId) => {
  const status = incomingState?.status ?? null;
  // Não herdar estados "ready" de jogos já terminados/interrompidos
  if (incomingState?.matchForfeited || status === "Ended" || status === "Interrupted") {
    return { me: false, opponent: false };
  }

  const readyFromState = incomingState?.ready;
  if (readyFromState && typeof readyFromState === "object") {
    return {
      me: Boolean(readyFromState.me),
      opponent: Boolean(readyFromState.opponent),
    };
  }

  if (!Array.isArray(incomingState?.readyPlayers)) return null;

  const readyPlayers = incomingState.readyPlayers
    .map((id) => Number(id))
    .filter((id) => Number.isFinite(id));

  return {
    me: meId != null ? readyPlayers.includes(Number(meId)) : false,
    opponent: opponentId != null ? readyPlayers.includes(Number(opponentId)) : false,
  };
};

const computeWaitingForOpponent = (mode, state, meId, opponentId) => {
  if (mode !== "online") return false;

  const status = state?.status;
  const hasOpponent = opponentId && opponentId !== meId;
  const waitingFlag = (state?.custom?.waiting_for_opponent ?? false) === true;

  return status === WAITING_PLAYERS_STATUS || (waitingFlag && !hasOpponent);
};

const shouldShowResultModal = (state) =>
  state?.status === "Ended" || state?.matchForfeited;

const toResultModal = (state) => ({
  winner: state?.winner,
  loser: state?.loser,
  isDraw: state?.is_draw,
  me: state?.me,
  opponent: state?.opponent,
  mePoints: state?.me?.score ?? 0,
  opponentPoints: state?.opponent?.score ?? 0,
  marks: (() => {
    if (state?.matchForfeited) {
      // Numa desistência o resultado da partida é sempre 4-0
      const winnerIsMe = state?.winner === "me";
      return winnerIsMe
        ? { me: 4, opponent: 0 }
        : { me: 0, opponent: 4 };
    }
    return {
      me: state?.me?.marks ?? state?.marks?.me ?? 0,
      opponent: state?.opponent?.marks ?? state?.marks?.opponent ?? 0,
    };
  })(),
});

const normalizeLobbyLog = (entries) => {
  if (!Array.isArray(entries)) return [];
  const seenKeys = new Set();
  const normalized = [];
  for (const rawEntry of entries) {
    if (!rawEntry || typeof rawEntry !== "object") continue;
    const entry = {
      action: rawEntry.action ?? null,
      userId: rawEntry.userId ?? null,
      nickname: rawEntry.nickname ?? null,
      at: rawEntry.at ?? null,
    };
    const key = `${entry.action ?? ""}-${entry.userId ?? ""}`;
    if (seenKeys.has(`${entry.at ?? ""}-${key}`)) continue;
    seenKeys.add(key);
    seenKeys.add(`${entry.at ?? ""}-${key}`);
    normalized.push(entry);
  }
  if (normalized.length > 50) {
    return normalized.slice(-50);
  }
  return normalized;
};

export const useGameStore = defineStore("game", {
  state: () => createInitialState(),
  getters: {
    me: (s) => s.state?.me ?? null,
    opponent: (s) => s.state?.opponent ?? null,
    currentTurn: (s) => s.state?.currentTurn ?? null,
    trumpCard: (s) => s.state?.trumpCard ?? s.trumpCardSnapshot ?? null,
    deckCount: (s) => s.state?.deckCount ?? 0,
    round: (s) => s.state?.round ?? 1,
    gameEnded: (s) => s.state?.status === "Ended" || s.state?.matchForfeited,
  },
  actions: {
    ready() {
      const readyFromState = this.state?.ready;
      if (readyFromState && typeof readyFromState === "object") {
        return {
          me: Boolean(readyFromState.me),
          opponent: Boolean(readyFromState.opponent),
        };
      }

      if (!this.state || !this.state.readyPlayers) {
        return { me: false, opponent: false };
      }

      const meId = this.playerNames?.meId;
      const opponentId = this.playerNames?.opponentId;

      return {
        me: meId != null ? this.state.readyPlayers.includes(meId) : false,
        opponent: opponentId != null ? this.state.readyPlayers.includes(opponentId) : false,
      };
    },
    updateState(newState) {
      const initialHydration = this.hydrationComplete === false;
      const prevMeScore = this.state?.me?.score ?? null;
      const prevOpponentScore = this.state?.opponent?.score ?? null;
      let nextState = newState;
      const auth = useAuthStore();
      const viewerIdRaw = auth.user?.id ?? this.playerNames?.meId ?? null;
      if (viewerIdRaw != null && nextState?.me?.id != null && nextState?.opponent?.id != null) {
        const viewerId = Number(viewerIdRaw);
        const meId = Number(nextState.me.id);
        const opponentId = Number(nextState.opponent.id);
        if (
          Number.isFinite(viewerId) &&
          Number.isFinite(meId) &&
          Number.isFinite(opponentId) &&
          meId !== viewerId &&
          opponentId === viewerId
        ) {
          const swapRelative = (value) =>
            value === "me" ? "opponent" : value === "opponent" ? "me" : value;
          const swappedReady = nextState.ready
            ? { me: nextState.ready.opponent, opponent: nextState.ready.me }
            : undefined;
          nextState = {
            ...nextState,
            me: nextState.opponent,
            opponent: nextState.me,
            currentTurn: swapRelative(nextState.currentTurn),
            dealer: swapRelative(nextState.dealer),
            roundWinner: swapRelative(nextState.roundWinner),
            winner: swapRelative(nextState.winner),
            loser: swapRelative(nextState.loser),
            forfeitLoser: swapRelative(nextState.forfeitLoser),
            ready: swappedReady ?? nextState.ready,
          };
        }
      }

      const wasFinished = shouldShowResultModal(this.state);
      const prevTurn = this.state?.currentTurn ?? null;
      const prevRound = this.state?.round ?? null;
      const prevStatus = this.state?.status ?? null;
      const prevOpponentId =
        this.playerNames?.opponentId ?? this.state?.opponent?.id ?? null;

      if (isMatchSessionState(nextState)) {
        this.sessionMode = "match";
        if (nextState?.matchGameNumber != null) {
          this.matchGameNumber = nextState.matchGameNumber;
        }
        if (nextState?.matchId != null) {
          this.matchId = nextState.matchId;
        }
        if (nextState?.matchStake != null) {
          this.matchStake = nextState.matchStake;
        }
      }

      // Evitar limpar a mesa enquanto o modal de transição está visível.
      if (
        this.sessionMode === "match" &&
        this.resultModal &&
        this.pendingNextGameId &&
        this.state?.status === "Ended"
      ) {
        const meId = this.playerNames?.meId ?? this.state?.me?.id ?? nextState?.me?.id ?? null;
        const opponentId = this.playerNames?.opponentId ?? this.state?.opponent?.id ?? nextState?.opponent?.id ?? null;
        const readySnapshot = normalizeReadyState(nextState, meId, opponentId);
        if (readySnapshot && (readySnapshot.me || readySnapshot.opponent)) {
          this.pendingNextReady = readySnapshot;
        }
        return;
      }

      if (nextState?.opponent?.handSize !== undefined) {
        this.lastOpponentHandSize = nextState.opponent.handSize;
      }
      if (nextState?.trumpCard) {
        this.trumpCardSnapshot = nextState.trumpCard;
      }
      if (this.mode !== "offline") {
        const incomingLastPlayed = nextState?.lastPlayedCards;
        const noCardsOnTable = !nextState?.me?.playedCard && !nextState?.opponent?.playedCard;
        const resolvingTrick = nextState?.status === "Playing" && !nextState?.currentTurn;
        // Só aplicar lastPlayed na hidratação inicial se estivermos a resolver a vaza ou se houver cartas na mesa.
        const shouldApplyLastPlayed =
          Boolean(incomingLastPlayed) &&
          (!initialHydration || resolvingTrick || !noCardsOnTable);
        console.log("[gameStore] updateState lastPlayed", {
          initialHydration,
          noCardsOnTable,
          resolvingTrick,
          incomingLastPlayed,
          shouldApplyLastPlayed,
          status: nextState?.status,
          currentTurn: nextState?.currentTurn,
          round: nextState?.round,
        });
        if (shouldApplyLastPlayed) {
          this.lastPlayedCards = {
            player1: incomingLastPlayed.me ?? null,
            player2: incomingLastPlayed.opponent ?? null,
          };
          const bothCleared =
            nextState?.me?.playedCard == null && nextState?.opponent?.playedCard == null;
          const roundAdvanced = prevRound !== null && nextState?.round != null
            ? nextState.round !== prevRound
            : false;
          if (bothCleared && roundAdvanced) {
            if (this.lastPlayedClearTimeout) {
              clearTimeout(this.lastPlayedClearTimeout);
            }
            if (this.pendingScoreTimeout) {
              clearTimeout(this.pendingScoreTimeout);
            }
            const delayMs = this.mode === "offline" ? 650 : 1200;
            this.lastPlayedClearTimeout = setTimeout(() => {
              this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
              this.lastPlayedClearTimeout = null;
            }, delayMs);
            this.turnTimerFrozen = true;
            this.turnTimerResolutionFrozen = true;
            if (this.turnTimerUnfreezeTimeout) {
              clearTimeout(this.turnTimerUnfreezeTimeout);
            }
            this.turnTimerUnfreezeTimeout = setTimeout(() => {
              this.turnTimerResolutionFrozen = false;
              this.turnTimerFrozen = false;
              this.turnTimerUnfreezeTimeout = null;
            }, delayMs);
            const shouldDelayScores =
              prevMeScore != null && prevOpponentScore != null;
            if (shouldDelayScores) {
              this.pendingScoreUpdate = {
                me: nextState?.me?.score ?? null,
                opponent: nextState?.opponent?.score ?? null,
              };
              this.pendingScoreTimeout = setTimeout(() => {
                if (this.state?.me && this.state?.opponent && this.pendingScoreUpdate) {
                  this.state = {
                    ...this.state,
                    me: { ...this.state.me, score: this.pendingScoreUpdate.me },
                    opponent: { ...this.state.opponent, score: this.pendingScoreUpdate.opponent },
                  };
                }
                this.pendingScoreUpdate = null;
                this.pendingScoreTimeout = null;
              }, delayMs);
            }
          }
        } else if (this.lastPlayedCards?.player1 || this.lastPlayedCards?.player2) {
          this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
        }
      } else if (this.turnTimerResolutionFrozen) {
        this.turnTimerResolutionFrozen = false;
        this.turnTimerFrozen = false;
        if (this.turnTimerUnfreezeTimeout) {
          clearTimeout(this.turnTimerUnfreezeTimeout);
          this.turnTimerUnfreezeTimeout = null;
        }
      }

      // Freeze timer while both cards are on the table and round is being resolved
      const bothCardsOnTable = Boolean(nextState?.me?.playedCard) && Boolean(nextState?.opponent?.playedCard);
      
      const resolvingTrick = nextState?.status === "Playing" && bothCardsOnTable && !nextState?.currentTurn;
      if (resolvingTrick) {
        this.turnTimerFrozen = true;
        if (this.turnTimerUnfreezeTimeout) {
          clearTimeout(this.turnTimerUnfreezeTimeout);
          this.turnTimerUnfreezeTimeout = null;
        }
      } else if (!this.turnTimerResolutionFrozen) {
        this.turnTimerFrozen = false;
      }

      const meIdFromState = nextState?.me?.id ?? this.playerNames?.meId ?? null;
      const opponentIdFromState = nextState?.opponent?.id ?? null;
      const meAvatar = nextState?.me?.photo_avatar_filename ?? null;
      const opponentAvatar = nextState?.opponent?.photo_avatar_filename ?? null;

      const ready = normalizeReadyState(nextState, meIdFromState, opponentIdFromState);
      let stagedState = ready ? { ...nextState, ready } : nextState;
      const nextEnded = shouldShowResultModal(nextState);
      if (this.sessionMode === "match" && this.pendingNextGameId && this.pendingNextReady) {
        stagedState = {
          ...stagedState,
          ready: {
            me: this.pendingNextReady.me ?? stagedState?.ready?.me ?? false,
            opponent: this.pendingNextReady.opponent ?? stagedState?.ready?.opponent ?? false,
          },
        };
      }
      if (
        nextEnded &&
        this.mode === "online" &&
        isMatchSessionState(nextState) &&
        !this.pendingNextGameId
      ) {
        stagedState = {
          ...stagedState,
          ready: { me: false, opponent: false },
          readyPlayers: [],
        };
        this.pendingNextReady = { me: false, opponent: false };
      }
      const shouldDelayScores =
        (nextState?.me?.playedCard == null && nextState?.opponent?.playedCard == null) &&
        (prevRound !== null && nextState?.round != null ? nextState.round !== prevRound : false) &&
        prevMeScore != null &&
        prevOpponentScore != null;
      this.state = shouldDelayScores
        ? {
            ...stagedState,
            me: { ...stagedState.me, score: prevMeScore },
            opponent: { ...stagedState.opponent, score: prevOpponentScore },
          }
        : stagedState;

      this.waitingForOpponent = computeWaitingForOpponent(
        this.mode,
        nextState,
        meIdFromState,
        opponentIdFromState
      );

      const nextOpponentId =
        nextState?.opponent?.id ?? opponentIdFromState ?? null;
      const meIdCurrent = this.playerNames?.meId ?? this.state?.me?.id ?? null;
      const prevIsPlaceholder =
        prevOpponentId != null &&
        meIdCurrent != null &&
        Number(prevOpponentId) === Number(meIdCurrent);
      const effectivePrevOpponentId = prevIsPlaceholder ? null : prevOpponentId;
      const nextIsSelf =
        nextOpponentId != null &&
        meIdCurrent != null &&
        Number(nextOpponentId) === Number(meIdCurrent);
      if (!nextIsSelf && !effectivePrevOpponentId && nextOpponentId) {
        const alreadyLogged = (this.lobbyLog ?? []).some(
          (entry) =>
            entry?.action === "join" &&
            Number(entry?.userId) === Number(nextOpponentId)
        );
        if (!alreadyLogged) {
          const joinEntry = {
            action: "join",
            userId: nextOpponentId,
            nickname:
              nextState?.opponent?.nickname ??
              this.playerNames?.opponent ??
              "Adversário",
            at: new Date().toISOString(),
          };
          // push through normalizer to avoid temporary duplicates; backend will also append its own entry
          this.setLobbyLog([...(this.lobbyLog ?? []), joinEntry]);
        }
      }

      if (meAvatar || opponentAvatar) {
        this.playerAvatars = {
          me: meAvatar ?? this.playerAvatars.me,
          opponent: opponentAvatar ?? this.playerAvatars.opponent,
        };
      }

      this.refreshNamesIfNeeded(opponentIdFromState ?? null);

      if (nextState?.status === "Playing" && nextState?.currentTurn) {
        const nextRound = nextState?.round ?? null;
        const nextTurn = nextState?.currentTurn ?? null;
        const startedAt = Number.isFinite(nextState?.turnStartedAt)
          ? nextState.turnStartedAt
          : null;
        const rawDurationMs = Number.isFinite(nextState?.turnDurationMs)
          ? nextState.turnDurationMs
          : null;
        const durationMs =
          rawDurationMs != null ? Math.max(rawDurationMs, MIN_TURN_DURATION_MS) : null;
        const justStartedPlaying = prevStatus !== "Playing";
        const normalizedStartedAt = justStartedPlaying ? Date.now() : (startedAt ?? Date.now());
        const preferClientTimer = this.mode === "offline";
        const sameTurn = prevTurn === nextTurn && prevRound === nextRound;
        const overrideActive = this.turnTimerOverride === true;
        const effectiveStartedAt = overrideActive
          ? Date.now()
          : (preferClientTimer ? Date.now() : normalizedStartedAt);
        const shouldFreezeTimer = this.turnTimerFrozen === true;

        if (shouldFreezeTimer) {
          this.turnTimerRound = nextRound;
          if (durationMs && durationMs !== this.turnTimerDurationMs) {
            this.turnTimerDurationMs = durationMs;
          }
          this.turnTimerStartedAt = null;
        } else {
          if (
            this.turnTimerRound !== nextRound ||
            prevTurn !== nextTurn ||
            prevRound !== nextRound ||
            !this.turnTimerStartedAt
          ) {
            this.turnTimerRound = nextRound;
            this.turnTimerStartedAt = effectiveStartedAt;
            if (durationMs) {
              this.turnTimerDurationMs = durationMs;
            }
          } else if (!preferClientTimer && startedAt && startedAt !== this.turnTimerStartedAt && !justStartedPlaying) {
            this.turnTimerStartedAt = startedAt;
          }
          if (durationMs && durationMs !== this.turnTimerDurationMs) {
            this.turnTimerDurationMs = durationMs;
          }
          if (overrideActive) {
            this.turnTimerOverride = false;
          }
        }
      } else {
        this.turnTimerRound = null;
        this.turnTimerStartedAt = null;
        this.turnTimerOverride = false;
      }

      if (this.mode === "offline") {
        const ended = shouldShowResultModal(nextState);
        this.offlineReconnectAvailable = !ended;
        this.pendingOfflineState = ended ? null : nextState;
      }

      const shouldShowResult = shouldShowResultModal(nextState);
      if (this.sessionMode === "match" && shouldShowResult && !wasFinished) {
        let gained = {
          me: nextState?.me?.marks ?? nextState?.marks?.me ?? 0,
          opponent: nextState?.opponent?.marks ?? nextState?.marks?.opponent ?? 0,
        };

        if (nextState?.matchForfeited) {
          gained = nextState?.winner === "me"
            ? { me: 4, opponent: 0 }
            : { me: 0, opponent: 4 };
        }
        this.matchMarks = {
          me: clampMarks(this.matchMarks.me + (gained.me ?? 0)),
          opponent: clampMarks(this.matchMarks.opponent + (gained.opponent ?? 0)),
        };
      }

      if (shouldShowResult) {
        this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
        const modal = toResultModal(nextState);
        // Adicionar o número do jogo ao modal se for um match
        if (isMatchSessionState(nextState) && this.matchGameNumber) {
          modal.gameNumber = this.matchGameNumber;
        }
        this.resultModal = modal;
      }

      this.hydrationComplete = true;
      this.loading = false;
    },
    async startOnlineGame(gameId, options = {}) {
      const { preserveMatchMarks = false } = options;
      const previousMarks = preserveMatchMarks ? { ...this.matchMarks } : null;
      const desiredMatchGameNumber = preserveMatchMarks ? this.matchGameNumber : 1;
      const desiredSessionMode = this.sessionMode;
      const desiredMatchId = this.matchId;
      const desiredMatchStake = this.matchStake;

      const auth = useAuthStore();
      auth.init?.();
      if (!auth.user) {
        try {
          await auth.fetchUser();
        } catch (err) {
          throw new Error("Precisas de iniciar sessão novamente.");
        }
      }
      const ws = useWsStore();
      // Guardar matchMarks antes de reset se preserveMatchMarks
      const marksToPreserve = preserveMatchMarks && previousMarks ? previousMarks : null;
      
      this.resetGameState();
      if (desiredSessionMode) {
        this.sessionMode = desiredSessionMode;
      }
      if (desiredMatchId) {
        this.matchId = desiredMatchId;
      }
      if (desiredMatchStake != null) {
        this.matchStake = desiredMatchStake;
      }
      this.entryStake = 2;

      // Restaurar matchMarks após reset se necessário
      if (marksToPreserve) {
        this.matchMarks = marksToPreserve;
      }
      this.matchGameNumber = desiredMatchGameNumber;

      this.mode = "online";
      this.gameId = gameId ?? null;
      this.sessionMode = this.sessionMode ?? "game";
      this.loading = true;
      this.waitingForOpponent = false;
      this.playerNames = { me: null, opponent: null, meId: null, opponentId: null };
      this.playerAvatars = { me: null, opponent: null };
      this.realtimeError = null;
      this.serverStatus = null;

      try {
        let game;
        let matchData = null;
        let matchActiveGame = null;
        let lastGame = null;
        const explicitMatchId = this.matchId ?? null;

        if (explicitMatchId) {
          const res = await api.get(`/matches/${explicitMatchId}`, {
            params: gameId ? { game_id: gameId } : {},
          });
          matchData = res.data?.match ?? res.data;
          matchActiveGame = res.data?.active_game ?? null;
          lastGame = res.data?.last_game ?? null;
          game =
            res.data?.requested_game ??
            res.data?.game ??
            matchActiveGame ??
            lastGame ??
            null;
          if (!this.matchId) this.matchId = matchData?.id ?? this.matchId;
          this.sessionMode = "match";
          if (matchData?.stake != null) {
            this.matchStake = matchData.stake;
          }
          const p1Marks = matchData?.player1_marks ?? null;
          const p2Marks = matchData?.player2_marks ?? null;
          // Sempre restaurar matchMarks do backend se disponível (mesmo que seja 0)
          // Isso garante que após F5 temos o estado correto
          // IMPORTANTE: player1_marks e player2_marks podem ser null se o match ainda não começou
          // ou se nenhum jogo terminou ainda. Nesse caso, usar 0.
          const finalP1Marks = p1Marks !== null ? Number(p1Marks) : 0;
          const finalP2Marks = p2Marks !== null ? Number(p2Marks) : 0;
          const meId = auth.user?.id ?? null;
          const meIsP1 = meId != null && Number(meId) === Number(matchData?.player1_user_id);
          this.matchMarks = meIsP1
            ? { me: clampMarks(finalP1Marks), opponent: clampMarks(finalP2Marks) }
            : { me: clampMarks(finalP2Marks), opponent: clampMarks(finalP1Marks) };
          
          // Verificar se há um jogo guardado no custom que deve mostrar o modal de transição
          const transitionModalGameId = matchData?.custom?.transition_modal_game_id ?? null;
          if (transitionModalGameId && matchActiveGame && !gameId) {
            // Se há um jogo ativo e um jogo guardado para o modal, restaurar o modal
            try {
              const transitionGameRes = await api.get(`/games/${transitionModalGameId}`);
              const transitionGame = transitionGameRes.data?.game;
              if (transitionGame && transitionGame.status === "Ended") {
                this.pendingNextGameId = matchActiveGame.id;
                const meId = auth.user?.id ?? null;
                const opponentId =
                  meId === matchActiveGame.player1_user_id
                    ? matchActiveGame.player2_user_id
                    : matchActiveGame.player1_user_id;
                // Verificar o status do jogo ativo antes de restaurar ready
                // Se o jogo ativo já começou, não restaurar ready
                if (matchActiveGame.status === "Pending" || matchActiveGame.status === "WaitingPlayers" || matchActiveGame.status === "WaitingReady") {
                  this.applyReadySnapshotFromGame(matchData ?? matchActiveGame, meId, opponentId);
                } else {
                  // Jogo já começou, limpar ready
                  this.pendingNextReady = { me: false, opponent: false };
                }
                // Calcular o número do jogo baseado no número de jogos terminados
                // Se há um próximo jogo, o jogo de transição é matchGameNumber - 1
                const gameNumber = this.matchGameNumber > 1 ? this.matchGameNumber - 1 : this.matchGameNumber;
                const modalFromTransition = resultModalFromGameRecord(transitionGame, meId, gameNumber);
                if (modalFromTransition) {
                  this.resultModal = modalFromTransition;
                  // Usar o jogo de transição como jogo atual para contexto
                  game = transitionGame;
                }
              }
            } catch (err) {
              console.warn("Falha a carregar jogo de transição do modal", err);
            }
          }
        } else {
          try {
            const res = await api.get(`/games/${gameId}`);
            game = res.data.game;
          } catch (err) {
            const status = err.response?.status;
            const isForbidden = status === 403 || status === 422;
            if (isForbidden) {
              const backendMessage = err.response?.data?.message ?? "";
              if (typeof backendMessage === "string" && backendMessage.toLowerCase().includes("match")) {
                throw new Error(backendMessage);
              }
              const joinRes = await api.post(`/games/${gameId}/join`);
              game = joinRes.data.game;
            } else {
              throw err;
            }
          }
        }

        if (!game) {
          throw new Error("Jogo indisponível.");
        }

        const isMatchContext =
          this.sessionMode === "match" ||
          Boolean(this.matchId) ||
          Boolean(game.match_id) ||
          Boolean(matchData);

        if (isMatchContext && !this.matchId) {
          this.matchId = matchData?.id ?? game.match_id ?? this.matchId;
        }

        if (isMatchContext && matchData?.status === "Ended") {
          throw new Error("Match já terminou.");
        }

        if (game.player1_user_id !== auth.user?.id && game.player2_user_id !== auth.user?.id) {
          throw new Error("Jogo completo ou indisponível.");
        }

        // Se há um jogo ativo diferente do último jogo terminado, mostrar modal de transição
        if (
          isMatchContext &&
          matchActiveGame &&
          lastGame &&
          lastGame.status === "Ended" &&
          Number(matchActiveGame.id) !== Number(lastGame.id)
        ) {
          this.pendingNextGameId = matchActiveGame.id;
          this.sessionMode = "match";
          const meId = auth.user?.id ?? null;
          const opponentId =
            meId === matchActiveGame.player1_user_id
              ? matchActiveGame.player2_user_id
              : matchActiveGame.player1_user_id;
          // Verificar o status do jogo ativo antes de restaurar ready
          // Se o jogo já começou, não restaurar ready
          if (matchActiveGame.status === "Pending" || matchActiveGame.status === "WaitingPlayers" || matchActiveGame.status === "WaitingReady") {
            this.applyReadySnapshotFromGame(matchData ?? matchActiveGame, meId, opponentId);
          } else {
            // Jogo já começou, limpar ready
            this.pendingNextReady = { me: false, opponent: false };
          }
          // Usar lastGame para criar o modal, ou o jogo atual se for o terminado
          const gameForModal = (game?.status === "Ended" && Number(game.id) === Number(lastGame.id)) ? game : lastGame;
          // Calcular o número do jogo: se há um próximo jogo, o jogo terminado é matchGameNumber - 1
          const gameNumber = this.matchGameNumber > 1 ? this.matchGameNumber - 1 : this.matchGameNumber;
          const modalFromGame = resultModalFromGameRecord(gameForModal, meId, gameNumber);
          if (modalFromGame && !this.resultModal) {
            this.resultModal = modalFromGame;
          }
        }

        this.gameId = game?.id ?? this.gameId ?? gameId ?? null;

        this.serverStatus = game.status ?? null;

        // Se o jogo atual terminou e há um próximo jogo ativo, garantir que o modal é mostrado
        if (
          isMatchContext &&
          this.serverStatus === "Ended" &&
          matchActiveGame &&
          Number(matchActiveGame.id) !== Number(game?.id ?? this.gameId) &&
          !this.resultModal
        ) {
          this.pendingNextGameId = matchActiveGame.id;
          const meId = auth.user?.id ?? null;
          const opponentId =
            meId === matchActiveGame.player1_user_id
              ? matchActiveGame.player2_user_id
              : matchActiveGame.player1_user_id;
          this.applyReadySnapshotFromGame(matchData ?? matchActiveGame, meId, opponentId);
          // Calcular o número do jogo: se há um próximo jogo, o jogo terminado é matchGameNumber - 1
          const gameNumber = this.matchGameNumber > 1 ? this.matchGameNumber - 1 : this.matchGameNumber;
          const modalFromCurrent = resultModalFromGameRecord(game, meId, gameNumber);
          if (modalFromCurrent) {
            this.resultModal = modalFromCurrent;
          }
        } else if (
          isMatchContext &&
          this.serverStatus === "Ended" &&
          !this.resultModal &&
          !matchActiveGame
        ) {
          // Se o jogo terminou mas não há próximo jogo, mostrar modal do jogo atual
          const meId = auth.user?.id ?? null;
          const modalFromCurrent = resultModalFromGameRecord(game, meId);
          if (modalFromCurrent) {
            this.resultModal = modalFromCurrent;
          }
        }

        // Se o jogo terminou mas o match continua, deixar seguir para modal de transição
        if (this.serverStatus === "Ended" && isMatchContext) {
          // Se há um jogo ativo diferente do jogo atual, mostrar modal de transição
          if (matchActiveGame && Number(matchActiveGame.id) !== Number(game?.id ?? gameId ?? this.gameId)) {
            this.pendingNextGameId = matchActiveGame.id;
            this.sessionMode = "match";
            const meId = auth.user?.id ?? null;
            const opponentId =
              meId === matchActiveGame.player1_user_id
                ? matchActiveGame.player2_user_id
                : matchActiveGame.player1_user_id;
            this.applyReadySnapshotFromGame(matchData ?? matchActiveGame, meId, opponentId);
            // Usar o jogo atual (terminado) para criar o modal, ou lastGame se disponível
            const gameForModal = (game?.status === "Ended" ? game : null) ?? lastGame;
            // Calcular o número do jogo: se há um próximo jogo, o jogo terminado é matchGameNumber - 1
            const gameNumber = this.matchGameNumber > 1 ? this.matchGameNumber - 1 : this.matchGameNumber;
            const modalFromGame = gameForModal?.status === "Ended"
              ? resultModalFromGameRecord(gameForModal, meId, gameNumber)
              : null;
            if (modalFromGame && !this.resultModal) {
              this.resultModal = modalFromGame;
            }
          }
          // Se não há jogo ativo, usar lastGame para mostrar o estado final e permitir modal criar próximo
          if (!matchActiveGame && lastGame && !this.pendingNextGameId) {
            this.gameId = lastGame.id;
            // Garantir que o modal é mostrado se o jogo terminou
            if (lastGame.status === "Ended" && !this.resultModal) {
              const meId = auth.user?.id ?? null;
              const modalFromLast = resultModalFromGameRecord(lastGame, meId, this.matchGameNumber);
              if (modalFromLast) {
                this.resultModal = modalFromLast;
              }
            }
          }
        }

        if (["Interrupted", "Cancelled"].includes(this.serverStatus)) {
          throw new Error("Este jogo já não está disponível.");
        }

        if (this.serverStatus === "Ended" && !isMatchContext) {
          throw new Error("Este jogo já não está disponível.");
        }

        const waitingForOpponent =
          !game.player2_user_id ||
          (game.custom?.waiting_for_opponent && game.player2_user_id === game.player1_user_id);
        this.waitingForOpponent = Boolean(waitingForOpponent);
        this.currentVariant = game.type ?? this.currentVariant;
        if (game.match_id && !this.matchId) {
          this.matchId = game.match_id;
        }
        this.ownerId = game.player1_user_id ?? this.ownerId;

        const meId = auth.user?.id;
        const p1 = game.player1;
        const p2 = game.player2;
        const meName =
          meId === p1?.id ? p1?.nickname :
          meId === p2?.id ? p2?.nickname :
          auth.user?.nickname;
        const opponentId = meId === p1?.id ? p2?.id : p1?.id;
        const opponentName =
          opponentId === p1?.id ? p1?.nickname :
          opponentId === p2?.id ? p2?.nickname :
          null;
        this.playerNames = {
          me: meName ?? null,
          opponent: opponentName ?? null,
          meId: meId ?? null,
          opponentId: opponentId ?? null,
        };
        this.playerAvatars = {
          me: meId === p1?.id ? p1?.photo_avatar_filename : p2?.photo_avatar_filename,
          opponent: opponentId === p1?.id ? p1?.photo_avatar_filename : p2?.photo_avatar_filename,
        };
        this.lobbyLog = normalizeLobbyLog(game.custom?.lobby_log);
        this.applyReadySnapshotFromGame(game, meId, opponentId);

        if (!ws.connected) {
          ws.connect(auth.token);
        }
        await this.waitForWsReady(ws);

        // Em match, a presença é gerida via sala de match; ainda assim mantemos join do jogo
        // para receber atualizações do jogo específico.
        ws.send({
          type: "join_game",
          gameId: this.gameId,
        });

        try {
          const stateRes = await api.get(`/games/${this.gameId}/state`);
          if (stateRes.data?.state) {
            this.updateState(stateRes.data.state);
          }
        } catch (err) {
          console.warn("Falha a obter estado do jogo", err?.response?.status || err);
        }
      } catch (err) {
        console.error(err);
        const msg = err.response?.data?.message ?? err.message ?? "Não foi possível entrar no jogo.";
        toast.error("Erro no multiplayer", { description: msg });
        this.resetGameState();
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async waitForWsReady(ws, retries = 10) {
      if (ws.connected) return true;
      return new Promise((resolve, reject) => {
        let attempts = 0;
        const timer = setInterval(() => {
          attempts += 1;
          if (ws.connected) {
            clearInterval(timer);
            resolve(true);
          } else if (attempts >= retries) {
            clearInterval(timer);
            reject(new Error("WebSocket indisponível."));
          }
        }, 250);
      });
    },
    resetGameState() {
      this.$reset();
    },
    handleLobbyReset(roomId) {
      const currentId = this.gameId != null ? Number(this.gameId) : null;
      const incomingId = roomId != null ? Number(roomId) : null;
      if (
        this.mode !== "online" ||
        !Number.isFinite(currentId) ||
        !Number.isFinite(incomingId) ||
        currentId !== incomingId
      ) {
        return false;
      }
      const hadOpponent = Boolean(this.playerNames?.opponentId || this.state?.opponent?.id);
      this.state = null;
      this.waitingForOpponent = true;
      this.playerNames = { ...this.playerNames, opponent: null, opponentId: null };
      this.trumpCardSnapshot = null;
      return hadOpponent;
    },
    handleRealtimeDisconnect(message = "Ligação em tempo-real indisponível. Estamos a tentar reconectar...") {
      if (this.mode !== "online") return;
      this.realtimeError = message;
    },
    handleRealtimeReconnect() {
      this.realtimeError = null;
    },
    setLobbyLog(entries) {
      this.lobbyLog = normalizeLobbyLog(entries);
    },
    appendLobbyLog(entry) {
      this.lobbyLog = normalizeLobbyLog([...(this.lobbyLog ?? []), entry]);
    },
    async startOffline(type = "3") {
      const sessionMode = this.sessionMode ?? "game";
      this.sessionMode = sessionMode;
      this.currentVariant = type;
      if (sessionMode !== "match") {
        this.matchGameNumber = 1;
        this.matchMarks = { me: 0, opponent: 0 };
      } else if (this.matchGameNumber <= 1) {
        this.matchMarks = { me: 0, opponent: 0 };
      }
      this.mode = "offline";
      this.loading = true;
      this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
      this.botActing = false
      this.resultModal = null
      this.offlineReconnectAvailable = false
      this.pendingOfflineState = null

      const res = await api.post("/offline/start", { type });

      this.state = res.data.state;
      this.loading = false;
      this.offlineReconnectAvailable = true;
      this.pendingOfflineState = this.state;
    },
    async continueOfflineMatch() {
      if (this.mode !== "offline") return false;

      this.sessionMode = "match";
      const nextType = this.currentVariant ?? this.state?.type ?? "3";
      this.matchGameNumber += 1;

      await this.startOffline(nextType);
      return true;
    },
    async checkOfflinePersistedGame(force = false) {
      if (!force && this.offlineReconnectChecked) {
        return this.offlineReconnectAvailable;
      }
      this.offlineReconnectChecked = true;

      if (this.mode === "offline" && this.state && !this.gameEnded) {
        this.offlineReconnectAvailable = true;
        this.pendingOfflineState = this.state;
        return true;
      }

      this.offlineReconnectAvailable = false;
      this.pendingOfflineState = null;
      return false;
    },
    async reconnectOffline() {
      this.mode = "offline";
      this.loading = true;
      this.botActing = false;
      this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
      this.resultModal = null;

      if (!this.offlineReconnectAvailable && !this.pendingOfflineState) {
        this.loading = false;
        return false;
      }

      const hydrateAndFinish = (incomingState) => {
        this.updateState(incomingState);
        this.loading = false;
        return true;
      };

      if (this.pendingOfflineState && !this.gameEnded) {
        return hydrateAndFinish(this.pendingOfflineState);
      }

      this.loading = false;
      this.offlineReconnectAvailable = false;
      this.pendingOfflineState = null;
      this.state = null;
      return false;
    },
    async resignOffline() {
      const res = await api.post("/offline/resign");
      this.updateState(res.data.state);
    },
    async resignOfflineWithReason(reason = "resign") {
      const res = await api.post("/offline/resign", { reason });
      this.updateState(res.data.state);
    },
    playCard(index) {
      if (this.mode === "offline") {
        return this.playCardOffline(index);
      }

      if (this.mode === "online") {
        return this.playCardOnline(index);
      }
    },
    async playCardOnline(cardIndex) {
      if (!this.gameId) return;
      try {
        await api.post(`/games/${this.gameId}/play-card`, { cardIndex });
        try {
          const stateRes = await api.get(`/games/${this.gameId}/state`);
          if (stateRes.data?.state) {
            this.updateState(stateRes.data.state);
          }
        } catch (err) {
          console.warn("Falha a obter estado do jogo", err?.response?.status || err);
        }
      } catch (err) {
        const message =
          err.response?.data?.message ??
          err.response?.data?.error ??
          "Não foi possível jogar essa carta.";
        toast.error("Jogada inválida", { description: message });
      }
    },
    async playCardOffline(cardIndex) {
      if (!this.state) return;
      if (this.state.status !== "Playing") return;
      if (this.state.currentTurn !== "me") return;
      if (this.lastPlayedCards.player1 !== null) return;

      const snapshot = this.state ? JSON.parse(JSON.stringify(this.state)) : null;

      const myCard = this.state.me.hand[cardIndex];
      this.lastPlayedCards.player1 = myCard;

      if (this.state) {
          this.state.currentTurn = "opponent";
      }

      this.state.me.hand = this.state.me.hand.map((c, i) => (i === cardIndex ? null : c));

      let backendState;
      try {
        const res = await api.post("/offline/play-card", { cardIndex });
        backendState = res.data.state;
      } catch (err) {
        this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
        this.botActing = false;
        if (snapshot) {
          this.state = snapshot;
        }
        const message = err.response?.data?.message ?? "Não foi possível jogar essa carta.";
        toast.error("Jogada inválida", { description: message });
        return;
      }

      const botCard = backendState.opponent.playedCard;

      if (botCard) {
        this.botActing = true;
        await new Promise(r => setTimeout(r, 1000));
        this.lastPlayedCards.player2 = botCard;
        if (this.state?.opponent) {
          this.state.opponent.handSize = backendState.opponent.handSize;
          this.lastOpponentHandSize = backendState.opponent.handSize;
        }
      }

      const roundFinished =
          backendState.me.playedCard !== null &&
          backendState.opponent.playedCard !== null;

      if (roundFinished) {
          await this.resolveOfflineRound();
          return;
      }

      await new Promise(r => setTimeout(r, 3000));

      this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };

      await new Promise(r => setTimeout(r, 100));
      this.updateState(backendState);
    },

    async resolveOfflineRound() {
      const res = await api.post("/offline/resolve-round");
      const backendState = res.data.state;

      if (backendState.status === "Ended" || backendState.matchForfeited) {
          this.updateState(backendState);
          setTimeout(() => {
              this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };
          }, 400);
          return;
      }

      const botStarts =
          backendState.opponent.playedCard !== null &&
          backendState.me.playedCard === null &&
          backendState.currentTurn === "me";

      let stateToShow = backendState;
      if (botStarts) {
          stateToShow = JSON.parse(JSON.stringify(backendState));
          stateToShow.opponent.playedCard = null;
          stateToShow.opponent.handSize = (backendState.opponent.handSize ?? 0) + 1;
          stateToShow.currentTurn = "opponent";
          stateToShow.me.hand = backendState.me.hand;
      } else {
          stateToShow = JSON.parse(JSON.stringify(backendState));
          stateToShow.me.hand = backendState.me.hand;
      }

      await new Promise(r => setTimeout(r, 200));

      const stagedState = JSON.parse(JSON.stringify(stateToShow));
      stagedState.me.hand = backendState.me.hand;
      stagedState.me.score = backendState.me.score;
      stagedState.opponent.score = backendState.opponent.score;

      this.updateState(stagedState);

      await new Promise(r => setTimeout(r, 80));
      this.lastPlayedCards = { ...DEFAULT_LAST_PLAYED };

      if (botStarts) {
          await this.animateBotStartingRound({
              ...backendState,
              me: { ...backendState.me, hand: backendState.me.hand }
          });
          this.botActing = false;
      } else {
          await new Promise(r => setTimeout(r, 120));
          this.updateState(backendState);
          this.botActing = false;
      }
    },
    async handleTimeout() {
      if (!this.state || this.state.status !== "Playing") return;

      if (this.mode === "offline") {
        if (this.state.currentTurn !== "me") return;
        await this.resignOfflineWithReason("timeout");
        return;
      }

      if (!this.gameId) return;
      try {
        const stateRes = await api.get(`/games/${this.gameId}/state`);
        if (stateRes.data?.state) {
          this.updateState(stateRes.data.state);
        }
      } catch (err) {
        console.warn("Falha ao validar timeout do turno", err?.response?.status || err);
      }
    },

    async animateBotStartingRound(state) {
      const botCard = state.opponent.playedCard;
      if (!botCard || state.status !== "Playing") return;

      if (this.state) {
          this.state.currentTurn = "opponent";
      }

      this.botActing = true;
      await new Promise(r => setTimeout(r, 1500));

      this.lastPlayedCards.player2 = botCard;
      const alignedState = JSON.parse(JSON.stringify(state));
      this.updateState(alignedState);
      this.botActing = false;
    },
    resign() {
      if (this.mode === "offline") {
        return this.resignOffline();
      }

    if (this.mode === "online" || this.gameId) {
        return this.resignOnline();
      }
    },
    async resignOnline() {
      if (!this.gameId) return;
      try {
        await api.post(`/games/${this.gameId}/resign`);
        try {
          const stateRes = await api.get(`/games/${this.gameId}/state`);
          if (stateRes.data?.state) {
            this.updateState(stateRes.data.state);
            return;
          }
        } catch {
        }

        if (this.state) {
          const fallback = {
            ...this.state,
            status: "Ended",
            matchForfeited: true,
            forfeitReason: "resign",
            forfeitLoser: "me",
            winner: "opponent",
            loser: "me",
            is_draw: false,
            currentTurn: null,
          };
          this.updateState(fallback);
        }
      } catch (err) {
        const message =
          err.response?.data?.message ??
          err.response?.data?.error ??
          "Não foi possível desistir.";
        toast.error("Erro ao desistir", { description: message });
      }
    },
    readyUp() {
      if (this.mode !== "online") return;
      const ws = useWsStore();
      const socketReady = ws.ws && ws.ws.readyState === WebSocket.OPEN;
      if (!socketReady) {
        toast.warning("Sem ligação ao lobby", {
          description: "Estamos a tentar reconectar. Volta a clicar em \"Pronto\" em instantes.",
        });
        if (ws.token) {
          ws.connect(ws.token);
        }
        return;
      }

      const alreadyReady = this.state?.ready?.me ?? false;
      const nextReady = {
        ...(this.state?.ready ?? { opponent: false, me: false }),
        me: !alreadyReady,
      };

      this.state = {
        ...(this.state ?? { status: WAITING_READY_STATUS }),
        ready: nextReady,
      };

      const targetRoom =
        this.sessionMode === "match" && this.matchId ? this.matchId : this.gameId;

      ws.send({
        type: alreadyReady ? "unready" : "ready",
        roomId: targetRoom,
        gameId: this.gameId,
        matchId: this.matchId,
      });
    },
    async refreshNamesIfNeeded(opponentId) {
      if (this.mode !== "online" || !this.gameId) return;
      const knownOpponentId = this.playerNames?.opponentId ?? null;
      if (opponentId && opponentId === knownOpponentId) return;
      try {
        const res = await api.get(`/games/${this.gameId}`);
        const game = res.data.game;
        const meId = useAuthStore().user?.id;
        const p1 = game.player1;
        const p2 = game.player2;
        const meName =
          meId === p1?.id ? p1?.nickname :
          meId === p2?.id ? p2?.nickname :
          useAuthStore().user?.nickname;
        const oppId = meId === p1?.id ? p2?.id : p1?.id;
        const oppName =
          oppId === p1?.id ? p1?.nickname :
          oppId === p2?.id ? p2?.nickname :
          null;

        this.playerNames = {
          me: meName ?? null,
          opponent: oppName ?? null,
          meId: meId ?? null,
          opponentId: oppId ?? null,
        };
      } catch (err) {
        console.warn("Falha a refrescar nomes do lobby", err?.response?.status || err);
      }
    },
    applyReadyState(roomId, userId, ready, matchId = null) {
      if (this.mode !== "online") return;

      const currentGame = this.gameId != null ? Number(this.gameId) : null;
      const currentMatch = this.matchId != null ? Number(this.matchId) : null;
      const incomingRoom = roomId != null ? Number(roomId) : null;
      const incomingMatch = matchId != null ? Number(matchId) : null;

      const targetPendingGame =
        this.pendingNextGameId != null ? Number(this.pendingNextGameId) : null;
      
      // Verificar se o evento é para o próximo jogo (pendingNextGameId)
      const isForPendingGame = targetPendingGame !== null && incomingRoom !== null && targetPendingGame === incomingRoom;
      
      const shouldUsePending =
        this.sessionMode === "match" &&
        targetPendingGame != null &&
        [
          currentMatch !== null && incomingMatch !== null && currentMatch === incomingMatch,
          currentMatch !== null && incomingRoom !== null && currentMatch === incomingRoom,
          isForPendingGame,
        ].some(Boolean);

      const matches =
        this.sessionMode === "match"
          ? [
              currentMatch !== null && incomingMatch !== null && currentMatch === incomingMatch,
              currentMatch !== null && incomingRoom !== null && currentMatch === incomingRoom,
              currentGame !== null && incomingRoom !== null && currentGame === incomingRoom,
              isForPendingGame, // Adicionar verificação do próximo jogo
            ].some(Boolean)
          : currentGame !== null && incomingRoom !== null && currentGame === incomingRoom;

      if (!matches) return;

      const meIdRaw = this.state?.me?.id ?? this.playerNames?.meId ?? null;
      const opponentIdRaw = this.state?.opponent?.id ?? this.playerNames?.opponentId ?? null;
      if (!meIdRaw && !opponentIdRaw) return;

      const targetId = userId != null ? Number(userId) : null;
      const meId = meIdRaw != null ? Number(meIdRaw) : null;
      const opponentId = opponentIdRaw != null ? Number(opponentIdRaw) : null;

      const readyState = shouldUsePending
        ? { ...(this.pendingNextReady ?? { me: false, opponent: false }) }
        : { ...(this.state?.ready ?? { me: false, opponent: false }) };
      const isSelf = targetId !== null && targetId === meId;
      const isOpponent = targetId !== null && targetId === opponentId;

      // Verificar se o estado já está no valor esperado para evitar atualizações redundantes
      // Mas não retornar se estamos a processar um toggle que vai tornar ambos prontos
      // Isso permite que o jogo comece quando ambos ficam prontos
      const currentBothReady = readyState.me && readyState.opponent;
      const willBeBothReady = ready && (
        (isSelf && readyState.opponent) || 
        (isOpponent && readyState.me) ||
        (isSelf && isOpponent) // Ambos na mesma mensagem (improvável mas possível)
      );
      
      // Só retornar se o estado já está correto E não estamos a tornar ambos prontos
      // E não estamos a processar uma mudança que vai fazer ambos ficarem prontos
      if (isSelf && readyState.me === ready && !willBeBothReady && !currentBothReady) return;
      if (isOpponent && readyState.opponent === ready && !willBeBothReady && !currentBothReady) return;

      if (isSelf) readyState.me = ready;
      if (isOpponent) readyState.opponent = ready;

      if (shouldUsePending) {
        this.pendingNextReady = readyState;
        return;
      }

      this.state = {
        ...(this.state ?? { status: WAITING_READY_STATUS }),
        ready: readyState,
      };

      if (!this.state.readyPlayers) {
        this.state.readyPlayers = [];
      }

      if (ready && targetId !== null && !this.state.readyPlayers.includes(targetId)) {
        this.state.readyPlayers = [...this.state.readyPlayers, targetId];
      } else if (!ready && targetId !== null) {
        this.state.readyPlayers = this.state.readyPlayers.filter((id) => id !== targetId);
      }
    },
    applyReadySnapshotFromGame(game, meId, opponentId) {
      // Verificar o status do jogo - se já começou, não restaurar ready
      // game pode ser o match (sem status) ou o jogo ativo (com status)
      const gameStatus = game?.status ?? null;
      const isGamePlaying = gameStatus === "Playing" || gameStatus === "Ended";
      
      // Se o jogo já começou, não limpar o ready enquanto estamos no modal de transição.
      // Isso evita que o estado volte para "Por confirmar" ao sincronizar com um jogo já em andamento.
      if (isGamePlaying) {
        // Não limpar nem sair se estamos em transição para o próximo jogo.
        if (!(this.sessionMode === "match" && this.pendingNextGameId)) {
          const baseState = this.state ?? { status: WAITING_READY_STATUS };
          this.state = {
            ...baseState,
            ready: {
              me: false,
              opponent: false,
            },
            readyPlayers: [],
          };
          return;
        }
      }
      
      // Se game não tem status (é o match), verificar se há um jogo ativo que já começou
      // Isso é verificado antes de chamar esta função, mas vamos garantir aqui também

      const readyFromState = normalizeReadyState(game, meId, opponentId);
      const readyRaw =
        game?.readyPlayers ??
        game?.ready_players ??
        game?.match?.custom?.ready_players ??
        game?.custom?.ready_players;
      const readyIds = Array.isArray(readyRaw)
        ? readyRaw
            .map((id) => Number(id))
            .filter((id) => Number.isFinite(id))
        : null;

      const readyFromCustom = readyIds
        ? {
            me: meId != null ? readyIds.includes(Number(meId)) : false,
            opponent: opponentId != null ? readyIds.includes(Number(opponentId)) : false,
          }
        : null;

      const readySnapshot = readyFromState ?? readyFromCustom;
      if (!readySnapshot) return;

      const currentReady =
        this.pendingNextGameId && this.pendingNextReady
          ? this.pendingNextReady
          : this.state?.ready ?? { me: false, opponent: false };

      // Evitar perder um "ready" já conhecido se o snapshot vier incompleto/desatualizado
      const mergedReady = {
        me: Boolean(readySnapshot.me || currentReady.me),
        opponent: Boolean(readySnapshot.opponent || currentReady.opponent),
      };

      if (this.sessionMode === "match" && this.pendingNextGameId) {
        // Verificar se o estado já está correto para evitar atualizações redundantes
        const currentPending = this.pendingNextReady ?? { me: false, opponent: false };
        if (currentPending.me === mergedReady.me && currentPending.opponent === mergedReady.opponent) {
          return;
        }
        this.pendingNextReady = mergedReady;
        return;
      }

      const baseState = this.state ?? { status: WAITING_READY_STATUS };
      this.state = {
        ...baseState,
        ready: mergedReady,
        readyPlayers: readyIds != null ? readyIds : baseState.readyPlayers ?? [],
      };
    },
    async cancelLobby() {
      if (this.mode !== "online") return false;

      const meId = useAuthStore().user?.id ?? null;
      let resolvedOwnerId = this.ownerId ?? null;
      const ws = useWsStore();
      if (!resolvedOwnerId && this.gameId) {
        try {
          const res = await api.get(`/games/${this.gameId}`);
          resolvedOwnerId = res.data?.game?.player1_user_id ?? null;
          if (resolvedOwnerId != null) {
            this.ownerId = resolvedOwnerId;
          }
        } catch {
        }
      }

      if (!resolvedOwnerId && this.sessionMode === "match" && this.matchId) {
        try {
          const res = await api.get(`/matches/${this.matchId}`);
          resolvedOwnerId = res.data?.match?.player1_user_id ?? null;
          if (resolvedOwnerId != null) {
            this.ownerId = resolvedOwnerId;
          }
        } catch {
        }
      }

      const isOwner =
        meId != null && resolvedOwnerId != null
          ? Number(meId) === Number(resolvedOwnerId)
          : false;

      if (this.sessionMode === "match" && this.matchId) {
        if (isOwner) {
          try {
            await api.delete(`/matches/${this.matchId}`);
            this.resetGameState();
            return "cancelled";
          } catch (err) {
            if (this.gameId) {
              ws.markSelfLeave(this.gameId);
              await api.post(`/games/${this.gameId}/leave-lobby`);
              this.resetGameState();
              return "cancelled";
            }
            throw err;
          }
        }

        if (this.gameId) {
          ws.markSelfLeave(this.gameId);
          await api.post(`/games/${this.gameId}/leave-lobby`);
        }
        this.resetGameState();
        return "left";
      }

      if (this.gameId) {
        if (isOwner) {
          await api.delete(`/games/${this.gameId}`);
        } else {
          ws.markSelfLeave(this.gameId);
          await api.post(`/games/${this.gameId}/leave-lobby`);
        }
      }
      this.resetGameState();
      return isOwner ? "cancelled" : "left";
    },
  },
});
