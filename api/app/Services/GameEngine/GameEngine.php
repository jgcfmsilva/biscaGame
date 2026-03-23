<?php

namespace App\Services\GameEngine;

use RuntimeException;

class GameEngine
{
    private const BOT_ID = 999999;
    public const TYPE_BISCA_3 = '3';
    public const TYPE_BISCA_9 = '9';

    public const STATUS_PENDING     = 'Pending';
    public const STATUS_WAITING_PLAYERS = 'WaitingPlayers';
    public const STATUS_WAITING_READY   = 'WaitingReady';
    public const STATUS_PLAYING     = 'Playing';
    public const STATUS_ENDED       = 'Ended';
    public const STATUS_INTERRUPTED = 'Interrupted';

    private const RANK = [
        1  => 12,  // Ás
        7  => 11,  // Sete
        13 => 10,  // Rei
        11 => 9,   // Valete
        12 => 8,   // Dama
        6  => 7,
        5  => 6,
        4  => 5,
        3  => 4,
        2  => 3,
        10 => 2,
        9  => 1,
        8  => 0,
    ];

    private const POINTS = [
        1  => 11,
        7  => 10,
        13 => 4,
        11 => 3,
        12 => 2,
    ];

    public function createNewGameState(string $type, int $player1Id, int $player2Id): array
    {
        if (!in_array($type, [self::TYPE_BISCA_3, self::TYPE_BISCA_9], true)) {
            throw new RuntimeException('Invalid game type');
        }

        $cardsPerPlayer = $type === self::TYPE_BISCA_3 ? 3 : 9;

        $deck = $this->shuffleDeck($this->createDeck());

        $trumpCard = end($deck);
        array_pop($deck);

        [$p1Hand, $p2Hand, $deck] = $this->dealInitialHands($deck, $cardsPerPlayer);

        // Em partidas contra o bot, deixa sempre o humano abrir a primeira vaza.
        if ($player2Id === self::BOT_ID && $player1Id !== self::BOT_ID) {
            $leader = 'player1';
            $dealer = 'player2';
        } elseif ($player1Id === self::BOT_ID && $player2Id !== self::BOT_ID) {
            $leader = 'player2';
            $dealer = 'player1';
        } else {
            $dealer = (rand(0, 1) === 0) ? 'player1' : 'player2';
            $leader = $dealer === 'player1' ? 'player2' : 'player1';
        }

        return [
            'type'       => $type,
            'status'     => self::STATUS_PLAYING,
            'round'      => 1,
            'deck'       => array_values($deck),
            'trumpCard'  => $trumpCard,
            'trumpSuit'  => $trumpCard['naipe'],
            'finalPhase' => false,
            'dealer'     => $dealer,

            'currentTurn' => $leader,
            'turnStartedAt' => $this->nowMs(),
            'turnDurationMs' => max(20000, (int) config('constants.turn_timer_ms', 20000)),
            'leadTurn'    => null,
            'roundWinner' => null,
            'lastPlayedCards' => [
                'player1' => null,
                'player2' => null,
            ],

            'player1' => [
                'id'         => $player1Id,
                'score'      => 0,
                'marks'      => 0,
                'hand'       => $p1Hand,
                'playedCard' => null,
            ],
            'player2' => [
                'id'         => $player2Id,
                'score'      => 0,
                'marks'      => 0,
                'hand'       => $p2Hand,
                'playedCard' => null,
            ],

            'matchForfeited' => false,
            'forfeitReason'  => null,
            'forfeitLoser'   => null,

            // multiplayer readiness
            'readyPlayers' => [],
        ];
    }

    private function createDeck(): array
    {
        $suits  = ['c', 'e', 'o', 'p'];
        $values = [1,2,3,4,5,6,7,11,12,13];

        $deck = [];
        foreach ($suits as $naipe) {
            foreach ($values as $valor) {
                $deck[] = ['naipe' => $naipe, 'valor' => $valor];
            }
        }
        return $deck;
    }

    private function shuffleDeck(array $deck): array
    {
        shuffle($deck);
        return $deck;
    }

    private function dealInitialHands(array $deck, int $cardsPerPlayer): array
    {
        return [
            array_splice($deck, 0, $cardsPerPlayer),
            array_splice($deck, 0, $cardsPerPlayer),
            $deck
        ];
    }

    private function drawCardFromDeck(array &$deck): ?array
    {
        return count($deck) ? array_shift($deck) : null;
    }

