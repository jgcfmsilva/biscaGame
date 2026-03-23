export function sameSuit(a, b) {
    return a.naipe === b.naipe
}

const RANK = {
    1: 12,  // Ás
    7: 11,  // Sete
    13: 10, // Rei
    11: 9,  // Valete
    12: 8,  // Dama
    6: 7,
    5: 6,
    4: 5,
    3: 4,
    2: 3,
    10: 2,
    9: 1,
    8: 0,
}

function rankOf(valor) {
    return RANK[valor]
}

export function compareCards({ leadCard, followCard, trumpSuit }) {
    const leadSuit = leadCard.naipe
    const followSuit = followCard.naipe
    const trump = trumpSuit
    
    if (followSuit !== leadSuit) {
        return followSuit === trump ? "follow" : "lead"
    }

    const leadRank = rankOf(leadCard.valor)
    const followRank = rankOf(followCard.valor)

    return followRank > leadRank ? "follow" : "lead"
}
