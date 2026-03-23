import axios from "axios";

export default async function handleJoinGame(ws, msg, rooms, apiBase) {
    const gameId = msg.gameId;

    if (!ws.user?.id || !ws.authToken) {
        return ws.send(JSON.stringify({ type: "error", message: "Authentication required" }));
    }

    if (!gameId) {
        return ws.send(JSON.stringify({ type: "error", message: "gameId missing" }));
    }

    rooms.join(gameId, ws);

    try {
        await axios.post(
            `${apiBase}/api/games/${gameId}/join`,
            {},
            { headers: { Authorization: `Bearer ${ws.authToken}` } }
        );
        ws.send(JSON.stringify({ type: "joined", roomId: gameId }));
    } catch (err) {
        rooms.leave(ws);
        if (err?.response?.status === 401) {
            const message = err.response?.data?.message ?? "Autenticação necessária";
            ws.send(JSON.stringify({ type: "auth_error", message }));
            return;
        }
        const message =
            err.response?.data?.message ?? err.response?.data?.error ?? "Não foi possível entrar no jogo.";
        ws.send(JSON.stringify({ type: "error", message }));
    }
}
