import { notifyUser } from "../users/UserRegistry.js";

export function handleRedisMessage(raw, rooms, wss) {
    const msg = JSON.parse(raw);

    if (msg.type === "state_update") {
        const room = rooms.get(msg.roomId);
        const { userStates } = msg;

        if (room) {
            for (const ws of room) {
                const userId = ws.user?.id;
                const stateForUser = userStates?.[userId];
                if (!stateForUser) continue;

                if (ws.readyState === 1) {
                    ws.send(
                        JSON.stringify({
                            type: "state_update",
                            state: stateForUser,
                        })
                    );
                }
            }
        }

        if (userStates) {
            for (const [userId, stateForUser] of Object.entries(userStates)) {
                if (!stateForUser) continue;
                notifyUser(Number(userId), {
                    type: "state_update",
                    state: stateForUser,
                });
            }
        }
    }

    if (msg.type === "ready_state") {
        const room = rooms.get(msg.roomId);
        if (!room) return;

        for (const ws of room) {
            if (ws.readyState === 1) {
                ws.send(
                    JSON.stringify({
                        type: "ready_state",
                        roomId: msg.roomId,
                        gameId: msg.gameId ?? null,
                        matchId: msg.matchId ?? null,
                        userId: msg.userId,
                        ready: Boolean(msg.ready),
                    })
                );
            }
        }
    }

    if (msg.type === "lobby_reset") {
        const room = rooms.get(msg.roomId);
        if (room) {
            for (const ws of room) {
                if (ws.readyState === 1) {
                    ws.send(JSON.stringify({
                        type: "lobby_reset",
                        roomId: msg.roomId,
                        reason: msg.reason ?? null,
                    }));
                }
            }
        }

        if (Array.isArray(msg.userIds)) {
            for (const userId of msg.userIds) {
                if (userId == null) continue;
                notifyUser(Number(userId), {
                    type: "lobby_reset",
                    roomId: msg.roomId,
                    reason: msg.reason ?? null,
                });
            }
        } else if (msg.userId != null) {
            notifyUser(Number(msg.userId), {
                type: "lobby_reset",
                roomId: msg.roomId,
                reason: msg.reason ?? null,
            });
        }
    }

    if (msg.type === "lobby_log_append") {
        const room = rooms.get(msg.roomId);
        if (room) {
            for (const ws of room) {
                if (ws.readyState === 1) {
                    ws.send(JSON.stringify({
                        type: "lobby_log_append",
                        roomId: msg.roomId,
                        entry: msg.entry ?? null,
                    }));
                }
            }
        }

        if (Array.isArray(msg.userIds)) {
            for (const userId of msg.userIds) {
                if (userId == null) continue;
                notifyUser(Number(userId), {
                    type: "lobby_log_append",
                    roomId: msg.roomId,
                    entry: msg.entry ?? null,
                });
            }
        }
    }

    if (msg.type === "lobby_kicked" && msg.userId) {
        const room = rooms.get(msg.gameId);
        if (room) {
            for (const ws of room) {
                if (ws.readyState === 1) {
                    ws.send(JSON.stringify({
                        type: "lobby_kicked",
                        userId: msg.userId,
                        gameId: msg.gameId ?? null,
                        matchId: msg.matchId ?? null,
                    }));
                }
            }
        }
        notifyUser(Number(msg.userId), {
            type: "lobby_kicked",
            userId: msg.userId,
            gameId: msg.gameId ?? null,
            matchId: msg.matchId ?? null,
        });
    }

    if (msg.type === "pending_ready" && msg.ownerId) {
        notifyUser(msg.ownerId, {
            type: "pending_ready",
            gameId: msg.gameId,
            game: msg.game ?? null,
        });
    }

    if (msg.type === "match_next_game") {
        if (Array.isArray(msg.userIds)) {
            for (const userId of msg.userIds) {
                if (userId == null) continue;
                notifyUser(Number(userId), {
                    type: "match_next_game",
                    matchId: msg.matchId ?? null,
                    gameId: msg.gameId ?? null,
                });
            }
        }
    }

    if (msg.type === "lobby_active_update") {
        for (const ws of wss.clients ?? []) {
            if (ws.readyState === 1) {
                ws.send(JSON.stringify({ type: "lobby_active_update" }));
            }
        }
    }

    if (msg.type === "balance_update" && msg.userId != null) {
        notifyUser(Number(msg.userId), {
            type: "balance_update",
            coins_balance: msg.coins_balance,
        });
    }

    if (msg.type === "players_online") {
        const payload = JSON.stringify({
            type: "players_online",
            count: msg.count ?? 0,
        });

        if (wss && wss.clients) {
            for (const client of wss.clients) {
                if (client.readyState === 1) {
                    client.send(payload);
                }
            }
        }
    }
}
