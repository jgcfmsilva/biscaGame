import { drawCardFromDeck } from "./deal"

export function executeBuyPhase(store) {
    const winner = store.roundWinner
    const loser = winner === "player" ? "opponent" : "player"

    const first = winner
    const second = loser

    const r1 = drawCardFromDeck(store.deck)
    if (r1.card) store[first].hand.push(r1.card)

    const r2 = drawCardFromDeck(store.deck)
    if (r2.card) store[second].hand.push(r2.card)

    return {
        deckEmpty: store.deck.length === 0
    }
}
