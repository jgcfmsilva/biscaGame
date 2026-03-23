<template>
    <Dialog :open="visible" modal>
        <DialogContent
            class="max-w-sm w-[90%] rounded-2xl p-0 overflow-hidden border-none shadow-2xl bg-slate-900 [&>button]:hidden">
            <div :class="['relative px-6 py-7 text-center', headerColor]">
                <DialogTitle class="text-3xl font-extrabold flex flex-col gap-1 drop-shadow-md">
                    <span v-if="isWin">🟢 Vitória</span>
                    <span v-else-if="isDraw">⚪ Empate</span>
                    <span v-else>🔴 Derrota</span>

                    <small class="text-sm font-normal opacity-80 text-white tracking-wide font-semibold">
                        <template v-if="game.sessionMode === 'match'">
                            Jogo {{ modalGameNumber }}
                        </template>
                        <template v-else>
                            Jogo concluído
                        </template>
                    </small>
                </DialogTitle>

                <DialogDescription v-if="isForfeit" class="opacity-95 mt-3 text-[13px] text-white drop-shadow-sm">
                    {{ forfeitText }}
                </DialogDescription>
            </div>

            <Card class="rounded-none border-none shadow-none bg-slate-900">
                <CardContent class="p-7 pt-6 text-center space-y-6">
                    <div class="space-y-2">
                        <p v-if="isForfeit" class="text-[11px] uppercase tracking-wide text-slate-400">
                            Pontos do jogo {{ game.sessionMode === 'match' ? modalGameNumber : '' }}
                        </p>
                        <div class="grid grid-cols-2 gap-4 text-sm">
 
                        <div class="flex flex-col items-center gap-1 bg-slate-800/40 px-3 py-3 rounded-xl">
                            <span class="text-[11px] uppercase tracking-wide text-emerald-400 font-semibold">
                                Tu
                            </span>
                            <span class="text-4xl font-bold text-slate-100 drop-shadow-sm">
                                {{ mePoints }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center gap-1 bg-slate-800/40 px-3 py-3 rounded-xl">
                            <span class="text-[11px] uppercase tracking-wide text-red-400 font-semibold">
                                Adv.
                            </span>
                            <span class="text-4xl font-bold text-slate-100 drop-shadow-sm">
                                {{ opponentPoints }}
                            </span>
                        </div>
                        </div>
                    </div>

                    <div class="rounded-xl px-4 py-4 text-sm font-medium border shadow-inner backdrop-blur-sm"
                        :class="riscaBoxColor">
                        <template v-if="!isMatchMode">
                            Jogo terminado.
                        </template>
                        <template v-else>
                            <template v-if="matchDecided">
                                <template v-if="matchWinner === 'me'">
                                    Ganhaste a partida ao chegar às 4 marcas.
                                </template>
                                <template v-else>
                                    Perdeste a partida — adversário chegou às 4 marcas.
                                </template>
                                <div class="mt-2 text-[12px] opacity-80">
                                    Total da partida: {{ matchMarksMe }} - {{ matchMarksOpponent }}
                                </div>
                            </template>
                            <template v-else-if="isForfeit">
                                <template v-if="isWin">
                                    Ganhaste a {{ scopeWord }} por {{ forfeitReasonText }} do adversário.
                                </template>
                                <template v-else>
                                    Perdeste {{ scopeWord === 'jogo' ? 'o ' + scopeWord : 'a ' + scopeWord }} por {{ forfeitReasonText }}.
                                </template>
                            </template>

                            <template v-else>
                                <template v-if="isDraw">
                                    Empate — sem marcas atribuídas
                                </template>

                                <template v-else-if="isWin">
                                    Ganhaste
                                    <strong class="text-emerald-400">
                                        {{ myMarks }} {{ myMarks === 1 ? 'marca' : 'marcas' }}
                                    </strong>
                                </template>

                                <template v-else>
                                    O adversário ganhou
                                    <strong class="text-red-400">
                                        {{ opponentMarks }} {{ opponentMarks === 1 ? 'marca' : 'marcas' }}
                                    </strong>
                                </template>
                            </template>
                        </template>
                    </div>

                    <div v-if="showNextGameReady" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3 text-xs font-semibold">
                            <div class="rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-slate-200">
                                <span class="block truncate text-slate-300">{{ meNickname }}</span>
                                <span :class="meReady ? 'text-emerald-300' : 'text-amber-300'">{{ meReady ? 'Pronto' : 'Por confirmar' }}</span>
                            </div>
                            <div class="rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-slate-200">
                                <span class="block truncate text-slate-300">{{ opponentNickname }}</span>
                                <span :class="opponentReady ? 'text-emerald-300' : 'text-amber-300'">{{ opponentReady ? 'Pronto' : 'Por confirmar' }}</span>
                            </div>
                        </div>
                        <div v-if="countdownActive" class="space-y-2">
                            <p class="text-xs text-emerald-200">A preparar o jogo {{ nextGameNumber }}...</p>
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-2 border-emerald-400/60 bg-emerald-500/10 text-2xl font-bold text-emerald-200">
                                {{ countdownSeconds }}
                            </div>
                        </div>
                        <div v-if="!countdownActive" class="flex flex-col gap-3 sm:flex-row">
                            <Button
                                class="flex-1 text-base py-4 font-semibold rounded-xl shadow-lg shadow-black/20"
                                :class="primaryButtonColor"
                                :disabled="readyTogglePending || forfeitPending"
                                @click="onPrimary">
                                <span class="inline-flex items-center justify-center gap-2">
                                    <CircleCheck class="w-4 h-4 opacity-90" />
                                    <span>{{ primaryButtonText }}</span>
                                </span>
                            </Button>
                            <Button
                                class="flex-1 text-base py-4 font-semibold rounded-xl shadow-lg shadow-black/20 bg-red-600 hover:bg-red-500 text-white"
                                :disabled="forfeitPending || readyTogglePending"
                                @click="onForfeit">
                                <span class="inline-flex items-center justify-center gap-2">
                                    <Flag class="w-4 h-4 opacity-90" />
                                    <span>{{ forfeitPending ? 'A sair...' : 'Desistir' }}</span>
                                </span>
                            </Button>
                        </div>
                    </div>
                    <Button
                        v-else
                        class="w-full text-base py-4 font-semibold rounded-xl shadow-lg shadow-black/20"
                        :class="primaryButtonColor"
                        :disabled="readyTogglePending || forfeitPending"
                        @click="onPrimary">
                        <CircleCheck class="w-4 h-4 opacity-90" />
                        {{ primaryButtonText }}
                    </Button>
                </CardContent>
            </Card>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { useGameStore } from "@/stores/gameStore";
import { useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { useWsStore } from "@/stores/ws";
import { useAuthStore } from "@/stores/auth";
import { Flag, CircleCheck } from "lucide-vue-next";

import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import api from "@/services/api";

const game = useGameStore();
const router = useRouter();
const wsStore = useWsStore();

const visible = computed(() => !!game.resultModal);

const modal = computed(() => game.resultModal || {});

const isWin = computed(() => modal.value.winner === "me");
const isDraw = computed(() => modal.value.isDraw);
const isLoss = computed(() => !isWin.value && !isDraw.value);

const isForfeit = computed(() => game.state?.matchForfeited === true);

const displayMarks = computed(() => {
    if (isForfeit.value) {
        return isWin.value ? { me: 4, opponent: 0 } : { me: 0, opponent: 4 };
    }
    return {
        me: modal.value.marks?.me ?? 0,
        opponent: modal.value.marks?.opponent ?? 0,
    };
});

const myMarks = computed(() => displayMarks.value.me);
const opponentMarks = computed(() => displayMarks.value.opponent);
const matchMarksMe = computed(() =>
    isForfeit.value ? (isWin.value ? 4 : 0) : (game.matchMarks?.me ?? 0)
);
const matchMarksOpponent = computed(() =>
    isForfeit.value ? (isWin.value ? 0 : 4) : (game.matchMarks?.opponent ?? 0)
);

const mePoints = computed(() => modal.value?.mePoints ?? game.state?.me?.score ?? 0);
const opponentPoints = computed(() => modal.value?.opponentPoints ?? game.state?.opponent?.score ?? 0);

const forfeitReason = computed(() => game.state?.forfeitReason);

const forfeitReasonText = computed(() =>
    forfeitReason.value === "timeout" ? "timeout" : "desistência"
);

const forfeitText = computed(() => {
    if (!isForfeit.value) return "";
    const reasonText = forfeitReason.value === "timeout"
        ? "timeout"
        : "desistência";
    if (isWin.value) {
        return `Vitória por ${reasonText} do adversário.`;
    }
    return `Derrota por ${reasonText}.`;
});

const headerColor = computed(() => {
    if (isWin.value) return "bg-green-600/90 text-white";
    if (isDraw.value) return "bg-slate-600/90 text-white";
    return "bg-red-600/90 text-white";
});

const riscaBoxColor = computed(() => {
    if (isDraw.value)
        return "bg-slate-600/20 border-slate-400/40 text-slate-200";
    return isWin.value
        ? "bg-emerald-600/15 border-emerald-500/30 text-emerald-300"
        : "bg-red-600/15 border-red-500/30 text-red-300";
});

const isMatchMode = computed(() => game.sessionMode === "match");
const isOnlineMatch = computed(() => game.mode === "online" && isMatchMode.value);

// Calcular o número do jogo do modal baseado no jogo que terminou
// Se o modal tem gameNumber guardado, usar esse, caso contrário calcular
const modalGameNumber = computed(() => {
    if (!isMatchMode.value) return null;
    // Se o modal tem o número do jogo guardado, usar esse
    if (modal.value.gameNumber != null) {
        return modal.value.gameNumber;
    }
    // Se há um próximo jogo pendente, o jogo atual terminou, então o número é matchGameNumber - 1
    // Caso contrário, usar o matchGameNumber atual
    if (game.pendingNextGameId && game.matchGameNumber > 1) {
        return game.matchGameNumber - 1;
    }
    return game.matchGameNumber ?? 1;
});
// Número do próximo jogo a exibir no countdown, usando o jogo terminado como base
const nextGameNumber = computed(() => {
    const base = modalGameNumber.value ?? game.matchGameNumber ?? 1;
    return base + 1;
});
const matchDecided = computed(() => {
    if (!isMatchMode.value) return false;
    const marksReached = matchMarksMe.value >= 4 || matchMarksOpponent.value >= 4;
    if (!marksReached) {
        return false;
    }
    if (showNextGameReady.value && game.pendingNextGameId) {
        return false;
    }
    return true;
});
const matchWinner = computed(() => {
    if (!matchDecided.value) return null;
    if (matchMarksMe.value > matchMarksOpponent.value) return "me";
    if (matchMarksOpponent.value > matchMarksMe.value) return "opponent";
    return null;
});
const scopeWord = computed(() => (isMatchMode.value ? "partida" : "jogo"));

const primaryButtonText = computed(() => {
    if (isForfeit.value) return "Terminar Partida";
    if (showNextGameReady.value) {
        if (readyTogglePending.value) return "A confirmar...";
        return meReady.value ? "Não pronto" : "Ficar pronto";
    }
    return isMatchMode.value ? "Continuar" : "Terminar";
});

const primaryButtonColor = computed(() => {
    return isForfeit.value
        ? "bg-red-600 hover:bg-red-500"
        : "bg-emerald-600 hover:bg-emerald-500";
});

const showNextGameReady = computed(() =>
    isOnlineMatch.value && !matchDecided.value && !isForfeit.value
);
const meReady = computed(() =>
    showNextGameReady.value
        ? (game.pendingNextReady?.me ?? game.state?.ready?.me ?? false)
        : (game.state?.ready?.me ?? false)
);
const opponentReady = computed(() =>
    showNextGameReady.value
        ? (game.pendingNextReady?.opponent ?? game.state?.ready?.opponent ?? false)
        : (game.state?.ready?.opponent ?? false)
);
const meNickname = computed(() => game.state?.me?.nickname ?? game.playerNames?.me ?? "Tu");
const opponentNickname = computed(() =>
    game.state?.opponent?.nickname ?? game.playerNames?.opponent ?? "Adversário"
);
const countdownActive = ref(false);
const countdownSeconds = ref(5);
let countdownTimer = null;
const readyTogglePending = ref(false);
let readyToggleTimeout = null;
const forfeitPending = ref(false);

function resetCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
    countdownActive.value = false;
    countdownSeconds.value = 5;
}

function clearReadyPending() {
    if (readyToggleTimeout) {
        clearTimeout(readyToggleTimeout);
        readyToggleTimeout = null;
    }
    readyTogglePending.value = false;
}

async function onForfeit() {
    if (forfeitPending.value) return;
    forfeitPending.value = true;
    try {
        await game.resign();
        game.pendingNextGameId = null;
        game.resultModal = null;
        router.replace({ name: "play" });
    } catch (err) {
        const message =
            err?.response?.data?.message ??
            err?.message ??
            "Não foi possível desistir.";
        toast.error(message, { id: "match-forfeit-error" });
    } finally {
        forfeitPending.value = false;
    }
}

let ensureNextGamePromise = null;
async function ensureNextGameId() {
    if (game.pendingNextGameId) return game.pendingNextGameId;
    if (!game.matchId) return null;
    if (ensureNextGamePromise) return ensureNextGamePromise;

    ensureNextGamePromise = (async () => {
        const auth = useAuthStore();
        const myId = auth.user?.id ?? null;
        let matchData = null;

        // 1) Tentar reutilizar um active_game já criado pelo backend para evitar gerar jogos a mais
        try {
            const matchRes = await api.get(`/matches/${game.matchId}`);
            matchData = matchRes.data?.match ?? matchRes.data;
            const activeGame = matchRes.data?.active_game ?? matchData?.active_game ?? null;
            const existingId = activeGame?.id ?? null;
            if (existingId) {
                game.pendingNextGameId = existingId;
                if (wsStore.ws && wsStore.ws.readyState === WebSocket.OPEN) {
                    wsStore.send({ type: "join_game", gameId: existingId });
                }
                return existingId;
            }
        } catch (err) {
            console.warn("Falha a verificar active_game antes de criar novo", err?.response?.status || err);
        }

        // 2) Caso não haja active_game, apenas o criador do match deve pedir criação
        const ownerId =
            matchData?.player1_user_id ??
            matchData?.player1?.id ??
            null;
        const isOwner = myId != null && ownerId != null && Number(myId) === Number(ownerId);
        if (!isOwner) {
            return null;
        }

        // 3) Pedir criação do próximo jogo (apenas uma vez por cliente)
        try {
            const res = await api.post(`/matches/${game.matchId}/next-game`);
            const nextId = res.data?.active_game?.id ?? null;
            if (nextId) {
                game.pendingNextGameId = nextId;
                wsStore.send({
                    type: "join_game",
                    gameId: nextId,
                });
            }
            return nextId;
        } catch (err) {
            const message =
                err?.response?.data?.message ??
                err?.message ??
                "Não foi possível iniciar o próximo jogo.";
            toast.error(message, { id: "match-next-game-error" });
            return null;
        }
    })();

    const result = await ensureNextGamePromise;
    ensureNextGamePromise = null;
    return result;
}

function sendReadyToggle(nextReady) {
    const targetGameId = game.pendingNextGameId;
    if (!targetGameId) return;
    // Keep the socket joined to the actual next game room to avoid bouncing rooms in the WS server.
    if (!wsStore.ws || wsStore.ws.readyState !== WebSocket.OPEN) {
        toast.warning("Sem ligação ao lobby", {
            description: "Estamos a tentar reconectar. Volta a clicar em \"Pronto\" em instantes.",
        });
        if (wsStore.token) {
            wsStore.connect(wsStore.token);
        }
        return;
    }
    // Garantir que estamos na sala do próximo jogo
    wsStore.send({
        type: "join_game",
        gameId: targetGameId,
    });
    // Enviar o ready/unready com gameId e matchId corretos
    wsStore.send({
        type: nextReady ? "ready" : "unready",
        roomId: targetGameId, // Usar gameId como roomId
        gameId: targetGameId,
        matchId: game.matchId,
    });
}

async function toggleReadyDirect(nextReady) {
    const targetGameId = game.pendingNextGameId;
    if (!targetGameId) return;
    try {
        await api.post(`/games/${targetGameId}/${nextReady ? "ready" : "unready"}`);
        // Atualizar estado local imediatamente após confirmação da API para evitar regressões visuais
        const currentPending = game.pendingNextReady ?? { me: false, opponent: false };
        game.pendingNextReady = { ...currentPending, me: nextReady };

        // Buscar o estado atualizado do match para sincronizar o estado de ready
        if (game.matchId) {
            try {
                const matchRes = await api.get(`/matches/${game.matchId}`);
                const matchData = matchRes.data?.match ?? matchRes.data;
                const activeGame = matchRes.data?.active_game ?? null;
                if (matchData) {
                    const meId = game.state?.me?.id ?? game.playerNames?.meId ?? null;
                    const opponentId = game.state?.opponent?.id ?? game.playerNames?.opponentId ?? null;
                    game.applyReadySnapshotFromGame(activeGame ?? matchData, meId, opponentId);
                }
            } catch (matchErr) {
                console.warn("Falha a sincronizar estado do match após ready", matchErr);
                // Fallback: buscar estado do jogo diretamente
                const res = await api.get(`/games/${targetGameId}/state`);
                if (res.data?.state) {
                    game.updateState?.(res.data.state);
                }
            }
        } else {
            const res = await api.get(`/games/${targetGameId}/state`);
            if (res.data?.state) {
                game.updateState?.(res.data.state);
            }
        }
    } catch (err) {
        const message =
            err?.response?.data?.message ??
            err?.response?.data?.error ??
            "Não foi possível atualizar o estado de pronto.";
        toast.error(message, { id: "match-ready-error" });
    }
}

async function onPrimary() {
    const ended = game.state?.status === "Ended" || game.state?.matchForfeited || visible.value;
    if (ended && matchDecided.value) {
        game.resultModal = null;
        game.resetGameState();
        router.push("/play");
        return;
    }

    if (ended && !isOnlineMatch.value) {
        if (isMatchMode.value || game.matchGameNumber > 1) {
            await game.continueOfflineMatch();
            return;
        }
    }

    if (ended && showNextGameReady.value) {
        const nextId = await ensureNextGameId();
        if (!nextId) return;
        if (readyTogglePending.value) return;
        readyTogglePending.value = true;
        const newReadyState = !meReady.value;
        
        sendReadyToggle(newReadyState);
        toggleReadyDirect(newReadyState).finally(() => {
            readyToggleTimeout = setTimeout(() => {
                readyTogglePending.value = false;
                readyToggleTimeout = null;
            }, 1500);
        });
        return;
    }

    if (ended) {
        game.resultModal = null;
        game.resetGameState();
        router.push("/play");
        return;
    }
}

watch(
    () => [showNextGameReady.value, meReady.value, opponentReady.value, game.pendingNextGameId],
    ([showReady, me, opponent, nextId]) => {
        if (!showReady || !nextId) {
            resetCountdown();
            clearReadyPending();
            return;
        }
        
        // Não iniciar countdown imediatamente - aguardar um pouco para evitar falsos positivos
        // Isso evita que o countdown apareça quando o estado está sendo restaurado
        clearReadyPending();
        
        if (me && opponent && !countdownActive.value) {
            // Aguardar um delay maior antes de iniciar o countdown
            // Isso garante que o estado está estável após a sincronização inicial
            // e não é um falso positivo durante a restauração do estado
            const startCountdownDelay = setTimeout(() => {
                // Verificar novamente se ambos ainda estão prontos após o delay
                // E garantir que não estamos na sincronização inicial
                if (meReady.value && opponentReady.value && !countdownActive.value) {
                    countdownActive.value = true;
                    countdownSeconds.value = 5;
                    countdownTimer = setInterval(() => {
                        countdownSeconds.value -= 1;
                        if (countdownSeconds.value <= 0) {
                        resetCountdown();
                        const matchId = game.matchId;
                        const gameId = game.pendingNextGameId;
                        game.resultModal = null;
                        game.pendingNextGameId = null;
                        if (gameId) {
                            // Garantir que o store aponta para o novo jogo e sincronizar o estado ao fechar o modal
                            game.gameId = gameId;
                            api
                                .get(`/games/${gameId}/state`)
                                .then((res) => {
                                    if (res.data?.state) {
                                        game.updateState?.(res.data.state);
                                    }
                                })
                                .catch(() => {});
                        }
                        if (matchId) {
                            router.replace({ name: "match-game", params: { matchId } });
                        }
                    }
                }, 1000);
                }
            }, 800); // 800ms de delay para garantir que o estado está estável após sincronização
            
            // Guardar o timeout para poder cancelar se necessário
            if (readyToggleTimeout) {
                clearTimeout(readyToggleTimeout);
            }
            readyToggleTimeout = startCountdownDelay;
        }
        
        if ((!me || !opponent) && countdownActive.value) {
            resetCountdown();
        }
    }
);

let syncInterval = null;
let isInitialSync = true; // Flag para indicar se é a primeira sincronização

watch(
    () => [visible.value, showNextGameReady.value, game.pendingNextGameId],
    async ([isVisible, showReady, nextId]) => {
        if (!isVisible || !showReady) {
            if (syncInterval) {
                clearInterval(syncInterval);
                syncInterval = null;
            }
            isInitialSync = true; // Reset flag quando o modal fecha
            return;
        }
        if (!game.matchId) return;
        const finalNextId = nextId || await ensureNextGameId();
        if (!finalNextId) return;
        
        // Garantir que estamos na sala do próximo jogo via websocket
        if (wsStore.ws && wsStore.ws.readyState === WebSocket.OPEN) {
            wsStore.send({
                type: "join_game",
                gameId: finalNextId,
            });
        }
        
        // Função para sincronizar o estado
        const syncReadyState = async () => {
            // Não sincronizar se estivermos a processar um toggle para evitar conflitos
            if (readyTogglePending.value) return;
            try {
                const res = await api.get(`/matches/${game.matchId}`);
                const matchData = res.data?.match ?? res.data;
                const activeGame = res.data?.active_game ?? null;
                if (matchData) {
                    const meId = game.state?.me?.id ?? game.playerNames?.meId ?? null;
                    const opponentId = game.state?.opponent?.id ?? game.playerNames?.opponentId ?? null;
                    // Atualizar ready sempre que o backend tiver snapshot, mesmo que o jogo já esteja marcado como Playing.
                    // Usamos matchData como fonte principal para não perder ready_players do custom.
                    const snapshotSource = activeGame ?? matchData;
                    game.applyReadySnapshotFromGame(snapshotSource, meId, opponentId);
                }
                // Após a primeira sincronização, marcar como não inicial
                if (isInitialSync) {
                    isInitialSync = false;
                }
            } catch (err) {
                console.warn("Falha a sincronizar pronto do próximo jogo", err?.response?.status || err);
            }
        };
        
        // Sincronizar imediatamente
        await syncReadyState();
        
        // Sincronizar periodicamente a cada 2 segundos enquanto o modal estiver aberto
        if (syncInterval) {
            clearInterval(syncInterval);
        }
        syncInterval = setInterval(syncReadyState, 2000);
    }
);

watch(
    () => [visible.value, showNextGameReady.value],
    ([isVisible, showReady]) => {
        if (!isVisible || !showReady) return;
        if (game.state) {
            game.state = {
                ...game.state,
                ready: { me: false, opponent: false },
                readyPlayers: [],
            };
        }
        clearReadyPending();
    }
);

onBeforeUnmount(() => {
    resetCountdown();
    clearReadyPending();
    if (syncInterval) {
        clearInterval(syncInterval);
        syncInterval = null;
    }
});
</script>
