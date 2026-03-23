export function drawCards(deck, n) {
  const hand = []
  for (let i = 0; i < n; i++) {
    const card = deck.shift()
    if (!card) break
    hand.push(card)
  }
  return hand
}

export function dealInitialHands(deck, cardsPerPlayer) {
  return {
    player: drawCards(deck, cardsPerPlayer),
    opponent: drawCards(deck, cardsPerPlayer),
  }
}

export function drawCardFromDeck(deck) {
    if (deck.length === 0) {
        return { card: null }
    }

    if (deck.length > 1) {
        return { card: deck.shift() }
    }

    const card = deck.shift()

    return {
        card,
        trumpTaken: true
    }
}
