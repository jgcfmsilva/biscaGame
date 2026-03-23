import { totalPoints } from "./points"
import { compareCards } from "./rules"

export function resolveRound(store) {
    const lead = store.leadTurn
    const follow = lead === "player" ? "opponent" : "player"

    const leadCard = store[lead].playedCard
    const followCard = store[follow].playedCard

    if (!leadCard || !followCard) {
        console.error("resolveRound: cartas em falta (lead:", lead, ")")
        return { winner: null, points: 0 }
    }

    const trumpSuit = store.trumpCard?.naipe

    const winnerType = compareCards({
        leadCard,
        followCard,
        trumpSuit
    })

    const roundWinner = winnerType === "lead" ? lead : follow
    const points = totalPoints(leadCard, followCard)

    store[roundWinner].score += points
    store.roundWinner = roundWinner

    return {
        winner: roundWinner,
        points
    }
}
