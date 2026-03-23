<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Database;

class GamesSeeder extends Seeder
{
    private $ratioStandaloneToMatch = 15;

    private function calculateRandomSeconds($filteredCollection)
    {
        $totalPlayers = $filteredCollection->count() + 1;
        return (12 * 60 * 60) / $totalPlayers + rand(0, 2000);
    }

    private function nextGameDateTime(&$d, $filteredPlayers, $withinSameMatch = false)
    {
        if ($withinSameMatch) {
            $deltaSegundos = rand(300, 900);
        } else {
            $deltaSegundos = $this->calculateRandomSeconds($filteredPlayers);
        }
        $d->addSeconds($deltaSegundos);
    }

    public function run(): void
    {
        $this->command->info("Games seeder - Start");

        $start = DB::table('users')->where('type', 'P')->min('created_at');

        $allPlayers = DB::table('users')->where('type', 'P')->get();
        $sortedPlayers = $allPlayers->sortBy('created_at')->values();

        $d = new \Carbon\Carbon($start);
        $d = $d->addDay();
        $now = \Carbon\Carbon::now();
        $this->command->info("Starting to create games");

        $games = [];
        $matches = [];

        $i = 0;

        $filteredPlayers = null;
        $filteredPlayersIds = null;
        $nextCreatedAt = null;

        while ($d->lte($now)) {
            $i++;
            if (($filteredPlayers === null) || ($nextCreatedAt === null) ||
                ($d->gte($nextCreatedAt))
            ) {
                $filteredPlayers = $allPlayers->filter(function ($value) use ($d) {
                    return $d->gt($value->created_at);
                });
                $nextCreatedAtPlayer = $sortedPlayers->first(function ($value) use ($d) {
                    return $d->lte($value->created_at);
                });
                $nextCreatedAt = $nextCreatedAtPlayer ? $nextCreatedAtPlayer->created_at : new \Carbon\Carbon('9999-12-31');
                $filteredPlayersIds = $filteredPlayers->pluck('id')->toArray();
            }

            // Only creates games or matches when there are enough players
            if ($filteredPlayersIds === null || empty($filteredPlayersIds) || count($filteredPlayersIds) < 2) {
                $this->nextGameDateTime($d, $filteredPlayers);
                continue;
            }

            $userIdKeys = array_rand($filteredPlayersIds, 2);
            $userIDPlayer1 = $filteredPlayersIds[$userIdKeys[0]];
            $userIDPlayer2 = $filteredPlayersIds[$userIdKeys[1]];

            if ($userIDPlayer1 == $userIDPlayer2) {
                $this->nextGameDateTime($d, $filteredPlayers);
                continue;
            }
            $match = null;
            if (rand(1, $this->ratioStandaloneToMatch) === 1) {
                $match = $this->newMatch($filteredPlayers, $userIDPlayer1, $userIDPlayer2, $d);
                $playersMarks = [0, 0];
                $playersPoints = [0, 0];
                while ($playersMarks[0] < 4 && $playersMarks[1] < 4) {
                    $newGame = $this->newGame($filteredPlayers, $match, $userIDPlayer1, $userIDPlayer2, $d);
                    $games[] = $newGame;
                    $playersPoints[0] += $newGame['player1_points'];
                    $playersPoints[1] += $newGame['player2_points'];
                    if ($newGame['player1_points'] > $newGame['player2_points']) {
                        if ($newGame['player1_points'] >= 120) {
                            $playersMarks[0] += 4;
                        } elseif ($newGame['player1_points'] >= 91) {
                            $playersMarks[0] += 2;
                        } else {
                            $playersMarks[0]++;
                        }
                    } elseif ($newGame['player2_points'] > $newGame['player1_points']) {
                        if ($newGame['player2_points'] >= 120) {
                            $playersMarks[1] += 4;
                        } elseif ($newGame['player2_points'] >= 91) {
                            $playersMarks[1] += 2;
                        } else {
                            $playersMarks[1]++;
                        }
                    }
                }
                $this->updateMatchWinner($match, $playersMarks[0], $playersMarks[1], $playersPoints[0], $playersPoints[1], $d);
                $matches[] = $match;
            } else {
                $newGame = $this->newGame($filteredPlayers, $match, $userIDPlayer1, $userIDPlayer2, $d);
                $games[] = $newGame;
            }

            if ($i >= DatabaseSeeder::$dbInsertBlockSize) {
                if (!empty($matches)) {
                    DB::table('matches')->insert($matches);
                    $this->command->info("Saved " . count($matches) . " matches at date " . $d->format('Y-m-d H:i:s'));
                }
                if (!empty($games)) {
                    DB::table('games')->insert($games);
                    $this->command->info("Saved " . count($games) . " games at date " . $d->format('Y-m-d H:i:s'));
                }
                $i = 0;
                $games = [];
                $matches = [];
            }
            //$this->nextGameDateTime($d, $filteredPlayers);
        }
        if (!empty($matches)) {
            DB::table('matches')->insert($matches);
            $this->command->info("Saved " . count($matches) . " matches at date " . $d->format('Y-m-d H:i:s'));
        }
        if (!empty($games)) {
            DB::table('games')->insert($games);
            $this->command->info("Saved " . count($games) . " games at date " . $d->format('Y-m-d H:i:s'));
        }
        $this->command->info("Games seeder - End");
    }