    public function playCard(array $state, int $playerId, int $cardIndex): array
    {
        if ($state['status'] !== self::STATUS_PLAYING) {
            return $state;
        }

        $state = $this->playCardInternal($state, $playerId, $cardIndex);

        $botId = $state['player2']['id'];
        if ($botId === self::BOT_ID) {
            $botKey = $this->getPlayerKeyById($state, $botId);
            if ($state['currentTurn'] === $botKey) {
                $idx = $this->chooseBotCardIndex($state, $botKey, $state[$botKey]['hand']);
                $state = $this->playCardInternal($state, $botId, $idx);
            }
        }

        return $state;
    }

    /**
     * Se for a vez do bot liderar (ex.: início do jogo ou depois de reconectar),
     * faz o bot jogar a carta de saída automaticamente.
     */
    public function autoPlayForBot(array $state, int $botId): array
    {
        if ($state['status'] !== self::STATUS_PLAYING) {
            return $state;
        }

        $botKey = $this->getPlayerKeyById($state, $botId);

        if ($state['currentTurn'] !== $botKey) {
            return $state;
        }

        // Só dispara se ainda não há carta de saída nesta vaza
        if ($state['leadTurn']) {
            return $state;
        }

        if (empty($state[$botKey]['hand'])) {
            return $state;
        }

        $idx = $this->chooseBotCardIndex($state, $botKey, $state[$botKey]['hand']);
        return $this->playCardInternal($state, $botId, $idx);
    }

    public function forfeit(array $state, int $loserId, string $reason): array
    {
        $loserKey  = $this->getPlayerKeyById($state, $loserId);
        $winnerKey = $loserKey === 'player1' ? 'player2' : 'player1';

        $remaining = array_merge(
            $state[$loserKey]['hand'] ?? [],
            $state[$winnerKey]['hand'] ?? [],
            $state['deck'] ?? []
        );

        if (!empty($state['trumpCard'])) {
            $remaining[] = $state['trumpCard'];
        }

        if (!empty($state[$loserKey]['playedCard'])) {
            $remaining[] = $state[$loserKey]['playedCard'];
        }
        if (!empty($state[$winnerKey]['playedCard'])) {
            $remaining[] = $state[$winnerKey]['playedCard'];
        }

        $unclaimedPoints = array_reduce($remaining, function ($carry, $card) {
            return $carry + $this->cardPoints($card);
        }, 0);

        $state[$winnerKey]['score'] += $unclaimedPoints;

        $state['deck'] = [];
        $state['trumpCard'] = null;
        $state['finalPhase'] = true;
        $state[$loserKey]['hand'] = [];
        $state[$winnerKey]['hand'] = [];
        $state[$loserKey]['playedCard'] = null;
        $state[$winnerKey]['playedCard'] = null;
        unset($state['roundFinished']);

        $marks = $this->calculateMarks(
            $state['player1']['score'],
            $state['player2']['score']
        );
        $state['player1']['marks'] = $marks['p1'];
        $state['player2']['marks'] = $marks['p2'];

        $state['status'] = self::STATUS_ENDED;
        $state['currentTurn'] = null;
        $state['turnStartedAt'] = null;
        $state['leadTurn'] = null;
        $state['matchForfeited'] = true;
        $state['forfeitReason']  = $reason;
        $state['forfeitLoser']   = $loserKey;
        $state['is_draw'] = false;

        $state['winner'] = $winnerKey;
        $state['loser']  = $loserKey;

        return $state;
    }

