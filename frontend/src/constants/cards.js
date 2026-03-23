import { VALOR } from "@/constants/points"

export const NAIPE = {
  COPAS: "c",
  ESPADAS: "e",
  OUROS: "o",
  PAUS: "p",
}

export function cartaPath(naipe, valor) {
  return `/cards/${naipe}${valor}.png`
}

export const CARTAS = {}

Object.values(NAIPE).forEach((naipe) => {
  Object.values(VALOR).forEach((valor) => {
    const key = `${naipe}${valor}`
    CARTAS[key] = {
      naipe,
      valor,
      img: cartaPath(naipe, valor),
    }
  })
})

export const CARTA_VIRADA = {
  naipe: null,
  valor: null,
  img: "/cards/semFace.png",
}