    private $matchID = 0;
    private function newMatch($filteredPlayers, $user1, $user2, $d)
    {
        $this->matchID++;
        $this->nextGameDateTime($d, $filteredPlayers);
        return [
            'id' => $this->matchID,
            'type' => random_int(1, 2) == 1 ? '3' : '9',
            'player1_user_id' => $user1,
            'player2_user_id' => $user2,
            'winner_user_id' => null,
            'loser_user_id' => null,
            'status' => 'Ended',
            'stake' => random_int(1, 4) > 1 ? 3 : random_int(4, 100),
            'began_at' => $d->copy(),
            'ended_at' => null,
            'total_time' => null,
            'player1_marks' => null,
            'player2_marks' => null,
            'player1_points' => null,
            'player2_points' => null,
            'custom' => null
        ];
    }

    private function updateMatchWinner(&$match, $player1Marks, $player2Marks, $totalPlayers1, $totalPlayers2, $d)
    {
        $match['player1_marks'] = $player1Marks;
        $match['player2_marks'] = $player2Marks;
        $match['player1_points'] = $totalPlayers1;
        $match['player2_points'] = $totalPlayers2;
        $match['ended_at'] = $d->copy();
        $match['total_time'] = $match['began_at']->diffInSeconds($match['ended_at']);
        $match['winner_user_id'] = $player1Marks > $player2Marks ? $match['player1_user_id'] : ($player2Marks > $player1Marks ? $match['player2_user_id'] : null);
        $match['loser_user_id'] = $player1Marks > $player2Marks ? $match['player2_user_id'] : ($player2Marks > $player1Marks ? $match['player1_user_id'] : null);
    }

    private $gameID = 0;
    private function newGame($filteredPlayers, $match, $user1, $user2, $d)
    {
        $this->gameID++;
        $this->nextGameDateTime($d, $filteredPlayers, $match != null);
        $begin_d = $d->copy();
        $pointsUser1 = 60;
        $pointsUser2 = 60;
        // if random == 1 it is a draw
        if (random_int(1, 30) > 1) {
            $pointsUser1 = rand(0, 120);
            $pointsUser2 = 120 - $pointsUser1;
        }
        $duration = random_int(200, 900);
        $d->addSeconds($duration);
        $gameType = $match ? $match['type'] : (random_int(1, 2) == 1 ? '3' : '9');
        $custom = [
            'rounds' => $this->buildRounds($gameType, $pointsUser1, $pointsUser2, $begin_d, $d->copy(), $user1, $user2),
        ];

        return [
            'id' => $this->gameID,
            'type' => $gameType,
            'match_id' => $match ? $match['id'] : null,
            'player1_user_id' => $user1,
            'player2_user_id' => $user2,
            'is_draw' => $pointsUser1 == $pointsUser2,
            'winner_user_id' => $pointsUser1 > $pointsUser2 ? $user1 : ($pointsUser2 > $pointsUser1 ? $user2 : null),
            'loser_user_id' => $pointsUser1 < $pointsUser2 ? $user1 : ($pointsUser2 < $pointsUser1 ? $user2 : null),
            'status' => 'Ended',
            'began_at' => $begin_d,
            'ended_at' => $d->copy(),
            'total_time' => $duration,
            'player1_points' => $pointsUser1,
            'player2_points' => $pointsUser2,
            'custom' => json_encode($custom),
        ];
    }