    private function playCardInternal(array $state, int $playerId, int $cardIndex): array
    {
        $playerKey = $this->getPlayerKeyById($state, $playerId);
        $otherKey  = $playerKey === 'player1' ? 'player2' : 'player1';

        if (!$state['leadTurn'] && !empty($state['lastPlayedCards'])) {
            $state['lastPlayedCards'] = [
                'player1' => null,
                'player2' => null,
            ];
        }

        if (!isset($state[$playerKey]['hand'][$cardIndex])) {
            throw new RuntimeException('Invalid card index');
        }

        if ($state['finalPhase'] && $state['leadTurn'] && $state['leadTurn'] !== $playerKey) {
            $leadCard = $state[$state['leadTurn']]['playedCard'] ?? null;
            if ($leadCard) {
                $leadSuit = $leadCard['naipe'];
                $hasSuit = $this->playerHasSuit($state[$playerKey]['hand'], $leadSuit);
                if ($hasSuit && $state[$playerKey]['hand'][$cardIndex]['naipe'] !== $leadSuit) {
                    throw new RuntimeException('Jogada inválida: deve assistir ao naipe na fase final.');
                }
            }
        }

        $card = $state[$playerKey]['hand'][$cardIndex];
        unset($state[$playerKey]['hand'][$cardIndex]);
        $state[$playerKey]['hand'] = array_values($state[$playerKey]['hand']);
        $state[$playerKey]['playedCard'] = $card;

        if (!$state['leadTurn']) {
            $state['leadTurn'] = $playerKey;
            $state['currentTurn'] = $otherKey;
            $state['turnStartedAt'] = $this->nowMs();
            return $state;
        }

        $state['roundFinished'] = true;
        // Ambas as cartas estão na mesa, ninguém deve estar com a vez enquanto aguardamos o desfecho da vaza.
        $state['currentTurn'] = null;
        $state['turnStartedAt'] = null;

        return $state;
    }

    private function playerHasSuit(array $hand, string $suit): bool
    {
        foreach ($hand as $c) {
            if (($c['naipe'] ?? null) === $suit) {
                return true;
            }
        }
        return false;
    }

    private function chooseBotCardIndex(array $state, string $botKey, array $hand): int
    {
        $leadKey = $state['leadTurn'] ?? null;

        if (!$leadKey) {
            return array_rand($hand);
        }

        if ($leadKey !== $botKey && $state[$leadKey]['playedCard']) {
            $leadSuit = $state[$leadKey]['playedCard']['naipe'];
            foreach ($hand as $i => $c) {
                if ($c['naipe'] === $leadSuit) {
                    return $i;
                }
            }
        }

        return array_rand($hand);
    }

    public function resolveRound(array $state): array
    {
        if ($state['status'] !== self::STATUS_PLAYING) {
            return $state;
        }

        $leadKey   = $state['leadTurn'];
        $followKey = $leadKey === 'player1' ? 'player2' : 'player1';

        $leadCard   = $state[$leadKey]['playedCard'];
        $followCard = $state[$followKey]['playedCard'];

        if (!$leadCard || !$followCard) {
            throw new RuntimeException("Cannot resolve round before both cards are played");
        }

        $winner = $this->compareCardsRound(
            $state, $leadCard, $followCard, $leadKey, $followKey
        );

        $state['roundWinner'] = $winner;
        $state[$winner]['score'] +=
            $this->cardPoints($leadCard) + $this->cardPoints($followCard);

        $state = $this->performBuyPhase($state);

        $state['lastPlayedCards'] = [
            'player1' => $state['player1']['playedCard'],
            'player2' => $state['player2']['playedCard'],
        ];

        $state['player1']['playedCard'] = null;
        $state['player2']['playedCard'] = null;

        $state['leadTurn'] = null;
        $state['currentTurn'] = $winner;
        $state['turnStartedAt'] = $this->nowMs();
        $state['round']++;
        unset($state['roundFinished']);

        if ($this->isGameOver($state)) {
            return $this->finishGame($state);
        }

        $botId = $state['player2']['id'];
        if ($botId === self::BOT_ID) {
            $botKey = $this->getPlayerKeyById($state, $botId);
            if ($winner === $botKey && !empty($state[$botKey]['hand'])) {
                $idx = $this->chooseBotCardIndex($state, $botKey, $state[$botKey]['hand']);
                $state = $this->playCardInternal($state, $botId, $idx);
            }
        }

        return $state;
    }

    private function compareCardsRound($state, $leadCard, $followCard, $leadKey, $followKey): string
    {
        $trumpSuit = $state['trumpSuit'] ?? ($state['trumpCard']['naipe'] ?? null);

        // Se por algum motivo o naipe do trunfo não estiver disponível,
        // assume que não há trunfo (melhor do que quebrar o jogo).
        $trumpSuit ??= '';

        $result = $this->compareCards($leadCard, $followCard, $trumpSuit);
        return $result === 'lead' ? $leadKey : $followKey;
    }

    private function compareCards(array $leadCard, array $followCard, string $trump): string
    {
        if ($leadCard['naipe'] !== $followCard['naipe']) {
            return ($followCard['naipe'] === $trump) ? 'follow' : 'lead';
        }

        $leadRank   = $this->rankOf($leadCard['valor']);
        $followRank = $this->rankOf($followCard['valor']);

        return $followRank > $leadRank ? 'follow' : 'lead';
    }

