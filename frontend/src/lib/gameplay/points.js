export function cardPoints(card) {
    switch (card.valor) {
        case 1: return 11   // Ás
        case 7: return 10   // Sete
        case 13: return 4   // Rei
        case 11: return 3   // Valete
        case 12: return 2   // Dama
        default: return 0
    }
}

export function totalPoints(cardA, cardB) {
    return cardPoints(cardA) + cardPoints(cardB)
}

export function calculateRiscas(playerPoints, opponentPoints) {
    if (playerPoints === opponentPoints) return { player: 0, opponent: 0 }

    const winner = playerPoints > opponentPoints ? "player" : "opponent"
    const winnerPoints = Math.max(playerPoints, opponentPoints)
    
    let marks = 0
    
    if (winnerPoints >= 120) marks = 4     // Bandeira
    else if (winnerPoints >= 91) marks = 2 // Capote
    else if (winnerPoints >= 61) marks = 1 // Risca normal
    else marks = 0

    return {
        player: winner === "player" ? marks : 0,
        opponent: winner === "opponent" ? marks : 0
    }
}