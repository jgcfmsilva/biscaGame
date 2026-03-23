const userSockets = new Map();

export function getRegisteredUserIds() {
    return Array.from(userSockets.keys());
}

export function registerUserSocket(ws) {
    const userId = ws.user?.id;
    if (!userId) return;

    const key = String(userId);
    if (!userSockets.has(key)) {
        userSockets.set(key, new Set());
    }

    userSockets.get(key).add(ws);
}

export function unregisterUserSocket(ws) {
    const userId = ws.user?.id;
    if (!userId) return;

    const key = String(userId);
    const sockets = userSockets.get(key);
    if (!sockets) return;

    sockets.delete(ws);
    if (!sockets.size) {
        userSockets.delete(key);
    }
}

export function notifyUser(userId, payload) {
    const key = String(userId);
    const sockets = userSockets.get(key);
    if (!sockets) return;

    for (const ws of sockets) {
        if (ws.readyState === 1) {
            ws.send(JSON.stringify(payload));
        }
    }
}

export function hasActiveSocket(userId) {
    const key = String(userId);
    const sockets = userSockets.get(key);
    if (!sockets) return false;
    for (const ws of sockets) {
        if (ws.readyState === 1) return true;
    }
    return false;
}
