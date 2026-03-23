export function opponentChooseCard(state) {
    const hand = state.opponent.hand
    const playerCard = state.player.playedCard

    if (!playerCard) {
        const random = Math.floor(Math.random() * hand.length)
        return random
    }

    const sameSuitCards = hand.filter(c => c.naipe === playerCard.naipe)

    if (sameSuitCards.length > 0) {
        return hand.indexOf(sameSuitCards[0])
    }

    return 0
}