    // SUBSTITUI o buildRounds(...) pelo seguinte
    private function buildRounds(string $gameType, int $pointsUser1, int $pointsUser2, \Carbon\Carbon $begin, \Carbon\Carbon $end, int $user1, int $user2): array
    {
        $numTricks = 20;

        // Naipe em letra, como no exemplo: o/c/p/e
        $suits = ['o', 'c', 'p', 'e'];
        $trumpSuit = $suits[array_rand($suits)];

        // Vamos tentar gerar 20 "tricks" (2 cartas) a partir de um baralho válido (40 cartas),
        // de modo a que exista um subconjunto de totals que some exatamente pointsUser1.
        $tricks = null;          // array de 20 itens, cada um com ['p1' => card, 'p2' => card, 'total' => int]
        $totals = null;          // array de 20 ints (total de pontos de cada trick)
        $player1TrickMap = null; // array de 20 bools
        $resolved = false;

        for ($attempt = 0; $attempt < 200; $attempt++) {
            $deck = $this->generateDeck($suits);
            shuffle($deck);

            $localTricks = [];
            $localTotals = [];

            for ($i = 0; $i < $numTricks; $i++) {
                $card1 = $deck[$i * 2];
                $card2 = $deck[$i * 2 + 1];

                $p1Points = $this->cardPoints($card1['valor']);
                $p2Points = $this->cardPoints($card2['valor']);
                $total = $p1Points + $p2Points;

                $localTricks[] = [
                    'p1' => $card1,
                    'p2' => $card2,
                    'p1_points' => $p1Points,
                    'p2_points' => $p2Points,
                    'total' => $total,
                ];
                $localTotals[] = $total;
            }

            $indicesPlayer1 = $this->chooseTricksForPlayer($localTotals, $pointsUser1);
            if ($indicesPlayer1 === null) {
                continue;
            }

            $map = array_fill(0, $numTricks, false);
            foreach ($indicesPlayer1 as $idx) {
                $map[$idx] = true;
            }

            $tricks = $localTricks;
            $totals = $localTotals;
            $player1TrickMap = $map;
            $resolved = true;
            break;
        }

        // Fallback (muito raro): constrói totals e cartas sem garantir baralho “real”, mas garantindo regras de pontuação e estrutura.
        if (!$resolved) {
            $fallback = $this->buildFallbackTricks($pointsUser1, $pointsUser2, $numTricks, $suits);
            $tricks = $fallback['tricks'];
            $totals = array_map(fn($t) => $t['total'], $tricks);
            $player1TrickMap = $fallback['player1TrickMap'];
        }

        $totalSeconds = max(1, $end->diffInSeconds($begin));
        $interval = max(1, intdiv($totalSeconds, $numTricks));

        $player1Total = 0;
        $player2Total = 0;
        $rounds = [];

        for ($i = 0; $i < $numTricks; $i++) {
            $player1Wins = $player1TrickMap[$i];
            $winnerId = $player1Wins ? $user1 : $user2;

            $trickTotal = $totals[$i];

            if ($player1Wins) {
                $player1Total += $trickTotal;
            } else {
                $player2Total += $trickTotal;
            }

            // Timestamp no formato do teu exemplo: ...Z com microsegundos
            $ts = $begin->copy()->addSeconds($interval * ($i + 1))->utc();
            $timestamp = $ts->format('Y-m-d\TH:i:s.u\Z');

            $leadPlayerId = ($i % 2 === 0) ? $user1 : $user2;

            $rounds[] = [
                'round_number' => $i + 1,
                'lead_player_id' => $leadPlayerId,
                'winner_user_id' => $winnerId,

                'player1_card' => [
                    'naipe' => $tricks[$i]['p1']['naipe'],
                    'valor' => $tricks[$i]['p1']['valor'],
                ],
                'player2_card' => [
                    'naipe' => $tricks[$i]['p2']['naipe'],
                    'valor' => $tricks[$i]['p2']['valor'],
                ],

                'player1_card_points' => $tricks[$i]['p1_points'],
                'player2_card_points' => $tricks[$i]['p2_points'],

                'player1_total_points' => $player1Total,
                'player2_total_points' => $player2Total,

                'trump_suit' => $trumpSuit,

                // no teu exemplo: points_awarded = soma dos pontos das 2 cartas do round
                'points_awarded' => $trickTotal,

                // no teu exemplo: só true nos 4 últimos (17-20)
                'final_phase' => $i >= 16,

                'timestamp' => $timestamp,
            ];
        }

        return $rounds;
    }

