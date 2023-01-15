<?php

namespace AppBundle\Cards;

class Round extends BaseProcess {
    protected $numberOfPlayers = 4;
    protected $numberOfCardsToDeal = 13;
    protected $isStarting = true;
    protected $deck;
    protected $roundScores = [];
    protected $gameScores = [];
    protected $players;
    protected $roundCount = 1;
    protected $roundOver = false;
    protected $leadPlayer = 0;
    protected $isBrokenHearts = false;
    protected $passDirection;

    public function __construct($params)
    {
        $this->deck = new Deck();
        $this->numberOfPlayers = $params['numberOfPlayers'];
        $this->numberOfCardsToDeal = $params['numberOfCardsToDeal'];
        $this->gameScores = $params['scores'];
        $this->roundScores = $params['scores'];
        foreach ($this->roundScores as $id => $score) {
            $this->roundScores[$id] = 0;
        }
        $this->players = $params['players'];
        $this->roundCount = $params['roundCount'];
    }

    public function start()
    {
        $this->passDirection = $this->getPassDirection();
        for ($i = 1; $i <= $this->numberOfPlayers; $i++) {
            $hand = new Hand($this->deck->deal($this->numberOfCardsToDeal));
            $this->players[$i]->addHand($hand, $this->passDirection === 'hold');
            $this->players[$i]->showHand();
        }

        $this->passCards();

        foreach ($this->players as $p) {
            if ($p->hasCard(0)) {
                $this->leadPlayer = $p->getId();
            }
        }
    }

    public function play()
    {
        $trick = new Trick([
            'players' => $this->players,
            'numberOfPlayers' => $this->numberOfPlayers,
            'leadPlayer' => $this->leadPlayer,
            'isBrokenHearts' => $this->isBrokenHearts,
            'isFirstTrick' => $this->isStarting,
            'gameScores' => $this->gameScores,
            'roundScores' => $this->roundScores,
        ]);
        $trick->play();
        $this->handleTrickResult($trick);
        $this->isStarting = false;

        $isOver = $trick->getRoundOver();
        // if ($isOver) {
        //     $this->report();
        // }
        return !$trick->getRoundOver();
        // return !$isOver;
    }

    public function report()
    {
        $this->writeln('At the end of round ' . $this->roundCount . ', the score is: ');
        foreach($this->players as $playerId => $player) {
            $this->writeln($player->getName() . ' has ' . $this->gameScores[$playerId] . ' points.');
        }
    }

    public function getScores()
    {
        // $scores = empty($this->scores) ? [] : $this->scores;
        $scores = $this->roundScores;
        // foreach($this->players as $player) {
        //     $scores[$player->getId()] = $player->getMyScore();
        // }
        // check to see if anyone shot the moon
        $shotTheMoon = null;
        $stmNote = '';
        foreach ($scores as $id => $score) {
            if ($score === 26) {
                $shotTheMoon = $id;
            }
        }

        if ($shotTheMoon) {
            $this->writeln($this->players[$shotTheMoon]->getName() . ' SHOT THE MOON!!.');
            $stmNote = ' (STM)';
            foreach ($scores as $id => $score) {
                if ($id === $shotTheMoon) {
                    $scores[$id] = 0;
                } else {
                    $scores[$id] = 26;
                }
            }
        }

        foreach ($scores as $id => $score) {
$this->writeln($this->players[$id]->getName() . ' has ' . $score . ' points this round'. $stmNote);
            $this->gameScores[$id] += $score;
        }

        return $this->gameScores;
    }

    protected function handleTrickResult($trick)
    {
        $trick->show();
        $this->players = $trick->getPlayers();
        $cards = $trick->getCardsPlayed();
        $leadSuit = array_values($cards)[0]->getSuit();
        $topValue = array_values($cards)[0]->getValue();
        $takesTrick = $this->leadPlayer;
        $points = 0;
        foreach($cards as $idx => $card) {
            $suit = $card->getSuit();
            $value = $card->getValue();
            if ($suit === 2) {
                $this->isBrokenHearts = true;
                $points++;
            }
            if ($suit === 3 && $value == 10) {
                $points += 13;
            }
            if ($suit === $leadSuit && $value > $topValue) {
                $topValue = $value;
                $takesTrick = $idx;
            }
        }

        $this->roundScores[$takesTrick] += $points;
        $this->leadPlayer = $takesTrick;
        $this->writeln($this->players[$takesTrick]->getName() . ' takes ' . $points . ' points ');
    }

    protected function passCards()
    {
        $this->writeln($this->passDirection === 'hold' ? 'We do not pass this round' : 'We pass 3 cards ' . $this->passDirection . '...');
        if ($this->passDirection === 'hold') {
            return;
        }

        $cardsInTransition = [];
        foreach ($this->players as $i => $p) {
            $cardsInTransition[$i] = $p->getCardsToPass($this->passDirection);
        }

        foreach ($this->players as $i => $p) {
            switch ($this->passDirection) {
                case 'left':
                    $j = $i === 1 ? 4 : ($i + 3) % 4; // 1->2, 3->4, 4->1
                    break;
                case 'right':
                    $j = $i === 3 ? 4 : ($i + 1) % 4;
                    break;
                default:
                    $j = $i === 2 ? 4 : ($i + 2) % 4;
                    break;
            }

            $this->showCards($cardsInTransition[$i], $p->getName() . ' passes: ');
            $p->receivePassedCards($cardsInTransition[$j]);
            $p->showHand();
        }
    }

    protected function getPassDirection()
    {
        $a = ['left', 'right', 'across', 'hold'];
        return $a[($this->roundCount - 1) % 4];
    }
}
