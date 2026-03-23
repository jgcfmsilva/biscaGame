import axios from "axios";

export default async function handleUnready(ws, msg, rooms, apiBase) {
    const roomId = msg.roomId;
    const gameId = msg.gameId || roomId;
    const matchId = msg.matchId || null;

    if (!ws.user?.id || !ws.authToken) {
        return ws.send(JSON.stringify({ type: "error", message: "Authentication required" }));
    }

    if (!roomId) {
        return ws.send(JSON.stringify({ type: "error", message: "roomId is required" }));
    }

    try {
        if (matchId) {
            rooms.join(matchId, ws);
        }
        await axios.post(
            `${apiBase}/api/games/${gameId}/unready`,
            {},
            { headers: { Authorization: `Bearer ${ws.authToken}` } }
        );
        if (!rooms.isMember(gameId, ws)) {
            rooms.join(gameId, ws);
        }
        // Keep the socket in the game room; match room would replace ws.roomId in RoomManager.

        const payload = {
            type: "ready_state",
            roomId: gameId, // Sempre usar gameId como roomId para garantir matching correto
            gameId,
            matchId: msg.matchId ?? null,
            userId: ws.user.id,
            ready: false,
        };

        const gameRoom = rooms.get(gameId);
        if (gameRoom) {
            for (const client of gameRoom) {
                if (client.readyState === 1) {
                    client.send(JSON.stringify(payload));
                }
            }
        }

        // Também enviar para a sala do match se existir
        if (matchId) {
            const matchRoom = rooms.get(matchId);
            if (matchRoom) {
                for (const client of matchRoom) {
                    if (client.readyState === 1) {
                        client.send(JSON.stringify(payload));
                    }
                }
            }
        }
    } catch (err) {
        if (err?.response?.status === 401) {
            const message = err.response?.data?.message ?? "Autenticação necessária";
            ws.send(JSON.stringify({ type: "auth_error", message }));
            return;
        }
        const message =
            err.response?.data?.message ?? err.response?.data?.error ?? "Não foi possível remover o pronto.";
        ws.send(JSON.stringify({ type: "error", message }));
    }
}
