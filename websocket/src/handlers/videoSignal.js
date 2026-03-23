import { notifyUser, hasActiveSocket, getRegisteredUserIds } from '../users/UserRegistry.js';

const activeCalls = new Map(); // userId -> Set<peerId>

export function handleVideoDisconnect(ws) {
    const userId = ws.user?.id;
    if (!userId || !activeCalls.has(userId)) return;

    const peers = activeCalls.get(userId);
    for (const peerId of peers) {
        // Notify peer that connection was lost/ended
        notifyUser(peerId, {
            type: 'video_signal',
            senderId: userId,
            senderName: ws.user?.nickname || 'Utilizador',
            payload: { ended: true, timedOut: true } // "timedOut" just to imply abrupt end
        });

        // Also cleanup the reverse mapping
        if (activeCalls.has(peerId)) {
            activeCalls.get(peerId).delete(userId);
            if (activeCalls.get(peerId).size === 0) activeCalls.delete(peerId);
        }
    }
    activeCalls.delete(userId);
}

export default function handleVideoSignal(ws, msg, rooms) {
    // msg: { type: 'video_signal', targetId: 123, payload: { sdp: ... } }
    // ... validation ...
    if (!ws.user) return; // ... logic handled below cleanly relative to original

    const targetId = msg.targetId;
    if (!targetId) {
        return ws.send(JSON.stringify({ type: 'error', message: 'Target ID required' }));
    }

    // Track the interaction (bidirectional)
    if (!activeCalls.has(ws.user.id)) activeCalls.set(ws.user.id, new Set());
    activeCalls.get(ws.user.id).add(targetId);

    if (!activeCalls.has(targetId)) activeCalls.set(targetId, new Set());
    activeCalls.get(targetId).add(ws.user.id);

    // Explicit 'ended' signal cleanup
    if (msg.payload?.ended) {
        if (activeCalls.has(ws.user.id)) activeCalls.get(ws.user.id).delete(targetId);
        if (activeCalls.has(targetId)) activeCalls.get(targetId).delete(ws.user.id);
    }

    if (!hasActiveSocket(targetId)) {
        // ... existing offline logic ...
        return ws.send(JSON.stringify({
            type: 'video_signal_error',
            message: 'User is offline',
            targetId
        }));
    }

    // ... existing routing logic ...
    const payload = msg.payload || {};
    notifyUser(targetId, {
        type: 'video_signal',
        senderId: ws.user.id,
        senderName: ws.user.nickname,
        payload
    });

    if (payload.reject === true) {
        notifyUser(ws.user.id, {
            type: 'video_signal_error',
            message: 'Chamada recusada pelo administrador.',
            targetId
        });
    }
}