    // === HELPERS A ADICIONAR NA CLASSE ===

    private function cardPoints(int $valor): int
    {
        // enum do utilizador:
        // 1=Ás, 13=Rei, 11=Valete, 12=Dama, 7=Sete(Bisca/Manilha), 2-6 conforme
        return match ($valor) {
            1 => 11,   // Ás
            7 => 10,   // Sete
            13 => 4,   // Rei
            11 => 3,   // Valete
            12 => 2,   // Dama
            default => 0, // 2-6
        };
    }

    private function generateDeck(array $suits): array
    {
        // Baralho da Sueca/Brisca: 10 cartas por naipe
        $ranks = [1, 2, 3, 4, 5, 6, 7, 11, 12, 13];
        $deck = [];
        foreach ($suits as $s) {
            foreach ($ranks as $r) {
                $deck[] = ['naipe' => $s, 'valor' => $r];
            }
        }
        return $deck; // 40 cartas
    }

    private function buildFallbackTricks(int $pointsUser1, int $pointsUser2, int $numTricks, array $suits): array
    {
        // Pontos possíveis por carta: {0,2,3,4,10,11}
        $cardPointValues = [0, 2, 3, 4, 10, 11];

        // Todos os totais possíveis de um trick (2 cartas)
        $possibleTotals = [];
        foreach ($cardPointValues as $a) {
            foreach ($cardPointValues as $b) {
                $possibleTotals[$a + $b] = true;
            }
        }
        $possibleTotals = array_keys($possibleTotals);
        sort($possibleTotals); // inclui 0..22 (mas apenas os atingíveis)

        // Decide quantos tricks ganha o P1 (heurística simples)
        $k = (int) max(0, min($numTricks, round($pointsUser1 / 6)));
        $k = max(1, min($numTricks - 1, $k));

        // Monta totals para P1 e P2 que somem exatamente aos pontos alvo
        $p1Totals = $this->splitIntoPossibleTotals($pointsUser1, $k, $possibleTotals);
        $p2Totals = $this->splitIntoPossibleTotals($pointsUser2, $numTricks - $k, $possibleTotals);

        // Cria mapa de winners e baralha a ordem
        $player1TrickMap = array_merge(array_fill(0, count($p1Totals), true), array_fill(0, count($p2Totals), false));
        $totals = array_merge($p1Totals, $p2Totals);

        $idx = range(0, $numTricks - 1);
        shuffle($idx);

        $shTotals = [];
        $shMap = [];
        foreach ($idx as $i) {
            $shTotals[] = $totals[$i];
            $shMap[] = $player1TrickMap[$i];
        }

        // Para cada total, gera 2 cartas com pontos que somem a esse total (ignorando unicidade do baralho)
        $tricks = [];
        foreach ($shTotals as $t) {
            [$p1p, $p2p] = $this->pickTwoCardPointsForTotal($t, $cardPointValues);

            $c1 = $this->cardFromPoints($p1p, $suits);
            $c2 = $this->cardFromPoints($p2p, $suits);

            $tricks[] = [
                'p1' => $c1,
                'p2' => $c2,
                'p1_points' => $p1p,
                'p2_points' => $p2p,
                'total' => $t,
            ];
        }

        return ['tricks' => $tricks, 'player1TrickMap' => $shMap];
    }

