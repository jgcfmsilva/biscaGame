import { defineStore } from 'pinia'
import { useAuthStore } from './auth'

export const useGameStore = defineStore('game', {
  state: () => ({
    roomId: null,
    serverState: {},
  }),

  actions: {
    listenToRoom(roomId) {
      this.roomId = roomId

      const auth = useAuthStore()

      auth.echo
        .private(`game.${roomId}`)
        .listen('.game.state', (state) => {
          this.applyServerState(state)
        })
    },

    applyServerState(state) {
      this.serverState = state
    },
  },
})
