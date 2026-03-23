import axios from "axios";

export default async function handlePlayCard(ws, msg, rooms, apiBase) {
    const roomId = msg.roomId ?? null;
    const { cardIndex } = msg;

    if (!ws.user?.id || !ws.authToken) {
        return ws.send(JSON.stringify({ type: "error", message: "Authentication required" }));
    }

    if (roomId == null || cardIndex == null) {
        return ws.send(
            JSON.stringify({ type: "error", message: "roomId and cardIndex are required" })
        );
    }

    if (rooms && !rooms.isMember(roomId, ws)) {
        return ws.send(JSON.stringify({ type: "error", message: "Not part of this lobby" }));
    }

    try {
        await axios.post(
            `${apiBase}/api/games/${roomId}/play-card`,
            { cardIndex },
            { headers: { Authorization: `Bearer ${ws.authToken}` } }
        );
    } catch (err) {
        if (err?.response?.status === 401) {
            const message = err.response?.data?.message ?? "Autenticação necessária";
            ws.send(JSON.stringify({ type: "auth_error", message }));
            return;
        }
        const message =
            err.response?.data?.message ??
            err.response?.data?.error ??
            "Jogada recusada pelo servidor.";
        ws.send(JSON.stringify({ type: "error", message }));
    }
}
