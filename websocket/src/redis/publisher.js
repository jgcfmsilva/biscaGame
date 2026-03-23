import { createClient } from "redis";

const redisPub = createClient();
await redisPub.connect();

export async function publish(channel, payload) {
    try {
        await redisPub.publish(channel, JSON.stringify(payload));
    } catch (e) {
        console.error("Redis publish error:", e);
    }
}
