export const DEFAULT_CONFIG = {
    initialDeckSize: 40,
    pointsToWin: 120,
    turnTimer: 20
}

export const GAME_MODES = {
    "bisca-3": {
        name: "Bisca de 3",
        cardsPerPlayer: 3,
        riscasToWin: 1
    },
    "bisca-9": {
        name: "Bisca de 9",
        cardsPerPlayer: 9,
        riscasToWin: 4
    }
}

export const GAME_TYPES = {
    "single-player": {
        offlineMode: true
    },
    "multiplayer": {
        offlineMode: false
    }
}

export function getModeConfig(type, mode) {
    const gameTypeCfg = GAME_TYPES[type]
    const gameModeCfg = GAME_MODES[mode]

    if (!gameTypeCfg || !gameModeCfg) return null

    return {
        ...DEFAULT_CONFIG,
        ...gameTypeCfg,
        ...gameModeCfg
    }
}