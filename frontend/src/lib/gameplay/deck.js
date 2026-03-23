import { CARTAS, NAIPE } from '@/constants/cards'
import { VALOR } from '@/constants/points'

export function createDeck() {
  const deck = []

  Object.values(NAIPE).forEach(naipe => {
    Object.values(VALOR).forEach(valor => {
      const key = `${naipe}${valor}`
      deck.push(CARTAS[key])
    })
  })

  return deck
}

export function shuffleDeck(deck) {
  const d = [...deck]

  for (let i = d.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[d[i], d[j]] = [d[j], d[i]]
  }

  return d
}