    private function splitIntoPossibleTotals(int $sum, int $parts, array $possibleTotals): array
    {
        // Greedy + ajuste final simples (suficiente para fallback)
        $out = [];
        $remaining = $sum;

        for ($i = 0; $i < $parts; $i++) {
            $partsLeft = $parts - $i - 1;

            // escolhe um valor que deixe o resto possível (aproximação)
            $choice = 0;
            foreach (array_reverse($possibleTotals) as $v) {
                $minPossible = 0 * $partsLeft;
                $maxPossible = max($possibleTotals) * $partsLeft;
                $newRem = $remaining - $v;
                if ($newRem >= $minPossible && $newRem <= $maxPossible) {
                    $choice = $v;
                    break;
                }
            }

            $out[] = $choice;
            $remaining -= $choice;
        }

        // Ajuste final: garantir soma exata (pode acontecer por arredondamentos da aproximação)
        $delta = $sum - array_sum($out);
        while ($delta !== 0) {
            for ($i = 0; $i < count($out) && $delta !== 0; $i++) {
                foreach ($possibleTotals as $candidate) {
                    $new = $out[$i] + ($delta > 0 ? 1 : -1);
                    // Só ajusta se o novo total ainda for atingível
                    if (in_array($new, $possibleTotals, true)) {
                        $out[$i] = $new;
                        $delta = $sum - array_sum($out);
                        break;
                    }
                }
            }
            // se não conseguir ajustar, sai para evitar loop infinito
            if ($delta !== 0) {
                break;
            }
        }

        return $out;
    }

    private function pickTwoCardPointsForTotal(int $total, array $cardPointValues): array
    {
        foreach ($cardPointValues as $a) {
            $b = $total - $a;
            if (in_array($b, $cardPointValues, true)) {
                return [$a, $b];
            }
        }
        // se não houver (não devia acontecer), devolve 0+0
        return [0, 0];
    }

    private function cardFromPoints(int $points, array $suits): array
    {
        // Mapeia pontos -> valor (respeitando o teu enum)
        // 11->1, 10->7, 4->13, 3->11, 2->12, 0->(2..6)
        $valor = match ($points) {
            11 => 1,
            10 => 7,
            4  => 13,
            3  => 11,
            2  => 12,
            default => random_int(2, 6),
        };

        return [
            'naipe' => $suits[array_rand($suits)],
            'valor' => $valor,
        ];
    }


    private function generateTrickTotals(int $numTricks, int $totalPoints): array
    {
        $weights = [];
        $weightSum = 0;
        for ($i = 0; $i < $numTricks; $i++) {
            $weight = random_int(1, 100);
            $weights[] = $weight;
            $weightSum += $weight;
        }

        $totals = [];
        $allocated = 0;
        for ($i = 0; $i < $numTricks; $i++) {
            $value = intdiv($totalPoints * $weights[$i], $weightSum);
            $totals[] = $value;
            $allocated += $value;
        }

        $remaining = $totalPoints - $allocated;
        for ($i = 0; $remaining > 0; $i = ($i + 1) % $numTricks) {
            $totals[$i]++;
            $remaining--;
        }

        return $totals;
    }

    private function splitPoints(int $total, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }
        if ($parts === 1) {
            return [$total];
        }

        $cuts = [];
        for ($i = 0; $i < $parts - 1; $i++) {
            $cuts[] = random_int(0, $total);
        }
        sort($cuts);

        $values = [];
        $prev = 0;
        foreach ($cuts as $cut) {
            $values[] = $cut - $prev;
            $prev = $cut;
        }
        $values[] = $total - $prev;

        shuffle($values);
        return $values;
    }

    private function chooseTricksForPlayer(array $totals, int $target): ?array
    {
        $max = $target;
        $dp = array_fill(0, $max + 1, null);
        $dp[0] = [];

        foreach ($totals as $index => $value) {
            for ($sum = $max; $sum >= $value; $sum--) {
                if ($dp[$sum] !== null || $dp[$sum - $value] === null) {
                    continue;
                }
                $dp[$sum] = array_merge($dp[$sum - $value], [$index]);
            }
        }

        return $dp[$target];
    }
}