    private function rankOf(int $valor): int
    {
        return self::RANK[$valor] ?? -1;
    }

    private function cardPoints(array $card): int
    {
        return self::POINTS[$card['valor']] ?? 0;
    }

    public function totalCardPoints(array ...$cards): int
    {
        return array_reduce($cards, function ($carry, $card) {
            if (!$card) {
                return $carry;
            }
            return $carry + $this->cardPoints($card);
        }, 0);
    }

    private function performBuyPhase(array $state): array
    {
        $winnerKey = $state['roundWinner'];
        $loserKey  = $winnerKey === 'player1' ? 'player2' : 'player1';

        // Garante que o naipe do trunfo fica persistido mesmo após a carta ser comprada.
        $state['trumpSuit'] = $state['trumpSuit'] ?? ($state['trumpCard']['naipe'] ?? null);

        $winnerTookTrump = false;

        if (!empty($state['deck'])) {
            $card = $this->drawCardFromDeck($state['deck']);
            if ($card) $state[$winnerKey]['hand'][] = $card;
        } elseif (!empty($state['trumpCard'])) {
            $state[$winnerKey]['hand'][] = $state['trumpCard'];
            $state['trumpCard'] = null;
            $winnerTookTrump = true;
        }

        if (!empty($state['deck'])) {
            $card = $this->drawCardFromDeck($state['deck']);
            if ($card) $state[$loserKey]['hand'][] = $card;
        } elseif (!$winnerTookTrump && !empty($state['trumpCard'])) {
            $state[$loserKey]['hand'][] = $state['trumpCard'];
            $state['trumpCard'] = null;
        }

        if (empty($state['deck']) && empty($state['trumpCard'])) {
            $state['finalPhase'] = true;
        }

        return $state;
    }

    private function isGameOver(array $state): bool
    {
        return (
            empty($state['player1']['hand']) &&
            empty($state['player2']['hand']) &&
            !$state['player1']['playedCard'] &&
            !$state['player2']['playedCard']
        );
    }

    private function finishGame(array $state): array
    {
        $state['status'] = self::STATUS_ENDED;
        $state['currentTurn'] = null;
        $state['turnStartedAt'] = null;
        $state['leadTurn'] = null;

        $p1 = $state['player1']['score'];
        $p2 = $state['player2']['score'];

        $marks = $this->calculateMarks($p1, $p2);
        $state['player1']['marks'] = $marks['p1'];
        $state['player2']['marks'] = $marks['p2'];

        if ($marks['is_draw']) {
            $state['winner'] = null;
            $state['loser']  = null;
            $state['is_draw'] = true;
        } else {
            $state['winner'] = $p1 > $p2 ? 'player1' : 'player2';
            $state['loser']  = $state['winner'] === 'player1' ? 'player2' : 'player1';
            $state['is_draw'] = false;
        }

        return $state;
    }

    public function calculateMarks(int $p1Points, int $p2Points): array
    {
        if ($p1Points === $p2Points) {
            return [
                'p1' => 0,
                'p2' => 0,
                'is_draw' => true,
            ];
        }

        $winnerMarks = $this->marksForPoints(max($p1Points, $p2Points));

        return [
            'p1' => $p1Points > $p2Points ? $winnerMarks : 0,
            'p2' => $p2Points > $p1Points ? $winnerMarks : 0,
            'is_draw' => false,
        ];
    }

    private function marksForPoints(int $points): int
    {
        // 61–90 -> 1 mark; 91–119 -> 2 marks; 120 -> 3 marks (bandeira); draw -> 0.
        return match (true) {
            $points >= 120 => 3, // bandeira
            $points >= 91  => 2, // capote
            $points >= 61  => 1, // risca
            default        => 0,
        };
    }

    private function getPlayerKeyById(array $state, int $userId): string
    {
        if ($state['player1']['id'] === $userId) return 'player1';
        if ($state['player2']['id'] === $userId) return 'player2';
        throw new RuntimeException('User not in game');
    }

