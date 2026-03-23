<template>
    <DefaultLayout>
        <section class="mx-auto max-w-6xl px-4 py-10 space-y-8 text-slate-100">
            <header class="space-y-3">
                <p class="text-xs uppercase tracking-[0.4em] text-emerald-300/80">Histórico</p>
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-semibold">Últimos jogos e partidas</h1>
                        <p class="text-sm text-slate-400">
                            Consulta jogos e partidas multiplayer finalizados com todos os detalhes: rondas, cartas e pontuações.
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <label class="flex items-center gap-2 rounded-2xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-300">
                            <span>Mostrar</span>
                            <select v-model.number="selectedLimit"
                                class="rounded-xl border border-slate-800 bg-slate-900/70 px-3 py-1 text-sm text-slate-100 focus:outline-none">
                                <option v-for="option in limitOptions" :key="option" :value="option">
                                    {{ option }} registos
                                </option>
                            </select>
                        </label>
                        <Button class="rounded-2xl border border-emerald-500/20 bg-emerald-500/15 px-4 py-2 text-sm font-medium text-emerald-100 hover:bg-emerald-500/25"
                            :disabled="isLoading" @click="fetchHistory">
                            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
                            Atualizar
                        </Button>
                    </div>
                </div>
            </header>

            <section class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950/80 via-slate-950/70 to-slate-900/70 p-6 shadow-[0_25px_60px_rgba(2,6,23,0.65)] space-y-6">
                <div v-if="isLoading" class="flex items-center justify-center gap-3 text-sm text-slate-400">
                    <Spinner class="text-emerald-300" />
                    A carregar histórico...
                </div>

                <div v-else-if="errorMessage" class="space-y-3 text-center">
                    <p class="text-sm text-rose-300">{{ errorMessage }}</p>
                    <Button class="btn btn-primary mx-auto" size="sm" @click="fetchHistory">
                        Tentar novamente
                    </Button>
                </div>

                <div v-else-if="matches.length === 0 && games.length === 0" class="space-y-2 text-center text-sm text-slate-400">
                    <p class="text-base font-semibold text-slate-100">Sem jogos ou partidas registadas</p>
                    <p>Assim que terminares jogos multiplayer vais encontrá-los aqui.</p>
                </div>

                <div v-else class="space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="inline-flex rounded-2xl border border-slate-800 bg-slate-900/70 p-1 text-sm">
                            <button
                                class="rounded-xl px-3 py-2 font-semibold transition"
                                :class="activeTab === 'matches'
                                    ? 'bg-emerald-600/20 text-emerald-100 border border-emerald-500/30 shadow-[0_10px_30px_rgba(16,185,129,0.25)]'
                                    : 'text-slate-300 hover:text-emerald-100'"
                                @click="activeTab = 'matches'">
                                Partidas ({{ matches.length }})
                            </button>
                            <button
                                class="rounded-xl px-3 py-2 font-semibold transition"
                                :class="activeTab === 'games'
                                    ? 'bg-emerald-600/20 text-emerald-100 border border-emerald-500/30 shadow-[0_10px_30px_rgba(16,185,129,0.25)]'
                                    : 'text-slate-300 hover:text-emerald-100'"
                                @click="activeTab = 'games'">
                                Jogos únicos ({{ games.length }})
                            </button>
                        </div>
                        <article v-for="game in games" :key="game.id"
                            class="rounded-2xl border border-slate-800/80 bg-slate-950/80 p-5 shadow-[0_18px_40px_rgba(2,6,23,0.55)] space-y-5 transition hover:-translate-y-0.5 hover:border-emerald-500/30 hover:shadow-[0_20px_50px_rgba(16,185,129,0.15)]">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-emerald-600/40 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                            Jogo
                                        </span>
                                        <span class="rounded-full border border-slate-800/70 bg-slate-900/60 px-3 py-1 text-xs font-semibold text-slate-300">
                                            {{ matchTypeLabel(game.type) }}
                                        </span>
                                        <span v-if="game.stake" class="rounded-full border border-emerald-600/40 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                            Stake {{ game.stake }} moedas
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-500">
                                        {{ matchDateLabel(game) }} · duração {{ formatDuration(durationSeconds(game)) }}
                                    </p>
                                </div>
                                <div class="text-sm text-slate-400 flex flex-col items-start gap-2 sm:items-end sm:text-right sm:gap-1">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide">Resultado</p>
                                        <p :class="resultLabelClasses(game)">
                                            {{ resultLabel(game) }}
                                        </p>
                                    </div>
                                    <RouterLink
                                        v-if="game.id"
                                        :to="{ name: 'player-standalone-game-detail', params: { gameId: game.id } }"
                                        class="rounded-lg border border-slate-800/70 bg-slate-900/70 px-3 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800/70 transition"
                                    >
                                        Ver detalhes
                                    </RouterLink>
                                </div>
                            </div>

                            <div class="grid gap-5 rounded-2xl border border-slate-800/70 bg-slate-950/70 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,160px)]">
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
                                    :class="playerCardClasses(game, 'player1')">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <PlayerBadge :player="game.player1" :isSelf="isCurrentUser(game.player1?.id)" />
                                        <span class="font-semibold text-lg"
                                            :class="scoreClassForGame(game, game.player1)">
                                            {{ game.player1_points ?? 0 }} pts
                                        </span>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
                                    :class="playerCardClasses(game, 'player2')">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <PlayerBadge :player="game.player2" :isSelf="isCurrentUser(game.player2?.id)" />
                                        <span class="font-semibold text-lg"
                                            :class="scoreClassForGame(game, game.player2)">
                                            {{ game.player2_points ?? 0 }} pts
                                        </span>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 text-center">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Trunfo</p>
                                    <p class="mt-3 text-3xl font-semibold text-slate-100">{{ formatCardSymbol(game.trump_card) }}</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <button type="button"
                                    class="flex w-full items-center justify-between rounded-xl border border-slate-800/70 bg-slate-950/70 px-4 py-3 text-left text-sm text-slate-200 hover:bg-slate-900/50 transition"
                                    @click="toggleGamePanel(game.id)">
                                    <span>Detalhes do jogo</span>
                                    <component :is="expandedGames[game.id] ? ChevronDown : ChevronRight" class="h-4 w-4 text-slate-400" />
                                </button>
                                <transition name="fade">
                                    <div v-if="expandedGames[game.id]" class="rounded-xl border border-slate-800/70 bg-slate-950/60 px-4 py-4">
                                        <div v-if="game.rounds.length === 0" class="text-xs text-slate-500">
                                            Este jogo não tem rondas registadas.
                                        </div>
                                        <div v-else class="overflow-x-auto">
                                            <table class="min-w-full text-xs text-slate-200">
                                                <thead>
                                                    <tr class="text-slate-400 text-[11px] uppercase tracking-wide bg-slate-950/60">
                                                        <th class="px-2 py-2 text-left">Ronda</th>
                                                        <th class="px-2 py-2 text-left">Cartas</th>
                                                        <th class="px-2 py-2 text-left">Pontos</th>
                                                        <th class="px-2 py-2 text-left">Vencedor</th>
                                                        <th class="px-2 py-2 text-left">Resultado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(round, roundIndex) in game.rounds" :key="round.round_number"
                                                        class="border-t border-slate-800/60 even:bg-slate-900/40">
                                                        <td class="px-2 py-2">
                                                            <span class="font-semibold text-slate-100">#{{ round.round_number }}</span>
                                                        </td>
                                                        <td class="px-2 py-2">
                                                            <div class="flex flex-col gap-1 text-slate-300">
                                                                <span>
                                                                    <span :class="playerNameClass(game.player1?.id)">{{ game.player1?.nickname ?? 'Jogador 1' }}</span> ·
                                                                    <strong :class="cardColorClass(round.player1_card)">
                                                                        {{ formatCard(round.player1_card) }}
                                                                    </strong>
                                                                </span>
                                                                <span>
                                                                    <span :class="playerNameClass(game.player2?.id)">{{ game.player2?.nickname ?? 'Jogador 2' }}</span> ·
                                                                    <strong :class="cardColorClass(round.player2_card)">
                                                                        {{ formatCard(round.player2_card) }}
                                                                    </strong>
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td class="px-2 py-2 text-slate-100">
                                                            {{ round.points_awarded ?? 0 }}
                                                        </td>
                                                        <td class="px-2 py-2">
                                                            <span :class="playerNameClass(round.winner?.id)">
                                                                {{ round.winner?.nickname ?? '—' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-2 py-2 text-slate-100">
                                                            <template v-for="totals in [roundTotalsData(game.rounds, roundIndex, game.player1, game.player2)]"
                                                                :key="round.round_number">
                                                                <span :class="playerNameClass(game.player1?.id)" class="pr-1">{{ totals.p1Name }}</span>
                                                                <span class="text-slate-400"> {{ totals.p1Total }} - {{ totals.p2Total }} </span>
                                                                <span :class="playerNameClass(game.player2?.id)" class="pl-1">{{ totals.p2Name }}</span>
                                                            </template>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </article>
                    </div>

                    <template v-if="activeTab === 'games'">
                        <div v-if="games.length" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-slate-100">Jogos recentes</h2>
                                <span class="text-xs uppercase tracking-wide text-slate-500">Jogos únicos</span>
                            </div>
                            <div class="text-sm text-slate-400 flex flex-col items-start gap-2 sm:items-end sm:text-right sm:gap-1">
                                <div>
                                    <p class="text-xs uppercase tracking-wide">Resultado</p>
                                    <p :class="resultLabelClasses(match)">
                                        {{ resultLabel(match) }}
                                    </p>
                                </div>
                                <RouterLink
                                    v-if="match.id"
                                    :to="{ name: 'player-match-detail', params: { id: match.id } }"
                                    class="rounded-lg border border-slate-800/70 bg-slate-900/70 px-3 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800/70 transition"
                                >
                                    Ver match
                                </RouterLink>
                            </div>
                        </div>

                        <div class="grid gap-5 rounded-2xl border border-slate-800/70 bg-slate-950/70 p-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
                                    :class="playerCardClasses(match, 'player1')">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <PlayerBadge :player="match.player1" :isSelf="isCurrentUser(match.player1?.id)" />
                                        <span class="rounded-full px-4 py-1.5 text-base font-semibold shadow-[0_8px_16px_rgba(2,6,23,0.45)]"
                                            :class="scoreClassForMatch(match, match.player1)">
                                            {{ match.player1_points ?? 0 }} pts
                                        </span>
                                    </div>
                                    <div class="text-sm text-slate-400">
                                        <p class="text-xs uppercase tracking-wide">Resultado</p>
                                        <p :class="resultLabelClasses(game)">
                                            {{ resultLabel(game) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid gap-5 rounded-2xl border border-slate-800/70 bg-slate-950/70 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(120px,160px)_minmax(0,1fr)]">
                                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 flex flex-col gap-3"
                                        :class="playerCardClasses(game, 'player1')">
                                        <div class="flex items-center justify-between gap-2">
                                            <PlayerBadge :player="game.player1" :isSelf="isCurrentUser(game.player1?.id)" />
                                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Jogador 1</span>
                                        </div>
                                        <div class="flex items-end justify-between">
                                            <p class="text-sm text-slate-400">Pontos</p>
                                            <span class="text-4xl font-bold leading-none"
                                                :class="scoreClassForGame(game, game.player1)">
                                                {{ game.player1_points ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 text-center flex flex-col items-center justify-center gap-2">
                                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Trunfo</p>
                                        <p class="text-3xl font-semibold text-slate-100">{{ formatCardSymbol(game.trump_card) }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 flex flex-col gap-3"
                                        :class="playerCardClasses(game, 'player2')">
                                        <div class="flex items-center justify-between gap-2">
                                            <PlayerBadge :player="game.player2" :isSelf="isCurrentUser(game.player2?.id)" />
                                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Jogador 2</span>
                                        </div>
                                        <div class="flex items-end justify-between">
                                            <p class="text-sm text-slate-400">Pontos</p>
                                            <span class="text-4xl font-bold leading-none"
                                                :class="scoreClassForGame(game, game.player2)">
                                                {{ game.player2_points ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <button type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-slate-800/70 bg-slate-950/70 px-4 py-3 text-left text-sm text-slate-200 hover:bg-slate-900/50 transition"
                                        @click="toggleGamePanel(game.id)">
                                        <span>Detalhes do jogo</span>
                                        <component :is="expandedGames[game.id] ? ChevronDown : ChevronRight" class="h-4 w-4 text-slate-400" />
                                    </button>
                                    <transition name="fade">
                                        <div v-if="expandedGames[game.id]" class="rounded-xl border border-slate-800/70 bg-slate-950/60 px-4 py-4">
                                            <div v-if="game.rounds.length === 0" class="text-xs text-slate-500">
                                                Este jogo não tem rondas registadas.
                                            </div>
                                            <div v-else class="overflow-x-auto">
                                                <table class="min-w-full text-xs text-slate-200">
                                                    <thead>
                                                        <tr class="text-slate-400 text-[11px] uppercase tracking-wide bg-slate-950/60">
                                                            <th class="px-2 py-2 text-left">Ronda</th>
                                                            <th class="px-2 py-2 text-left">Cartas</th>
                                                            <th class="px-2 py-2 text-left">Pontos</th>
                                                            <th class="px-2 py-2 text-left">Vencedor</th>
                                                            <th class="px-2 py-2 text-left">Resultado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(round, roundIndex) in game.rounds" :key="round.round_number"
                                                            class="border-t border-slate-800/60 even:bg-slate-900/40">
                                                            <td class="px-2 py-2">
                                                                <span class="font-semibold text-slate-100">#{{ round.round_number }}</span>
                                                            </td>
                                                            <td class="px-2 py-2">
                                                                <div class="flex flex-col gap-1 text-slate-300">
                                                                    <span>
                                                                        <span :class="playerNameClass(game.player1?.id)">{{ game.player1?.nickname ?? 'Jogador 1' }}</span> ·
                                                                        <strong :class="cardColorClass(round.player1_card)">
                                                                            {{ formatCard(round.player1_card) }}
                                                                        </strong>
                                                                    </span>
                                                                    <span>
                                                                        <span :class="playerNameClass(game.player2?.id)">{{ game.player2?.nickname ?? 'Jogador 2' }}</span> ·
                                                                        <strong :class="cardColorClass(round.player2_card)">
                                                                            {{ formatCard(round.player2_card) }}
                                                                        </strong>
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="px-2 py-2 text-slate-100">
                                                                {{ round.points_awarded ?? 0 }}
                                                            </td>
                                                            <td class="px-2 py-2">
                                                                <span :class="playerNameClass(round.winner?.id)">
                                                                    {{ round.winner?.nickname ?? '—' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-2 py-2 text-slate-100">
                                                                <template v-for="totals in [roundTotalsData(game.rounds, roundIndex, game.player1, game.player2)]"
                                                                    :key="round.round_number">
                                                                    <span :class="playerNameClass(game.player1?.id)" class="pr-1">{{ totals.p1Name }}</span>
                                                                    <span class="text-slate-400"> {{ totals.p1Total }} - {{ totals.p2Total }} </span>
                                                                    <span :class="playerNameClass(game.player2?.id)" class="pl-1">{{ totals.p2Name }}</span>
                                                                </template>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </article>
                        </div>
                        <div v-else class="rounded-2xl border border-slate-800/80 bg-slate-950/70 p-6 text-center text-sm text-slate-400">
                            Ainda não tens jogos únicos concluídos.
                        </div>
                    </template>

                    <template v-else>
                        <div v-if="matches.length" class="space-y-4">
                            <article v-for="match in matches" :key="match.id"
                                class="rounded-2xl border border-slate-800/80 bg-slate-950/80 p-5 shadow-[0_18px_40px_rgba(2,6,23,0.55)] space-y-5 transition hover:-translate-y-0.5 hover:border-emerald-500/30 hover:shadow-[0_20px_50px_rgba(16,185,129,0.15)]">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border border-blue-600/40 bg-blue-500/10 px-3 py-1 text-xs font-semibold text-blue-200">
                                                Partida
                                            </span>
                                            <span class="rounded-full border border-slate-800/70 bg-slate-900/60 px-3 py-1 text-xs font-semibold text-slate-300">
                                                {{ matchTypeLabel(match.type) }}
                                            </span>
                                            <span v-if="match.stake" class="rounded-full border border-emerald-600/40 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                                Stake {{ match.stake }} moedas
                                            </span>
                                        </div>
                                        <p class="text-sm text-slate-500">
                                            {{ matchDateLabel(match) }} · duração {{ formatDuration(durationSeconds(match)) }}
                                        </p>
                                    </div>
                                    <div class="text-sm text-slate-400">
                                        <p class="text-xs uppercase tracking-wide">Resultado</p>
                                        <p :class="resultLabelClasses(match)">
                                            {{ resultLabel(match) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid gap-5 rounded-2xl border border-slate-800/70 bg-slate-950/70 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,280px)]">
                                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 flex flex-col gap-3"
                                        :class="playerCardClasses(match, 'player1')">
                                        <div class="flex items-center justify-between gap-2">
                                            <PlayerBadge :player="match.player1" :isSelf="isCurrentUser(match.player1?.id)" />
                                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Jogador 1</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-xl border border-slate-800/80 bg-slate-900/70 p-3 text-center">
                                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Pontos</p>
                                                <p class="text-3xl font-semibold" :class="scoreClassForMatch(match, match.player1)">
                                                    {{ match.player1_points ?? 0 }}
                                                </p>
                                            </div>
                                            <div class="rounded-xl border border-slate-800/80 bg-slate-900/70 p-3 text-center">
                                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Marcas</p>
                                                <p class="text-3xl font-semibold" :class="scoreClassForMatch(match, match.player1)">
                                                    {{ match.player1_marks ?? 0 }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 flex flex-col gap-3"
                                        :class="playerCardClasses(match, 'player2')">
                                        <div class="flex items-center justify-between gap-2">
                                            <PlayerBadge :player="match.player2" :isSelf="isCurrentUser(match.player2?.id)" />
                                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Jogador 2</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-xl border border-slate-800/80 bg-slate-900/70 p-3 text-center">
                                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Pontos</p>
                                                <p class="text-3xl font-semibold" :class="scoreClassForMatch(match, match.player2)">
                                                    {{ match.player2_points ?? 0 }}
                                                </p>
                                            </div>
                                            <div class="rounded-xl border border-slate-800/80 bg-slate-900/70 p-3 text-center">
                                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Marcas</p>
                                                <p class="text-3xl font-semibold" :class="scoreClassForMatch(match, match.player2)">
                                                    {{ match.player2_marks ?? 0 }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full border border-slate-800/70 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold text-slate-200">
                                                {{ matchTypeLabel(match.type) }}
                                            </span>
                                            <span v-if="match.stake" class="rounded-full border border-emerald-600/40 bg-emerald-500/10 px-3 py-1 text-[11px] font-semibold text-emerald-200">
                                                Stake {{ match.stake }} moedas
                                            </span>
                                        </div>
                                        <div class="text-sm text-slate-400 space-y-1">
                                            <p class="text-xs uppercase tracking-wide">Resultado final</p>
                                            <p :class="resultLabelClasses(match)" class="text-lg font-semibold">
                                                {{ resultLabel(match) }}
                                            </p>
                                            <p>{{ matchDateLabel(match) }} · duração {{ formatDuration(durationSeconds(match)) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="match.games?.length" class="space-y-3">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Jogos do match</p>
                                    <div v-for="(game, gameIndex) in match.games" :key="game.id" class="rounded-2xl border border-slate-800 bg-slate-950/70">
                                <button type="button"
                                    class="flex w-full flex-col gap-2 px-4 py-3 text-left text-sm text-slate-200 md:flex-row md:items-center md:justify-between hover:bg-slate-900/50 transition"
                                    @click="toggleGamePanel(game.id)">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold">Jogo {{ gameIndex + 1 }} · {{ formatDate(game.began_at) }}</p>
                                        <p class="text-xs text-slate-400 flex flex-wrap items-center gap-2">
                                            <span>Pontuação: {{ game.player1_points ?? 0 }} - {{ game.player2_points ?? 0 }}</span>
                                            <span v-if="game.trump_card"
                                                class="inline-flex items-center gap-1 rounded-full border border-slate-800/70 bg-slate-900/60 px-2 py-1 text-[11px] text-slate-200">
                                                <span class="text-slate-400">Trunfo</span>
                                                <strong class="text-slate-100">{{ formatCardSymbol(game.trump_card) }}</strong>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400">
                                        <RouterLink
                                            v-if="match.id && game.id"
                                            :to="{ name: 'player-game-detail', params: { id: match.id, gameId: game.id } }"
                                            class="rounded-lg border border-slate-800/70 bg-slate-900/70 px-3 py-1 text-[11px] font-semibold text-slate-200 hover:bg-slate-800/70 transition"
                                            @click.stop
                                        >
                                            Ver jogo
                                        </RouterLink>
                                        <span class="font-medium text-slate-300">
                                            {{ formatGameResult(match, game) }}
                                        </span>
                                                <component :is="expandedGames[game.id] ? ChevronDown : ChevronRight" class="h-4 w-4 text-slate-400" />
                                            </div>
                                        </button>
                                        <transition name="fade">
                                            <div v-if="expandedGames[game.id]" class="border-t border-slate-800 px-4 py-4">
                                                <div v-if="game.rounds.length === 0" class="text-xs text-slate-500">
                                                    Este jogo não tem rondas registadas.
                                                </div>
                                                <div v-else class="overflow-x-auto">
                                                    <table class="min-w-full text-xs text-slate-200">
                                                        <thead>
                                                            <tr class="text-slate-400 text-[11px] uppercase tracking-wide bg-slate-950/60">
                                                                <th class="px-2 py-2 text-left">Ronda</th>
                                                                <th class="px-2 py-2 text-left">Cartas</th>
                                                                <th class="px-2 py-2 text-left">Pontos</th>
                                                                <th class="px-2 py-2 text-left">Vencedor</th>
                                                                <th class="px-2 py-2 text-left">Resultado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(round, roundIndex) in game.rounds" :key="round.round_number"
                                                                class="border-t border-slate-800/60 even:bg-slate-900/40">
                                                                <td class="px-2 py-2">
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="font-semibold text-slate-100">#{{ round.round_number }}</span>
                                                                        <span class="rounded-full border border-slate-800/70 bg-slate-900/60 px-2 py-0.5 text-[10px] text-slate-400">
                                                                            {{ round.final_phase ? 'Fase final' : 'Fase normal' }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-2 py-2">
                                                                    <div class="flex flex-col gap-1 text-slate-300">
                                                                        <span>
                                                                            <span :class="playerNameClass(match.player1?.id)">{{ match.player1?.nickname ?? 'Jogador 1' }}</span> ·
                                                                            <strong :class="cardColorClass(round.player1_card)">
                                                                                {{ formatCard(round.player1_card) }}
                                                                            </strong>
                                                                        </span>
                                                                        <span>
                                                                            <span :class="playerNameClass(match.player2?.id)">{{ match.player2?.nickname ?? 'Jogador 2' }}</span> ·
                                                                            <strong :class="cardColorClass(round.player2_card)">
                                                                                {{ formatCard(round.player2_card) }}
                                                                            </strong>
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-2 py-2 text-slate-100">
                                                                    {{ round.points_awarded ?? 0 }}
                                                                </td>
                                                                <td class="px-2 py-2">
                                                                    <span :class="playerNameClass(round.winner?.id)">
                                                                        {{ round.winner?.nickname ?? '—' }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-2 py-2 text-slate-100">
                                                                    <template v-for="totals in [roundTotalsData(game.rounds, roundIndex, match.player1, match.player2)]"
                                                                        :key="round.round_number">
                                                                        <span :class="playerNameClass(match.player1?.id)" class="pr-1">{{ totals.p1Name }}</span>
                                                                        <span class="text-slate-400"> {{ totals.p1Total }} - {{ totals.p2Total }} </span>
                                                                        <span :class="playerNameClass(match.player2?.id)" class="pl-1">{{ totals.p2Name }}</span>
                                                                    </template>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <div v-else class="rounded-2xl border border-slate-800/80 bg-slate-950/70 p-6 text-center text-sm text-slate-400">
                            Ainda não tens partidas concluídas.
                        </div>
                    </template>
                </div>
            </section>
        </section>
    </DefaultLayout>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { RefreshCw, ChevronDown, ChevronRight } from 'lucide-vue-next'

const auth = useAuthStore()

const matches = ref([])
const games = ref([])
const activeTab = ref('matches')
const isLoading = ref(false)
const errorMessage = ref('')
const selectedLimit = ref(5)
const limitOptions = [5, 10, 15, 20]
const expandedGames = ref({})
const ensureActiveTab = () => {
    if (activeTab.value === 'matches' && !matches.value.length && games.value.length) {
        activeTab.value = 'games'
    } else if (activeTab.value === 'games' && !games.value.length && matches.value.length) {
        activeTab.value = 'matches'
    }
}
watch([matches, games], ensureActiveTab, { deep: true })

const dateTimeFormatter = new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short'
})

function matchTypeLabel(type) {
    return type === '9' ? 'Bisca de 9' : 'Bisca de 3'
}

function matchStatusLabel(match) {
    if (match.status === 'Ended') {
        return match.winner?.id ? 'Match finalizado' : 'Match empatado'
    }
    if (match.status === 'Playing') {
        return 'Em jogo'
    }
    if (match.status === 'Interrupted') {
        return 'Interrompido'
    }
    return match.status
}

function statusChipClasses(match) {
    if (match.status === 'Ended') {
        return match.winner?.id
            ? 'border-emerald-600/40 bg-emerald-500/10 text-emerald-200'
            : 'border-amber-500/40 bg-amber-500/10 text-amber-200'
    }
    if (match.status === 'Interrupted') {
        return 'border-rose-600/40 bg-rose-500/10 text-rose-200'
    }
    return 'border-slate-700 bg-slate-900/60 text-slate-200'
}

function matchDateLabel(match) {
    if (match.ended_at) {
        return dateTimeFormatter.format(new Date(match.ended_at))
    }
    if (match.began_at) {
        return dateTimeFormatter.format(new Date(match.began_at))
    }
    return 'Sem dados'
}

function formatDuration(seconds) {
    if (!seconds || seconds <= 0) return '—'
    const total = Math.round(Number(seconds) || 0)
    const mins = Math.floor(total / 60)
    const secs = total % 60
    if (mins === 0) return `${secs}s`
    return `${mins}m ${secs.toString().padStart(2, '0')}s`
}

function formatDate(date) {
    if (!date) return 'Sem dados'
    return dateTimeFormatter.format(new Date(date))
}

function durationSeconds(item) {
    const total = Number(item?.total_time ?? 0)
    if (total > 0) return total
}

function resultLabel(match) {
    const viewerId = auth.user?.id
    if (match.status !== 'Ended') return 'Partida a decorrer'
    if (!match.winner?.id) return 'Empate'
    const reason = match.forfeit_reason
        ? (match.forfeit_reason === 'timeout' ? 'timeout' : 'desistência')
        : null
    if (match.winner.id === viewerId) {
        return reason ? `Vitória por ${reason}` : 'Vitória'
    }
    if (match.loser?.id === viewerId) {
        return reason ? `Derrota por ${reason}` : 'Derrota'
    }
    return 'Finalizada'
}

function resultLabelClasses(match) {
    const viewerId = auth.user?.id
    if (match.status !== 'Ended') return 'text-slate-300'
    if (!match.winner?.id) return 'text-amber-300'
    if (match.winner.id === viewerId) return 'text-emerald-300'
    if (match.loser?.id === viewerId) return 'text-rose-300'
    return 'text-slate-300'
}

function formatSuit(suit) {
    if (!suit) return '—'
    const map = {
        paus: '♣ Paus',
        copas: '♥ Copas',
        espadas: '♠ Espadas',
        ouros: '♦ Ouros',
        p: '♣ Paus',
        c: '♥ Copas',
        e: '♠ Espadas',
        o: '♦ Ouros'
    }
    return map[String(suit).toLowerCase()] ?? suit
}

function formatSuitSymbol(suit) {
    if (!suit) return '—'
    const map = {
        paus: '♣',
        copas: '♥',
        espadas: '♠',
        ouros: '♦',
        p: '♣',
        c: '♥',
        e: '♠',
        o: '♦'
    }
    return map[String(suit).toLowerCase()] ?? String(suit).toUpperCase()
}

function formatCard(card) {
    if (!card) return '—'
    if (typeof card === 'string') {
        const trimmed = card.trim()
        if (trimmed.length === 1) return formatSuit(trimmed)
        const compact = trimmed.replace(/\s+/g, '')
        const match = compact.match(/^(\d+|[A-Z]+)([OPCE])$/i)
        if (match) {
            const value = humanValue(match[1])
            const suit = match[2]
            return `${value} ${formatSuit(suit)}`
        }
        return card
    }
    const value = humanValue(card?.valor ?? card?.value ?? '?')
    const suit = card?.naipe ?? card?.suit ?? ''
    if (!suit) return `${value}`
    return `${value} ${formatSuit(suit)}`
}

function formatCardSymbol(card) {
    if (!card) return '—'
    if (typeof card === 'string') {
        const trimmed = card.trim()
        const compact = trimmed.replace(/\s+/g, '')
        const match = compact.match(/^(\d+|[A-Z]+)([OPCE])$/i)
        if (match) {
            const value = humanValue(match[1])
            const suit = match[2]
            return `${value}${formatSuitSymbol(suit)}`
        }
        return trimmed
    }
    const value = humanValue(card?.valor ?? card?.value ?? '?')
    const suit = card?.naipe ?? card?.suit ?? ''
    if (!suit) return `${value}`
    return `${value} ${formatSuitSymbol(suit)}`
}

function humanValue(raw) {
    const n = Number(raw)
    if (n === 1) return 'Ás'
    if (n === 11) return 'Valete'
    if (n === 12) return 'Dama'
    if (n === 13) return 'Rei'
    return String(raw)
}

function cardColorClass(card) {
    const suit = card?.naipe ?? card?.suit
    if (!suit) return 'text-slate-100'
    const reds = ['copas', 'ouros']
    return reds.includes(String(suit).toLowerCase()) ? 'text-rose-300' : 'text-slate-100'
}

function roundTotalsData(rounds, index, player1, player2) {
    if (!Array.isArray(rounds) || index == null) {
        return { p1Name: 'Jogador 1', p2Name: 'Jogador 2', p1Total: 0, p2Total: 0 }
    }
    const p1Id = player1?.id ?? null
    const p2Id = player2?.id ?? null
    let p1Total = 0
    let p2Total = 0
    for (let i = 0; i <= index && i < rounds.length; i += 1) {
        const round = rounds[i]
        const winnerId = round?.winner?.id ?? null
        const points = Number(round?.points_awarded ?? 0)
        if (winnerId && p1Id && winnerId === p1Id) {
            p1Total += points
        } else if (winnerId && p2Id && winnerId === p2Id) {
            p2Total += points
        }
    }
    const p1Name = player1?.nickname ?? 'Jogador 1'
    const p2Name = player2?.nickname ?? 'Jogador 2'
    return { p1Name, p2Name, p1Total, p2Total }
}

function matchWinner(match) {
    return match.winner?.id ?? null
}

function playerCardClasses(match, key) {
    const player = key === 'player1' ? match.player1 : match.player2
    const playerId = player?.id
    const winnerId = matchWinner(match)
    if (winnerId && playerId === winnerId) {
        return 'border-emerald-600/40 bg-emerald-500/10'
    }
    return 'border-slate-800 bg-slate-900/60'
}

function gameWinner(match, game) {
    if (!game || game.is_draw || !game.winner_user_id) return null
    if (game.player1?.id === game.winner_user_id) {
        return game.player1
    }
    if (game.player2?.id === game.winner_user_id) {
        return game.player2
    }
    if (match.player1?.id === game.winner_user_id) {
        return match.player1
    }
    if (match.player2?.id === game.winner_user_id) {
        return match.player2
    }
    if (auth.user?.id === game.winner_user_id) {
        return { id: auth.user.id, nickname: auth.user.nickname }
    }
    return null
}

function formatGameResult(match, game) {
    if (!game) return 'Sem dados'
    if (game.is_draw || !game.winner_user_id) {
        return 'Empate'
    }
    const winner = gameWinner(match, game)
    if (winner?.nickname) {
        return `${winner.nickname} venceu`
    }
    if (match.player1?.id === game.winner_user_id) {
        return `${match.player1.nickname} venceu`
    }
    if (match.player2?.id === game.winner_user_id) {
        return `${match.player2.nickname} venceu`
    }
    if (auth.user?.id === game.winner_user_id) {
        return `${auth.user.nickname} venceu`
    }
    return 'Vitória'
}

function gameResultClasses(game) {
    if (game?.is_draw || !game?.winner_user_id) {
        return 'text-amber-300'
    }
    return 'text-emerald-300'
}

function gamePoints(game) {
    return {
        player1: Number(game?.player1_points ?? 0),
        player2: Number(game?.player2_points ?? 0)
    }
}

function scoreClassForMatch(match, player) {
    const winnerId = matchWinner(match)
    const playerId = player?.id ?? null
    if (!winnerId || !playerId) return 'text-slate-100'
    return winnerId === playerId ? 'text-emerald-300' : 'text-rose-300'
}

function scoreClassForGame(game, player) {
    if (!game || game.is_draw || !game.winner_user_id) return 'text-slate-100'
    const playerId = player?.id ?? null
    if (!playerId) return 'text-slate-100'
    return game.winner_user_id === playerId ? 'text-emerald-300' : 'text-rose-300'
}

function isCurrentUser(id) {
    if (!id) return false
    return auth.user?.id === id
}

function playerNameClass(playerId) {
    return isCurrentUser(playerId)
        ? 'text-emerald-300 font-semibold'
        : 'text-slate-200'
}

function toggleGamePanel(gameId) {
    expandedGames.value = {
        ...expandedGames.value,
        [gameId]: !expandedGames.value[gameId]
    }
}

async function fetchHistory() {
    isLoading.value = true
    errorMessage.value = ''
    try {
        const { data } = await api.get('/player/matches/history', {
            params: { limit: selectedLimit.value }
        })
        games.value = Array.isArray(data?.games)
            ? data.games.filter(game => game?.status === 'Ended').map(game => ({
                ...game,
                rounds: Array.isArray(game.rounds) ? game.rounds : [],
                trump_card: game.trump_card ?? null,
                forfeit_reason: game.forfeit_reason ?? null
            }))
            : []
        matches.value = Array.isArray(data?.matches)
            ? data.matches.filter(match => match?.status === 'Ended').map(match => ({
                ...match,
                forfeit_reason: match.forfeit_reason ?? null,
                games: Array.isArray(match.games)
                    ? match.games.map(game => ({
                        ...game,
                        rounds: Array.isArray(game.rounds) ? game.rounds : [],
                        trump_card: game.trump_card ?? null
                    }))
                    : []
            }))
            : []
        expandedGames.value = {}
        ensureActiveTab()
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar o histórico.'
    } finally {
        isLoading.value = false
    }
}

const PlayerBadge = defineComponent({
    name: 'PlayerBadge',
    props: {
        player: { type: Object, required: false, default: null },
        isSelf: { type: Boolean, required: false, default: false }
    },
    setup(props) {
        const initials = computed(() => {
            const nickname = props.player?.nickname
            if (!nickname) return '??'
            return nickname
                .split(' ')
                .filter(Boolean)
                .map((part) => part[0])
                .join('')
                .slice(0, 2)
                .toUpperCase()
        })

        const displayName = computed(() => props.player?.nickname ?? 'Jogador')

        return () => {
            if (!props.player) return null
            return h(
                'span',
                {
                    class: [
                        'group inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold shadow-[0_8px_20px_rgba(2,6,23,0.45)] transition',
                        props.isSelf
                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
                            : 'border-slate-800/70 bg-slate-950/70 text-slate-200'
                    ].join(' ')
                },
                [
                    h(
                        'span',
                        {
                            class: [
                                'inline-flex h-6 w-6 items-center justify-center rounded-full text-[10px] uppercase ring-1 ring-inset',
                                props.isSelf
                                    ? 'bg-emerald-500/20 text-emerald-200 ring-emerald-400/40'
                                    : 'bg-slate-900 text-slate-200 ring-slate-700/60'
                            ].join(' ')
                        },
                        initials.value
                    ),
                    h(
                        'span',
                        { class: 'max-w-[120px] truncate' },
                        displayName.value
                    ),
                    h('span', {
                        class: [
                            'h-1.5 w-1.5 rounded-full',
                            props.isSelf ? 'bg-emerald-300' : 'bg-slate-500'
                        ].join(' ')
                    }),
                    props.isSelf
                        ? h(
                            'span',
                            { class: 'rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] uppercase tracking-wide text-emerald-200' },
                            'tu'
                        )
                        : null
                ].filter(Boolean)
            )
        }
    },
})

onMounted(fetchHistory)
watch(selectedLimit, fetchHistory)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
