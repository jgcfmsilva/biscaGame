import "dotenv/config";
import WebSocket, { WebSocketServer } from "ws";
import { createClient } from "redis";
import axios from "axios";

import { RoomManager } from "./src/rooms/RoomManager.js";
import handleAuth from "./src/handlers/auth.js";
import handleJoinGame from "./src/handlers/joinGame.js";
import handlePlayCard from "./src/handlers/playCard.js";
import handleResign from "./src/handlers/resign.js";
import handleReady from "./src/handlers/ready.js";
import handleUnready from "./src/handlers/unready.js";
import handleVideoSignal, { handleVideoDisconnect } from "./src/handlers/videoSignal.js";
import { handleRedisMessage } from "./src/redis/subscriber.js";
import { registerUserSocket, unregisterUserSocket, hasActiveSocket } from "./src/users/UserRegistry.js";
// ... imports ...

const PORT = process.env.NODE_PORT || 3000;
const LARAVEL_URL = process.env.NODE_BACKEND || "http://localhost:80";
const REDIS_HOST = process.env.REDIS_HOST || "127.0.0.1";
const REDIS_PORT = process.env.REDIS_PORT || "6379";
const REDIS_PASSWORD = process.env.REDIS_PASSWORD || "";
const REDIS_PREFIX = process.env.REDIS_PREFIX || "";
const REDIS_PASSWORD_VALUE =
    REDIS_PASSWORD && REDIS_PASSWORD !== "null" && REDIS_PASSWORD !== "undefined"
        ? REDIS_PASSWORD
        : "";
const API_BASE = LARAVEL_URL.replace(/\/+$/, "");

export const rooms = new RoomManager();

const redisSub = createClient({
    url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
    password: REDIS_PASSWORD_VALUE || undefined,
});
redisSub.on("error", (err) => {
    console.error("Redis subscriber error:", err?.message ?? err);
});

try {
    await redisSub.connect();
    const channel = `${REDIS_PREFIX}laravel_to_ws`;
    await redisSub.subscribe(channel, (message) => {
        handleRedisMessage(message, rooms, wss);
    });
} catch (err) {
    console.error("Redis subscriber connect failed:", err?.message ?? err);
}

const pendingLeaveChecks = new Map();
const wss = new WebSocketServer({ port: PORT });

wss.on("connection", (ws) => {
    const cancelScheduledLeave = (userId) => {
        if (!userId) return;
        const pending = pendingLeaveChecks.get(userId);
        if (pending) {
            clearTimeout(pending.timeoutId);
            pendingLeaveChecks.delete(userId);
        }
    };

    const scheduleLeave = (userId, gameId, token, delay = 5000, retries = 4) => {
        if (!userId || !gameId || !token) return;

        const runCheck = (remaining) => {
            const hasSocket = hasActiveSocket(userId);
            if (hasSocket) {
                pendingLeaveChecks.delete(userId);
                return;
            }

            if (remaining <= 0) {
                axios.post(
                    `${API_BASE}/api/games/${gameId}/leave-lobby`,
                    {},
                    { headers: { Authorization: `Bearer ${token}` } }
                ).catch((err) => {
                    console.error("leave_lobby failed", err?.response?.status, err?.message);
                });
                pendingLeaveChecks.delete(userId);
                return;
            }

            const nextTimeout = setTimeout(() => runCheck(remaining - 1), delay);
            pendingLeaveChecks.set(userId, { timeoutId: nextTimeout });
        };

        const timeoutId = setTimeout(() => runCheck(retries - 1), delay);
        pendingLeaveChecks.set(userId, { timeoutId });
    };

    ws.on("message", async (raw) => {
        let msg;
        try {
            msg = JSON.parse(raw);
        } catch {
            return ws.send(JSON.stringify({ type: "error", message: "Invalid JSON" }));
        }

        switch (msg.type) {
            case "auth":
                return handleAuth(ws, msg, LARAVEL_URL, registerUserSocket, () =>
                    cancelScheduledLeave(ws.user?.id)
                );

            case "join_game":
                return handleJoinGame(ws, msg, rooms, API_BASE);

            case "play_card":
                return handlePlayCard(ws, msg, rooms, API_BASE);

            case "resign":
                return handleResign(ws, msg, rooms, API_BASE);

            case "ready":
                return handleReady(ws, msg, rooms, API_BASE);

            case "unready":
                return handleUnready(ws, msg, rooms, API_BASE);

            case "video_signal":
                return handleVideoSignal(ws, msg, rooms);

            case "ping":
                return ws.send(JSON.stringify({ type: "pong", clientTs: msg.clientTs ?? null }));

            default:
                return ws.send(JSON.stringify({ type: "error", message: "Unknown message type" }));
        }
    });

    ws.on("close", () => {
        const previousRooms = ws.rooms ? Array.from(ws.rooms) : [];
        const previousUserId = ws.user?.id;
        const previousToken = ws.authToken;

        rooms.leave(ws);
        handleVideoDisconnect(ws); // Notify peer of disconnect
        unregisterUserSocket(ws);

        if (previousRooms.length && previousUserId) {
            for (const roomId of previousRooms) {
                scheduleLeave(previousUserId, roomId, previousToken);
            }
        }
    });
});
