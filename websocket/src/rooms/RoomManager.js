export class RoomManager {
    constructor() {
        this.rooms = {};
    }

    join(roomId, ws) {
        ws.rooms = ws.rooms ?? new Set();
        if (!this.rooms[roomId]) this.rooms[roomId] = new Set();
        this.rooms[roomId].add(ws);
        ws.rooms.add(roomId);
    }

    leave(ws, roomId = null) {
        if (!ws.rooms) return;
        const targets = roomId ? [roomId] : Array.from(ws.rooms);
        for (const id of targets) {
            const room = this.rooms[id];
            if (room) {
                room.delete(ws);
                if (room.size === 0) delete this.rooms[id];
            }
            ws.rooms.delete(id);
        }
    }

    get(roomId) {
        return this.rooms[roomId] || null;
    }

    isMember(roomId, ws) {
        const room = this.get(roomId);
        if (!room || !ws) return false;
        return room.has(ws);
    }
}