    public function publicStateFor(array $state, int $viewerId, array $meta = []): array
    {
        $meKey = $this->getPlayerKeyById($state, $viewerId);
        $opKey = $meKey === 'player1' ? 'player2' : 'player1';

        $playerMeta = $meta['players'] ?? [];
        $mePlayedCard = $state[$meKey]['playedCard'] ?? null;
        $opPlayedCard = $state[$opKey]['playedCard'] ?? null;
        $cardsOnTable = !empty($mePlayedCard) || !empty($opPlayedCard);
        $lastPlayedRaw = $state['lastPlayedCards'] ?? [];
        $hasLastPlayed = !empty($lastPlayedRaw[$meKey] ?? null) || !empty($lastPlayedRaw[$opKey] ?? null);
        $shouldExposeLastPlayed = $cardsOnTable || $hasLastPlayed;

        $relative = fn ($key) => $key === null
            ? null
            : ($key === $meKey ? 'me' : ($key === $opKey ? 'opponent' : null));

        $lastPlayed = $shouldExposeLastPlayed ? ($state['lastPlayedCards'] ?? null) : null;
        $lastPlayedMe = is_array($lastPlayed) ? ($lastPlayed[$meKey] ?? null) : null;
        $lastPlayedOpponent = is_array($lastPlayed) ? ($lastPlayed[$opKey] ?? null) : null;

        $status = $state['status'] ?? null;
        $isPending = in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_WAITING_PLAYERS,
            self::STATUS_WAITING_READY,
        ], true);
        $playerHand = $state[$meKey]['hand'];
        if ($isPending) {
            $playerHand = array_map(
                fn ($card) => [
                    'naipe' => null,
                    'valor' => null,
                ],
                $playerHand
            );
        }

        return [
            'matchId'         => $meta['matchId'] ?? null,
            'matchGameNumber' => $meta['matchGameNumber'] ?? null,
            'sessionMode'     => $meta['sessionMode'] ?? null,

            'type'        => $state['type'],
            'status'      => $state['status'],
            'round'       => $state['round'],
            'dealer'      => $relative($state['dealer']),

            'currentTurn' =>
                ($state['currentTurn'] === $meKey ? 'me' :
                 ($state['currentTurn'] === $opKey ? 'opponent' : null)),
            'turnStartedAt' => $state['turnStartedAt'] ?? null,
            'turnDurationMs' => $state['turnDurationMs'] ?? null,

            'finalPhase'  => $state['finalPhase'],
            'trumpCard'   => $isPending ? null : $state['trumpCard'],
            'trumpSuit'   => $isPending
                ? null
                : ($state['trumpSuit'] ?? ($state['trumpCard']['naipe'] ?? null)),
            'deckCount'   => count($state['deck']),
            'roundWinner' => $state['roundWinner'] ?? null,
            'lastPlayedCards' => [
                'me' => $lastPlayedMe,
                'opponent' => $lastPlayedOpponent,
            ],

            'matchForfeited' => $state['matchForfeited'],
            'forfeitReason'  => $state['forfeitReason'],
            'forfeitLoser'   => $relative($state['forfeitLoser'] ?? null),

            'is_draw' => $state['is_draw'] ?? false,
            'winner'  => $relative($state['winner'] ?? null),
            'loser'   => $relative($state['loser'] ?? null),

            'ready' => [
                'me'       => in_array($state[$meKey]['id'], $state['readyPlayers'] ?? [], true),
                'opponent' => in_array($state[$opKey]['id'], $state['readyPlayers'] ?? [], true),
            ],

            'me' => [
                'id'         => $state[$meKey]['id'],
                'nickname'   => $playerMeta[$state[$meKey]['id']]['nickname'] ?? null,
                'photo_avatar_filename' => $playerMeta[$state[$meKey]['id']]['photo_avatar_filename'] ?? null,
                'score'      => $state[$meKey]['score'],
                'marks'      => $state[$meKey]['marks'],
                'hand'       => $playerHand,
                'playedCard' => $state[$meKey]['playedCard'],
            ],

            'opponent' => [
                'id'         => $state[$opKey]['id'],
                'nickname'   => $playerMeta[$state[$opKey]['id']]['nickname'] ?? null,
                'photo_avatar_filename' => $playerMeta[$state[$opKey]['id']]['photo_avatar_filename'] ?? null,
                'score'      => $state[$opKey]['score'],
                'marks'      => $state[$opKey]['marks'],
                'handSize'   => count($state[$opKey]['hand']),
                'playedCard' => $state[$opKey]['playedCard'],
            ],
        ];
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
