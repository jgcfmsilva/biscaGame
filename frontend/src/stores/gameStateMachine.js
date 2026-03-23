export function createGameStateMachine(ctx, onTransition) {
  let current = ctx.status || "LOADING"

  const states = {
    LOADING: {
        events: {
            START_MATCH: "DEALING",
        },
    },
    DEALING: {
        enter(ctx) {
            ctx.startDealing?.()
            ctx.stopTurnTimer?.()
        },
        events: {
            DEAL_COMPLETE: "PLAYING_TURN",
        },
    },
    PLAYING_TURN: {
      enter(ctx) {
        ctx.startTurnTimer?.()
        if (ctx.config?.offlineMode && ctx.currentTurn === 'opponent' && !ctx.opponent.playedCard) {
          setTimeout(() => {
            ctx.opponentAutoPlay?.()
          }, 1300)
        }
      },
      events: {
        BOTH_PLAYED: "RESOLVING_ROUND",
      },
    },
    RESOLVING_ROUND: {
      enter(ctx) {
        ctx.stopTurnTimer?.()
        ctx.resolveRound?.()
      },
      events: {
        ROUND_RESOLVED: "BUYING",
      },
    },
    BUYING: {
      enter(ctx) {
        ctx.stopTurnTimer?.()
        ctx.performBuyPhase?.()
      },
      events: {
        BUY_COMPLETE: "PLAYING_TURN",
        FINAL_PHASE: "FINAL_PHASE",
      },
    },
    FINAL_PHASE: {
        enter(ctx) {
            ctx.stopTurnTimer?.()
            ctx.finishFinalPhase?.()
        },
        events: {
            FINAL_PHASE_DONE: "CHECK_GAME_END",
        },
    },
    CHECK_GAME_END: {
        enter(ctx) {
            if (ctx.matchForfeited && ctx.lastRoundResult) {
              return
            }
            ctx.finishGame?.()
        },
        events: {
            GAME_FINISHED: "GAME_END",
            START_MATCH: "DEALING",
        },
    },
    GAME_END: {
        enter(ctx) {
            ctx.stopTurnTimer?.()
            ctx.finishMatch?.()
        },
        events: {
            START_MATCH: "DEALING"
        },
    },
  }

  function transition(event, data) {
    if (event === "BOTH_PLAYED") {
      if (!ctx.player.playedCard || !ctx.opponent.playedCard) {
          console.warn("BOTH_PLAYED fired but not both cards were played")
          return current
      }
  }
    
    if (event === "BOTH_PLAYED" && current !== "PLAYING_TURN") {
      return current
    }

    if (current === "RESOLVING_ROUND" && event === "BOTH_PLAYED") {
      return current
    }

    const state = states[current]

    if (!state.events || !state.events[event]) {
      console.warn(`No transition for event '${event}' from state '${current}'`)
      return current
    }

    const next = state.events[event]

    if (!states[next]) {
      console.error(`State '${next}' does not exist in machine`)
      return current
    }

    current = next

    states[current]?.enter?.(ctx, data)

    if (onTransition) {
      onTransition(current, data)
    }

    return current
  }

  function force(next, data = null) {
    if (!states[next]) {
      console.error(`Cannot force to non-existent state '${next}'`)
      return
    }

    current = next
    states[current]?.enter?.(ctx, data)

    if (onTransition) {
      onTransition(current, data)
    }
  }

  return {
    get state() {
      return current
    },
    transition,
    force,
  }
}