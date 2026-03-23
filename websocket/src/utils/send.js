export function send(ws, data) {
    if (!ws || ws.readyState !== 1) return; // 1 = OPEN

    try {
        ws.send(JSON.stringify(data));
    } catch (e) {
        console.error("WS send error:", e);
    }
}