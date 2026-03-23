import axios from "axios";

export default async function handleAuth(ws, msg, LARAVEL_URL, registerUserSocket, onAuthenticated) {
    const token = msg.token;

    if (!token) {
        ws.send(JSON.stringify({ type: "auth_error", message: "Token missing" }));
        return ws.close();
    }

    const base = LARAVEL_URL.replace(/\/+$/, "");
    const endpoints = [
        `${base}/api/profile/self`,
        `${base}/api/player/profile/self`,
    ];

    try {
        let res = null;
        let lastError = null;
        for (const url of endpoints) {
            try {
                res = await axios.get(url, {
                    headers: { Authorization: `Bearer ${token}` },
                });
                if (res?.data) break;
            } catch (err) {
                lastError = err;
            }
        }
        if (!res) throw lastError ?? new Error("Unable to fetch user profile");

        ws.user = res.data;
        ws.authToken = token;
        registerUserSocket?.(ws);
        onAuthenticated?.(ws);

        ws.send(JSON.stringify({ type: "auth_ok", user: ws.user }));
    } catch (err) {
        console.error("WS auth failed", err?.response?.status, err?.response?.data || err?.message);
        ws.send(JSON.stringify({ type: "auth_error", message: "Invalid token" }));
        ws.close();
    }
}
