<?php

namespace AppBundle\Cards;

class Round extends BaseProcess {
    protected $numberOfPlayers = 4;
    protected $numberOfCardsToDeal = 13;
    protected $isStarting = true;
    protected $deck;
    protected $players;
    protected $roundCount = 1;
    protected $roundOver = false;
    protected $leadPlayer = 0;
    protected $isBrokenHearts = false;

    public function __construct($params)
    {
        $this->deck = new Deck();
        $this->numberOfPlayers = $params['numberOfPlayers'];
        $this->numberOfCardsToDeal = $params['numberOfCardsToDeal'];
        $this->players = $params['players'];
        $this->roundCount = $params['roundCount'];
    }

    public function start()
    {
        for ($i = 0; $i < $this->numberOfPlayers; $i++) {
            $hand = new Hand($this->deck->deal($this->numberOfCardsToDeal));
            $this->players[$i]->addHand($hand);
            $this->players[$i]->showHand();
        }

        $this->passCards();

        foreach ($this->players as $i => $p) {
            if ($p->hasCard(0)) {
                $this->leadPlayer = $i;
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
        ]);
        $trick->play();
        $this->handleTrickResult($trick);
        $this->isStarting = false;

        $isOver = $trick->getRoundOver();
        if ($isOver) {
            $this->report();
        }
        return !$isOver;
    }

    public function report()
    {
        $this->writeln('At the end of round ' . $this->roundCount . ', the score is: ');
        foreach($this->players as $player) {
            $player->report();
        }
    }

    public function getMaxScore()
    {
        $maxScore = 0;
        foreach($this->players as $player) {
            $score = $player->getScore();
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }

        return $maxScore;
    }

    protected function handleTrickResult($trick)
    {
        $trick->show();
        $this->players = $trick->getPlayers();
        $cards = $trick->getCardsPlayed();
        $leadSuit = $cards[0]->getSuit();
        $topValue = $cards[0]->getValue();
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
                $takesTrick = ($this->leadPlayer + $idx) % $this->numberOfPlayers;
            }
        }

        $this->players[$takesTrick]->addPoints($points);
        $this->leadPlayer = $takesTrick;
        $this->writeln($points . ' for ' . $this->players[$takesTrick]->getName());
    }

    protected function passCards()
    {
        $dirLabel = $this->getPassDirectionLabel();
        $this->writeln($dirLabel === 'hold' ? 'We do not pass this round' : 'We pass 3 cards ' . $dirLabel . '...');
        if ($dirLabel === 'hold') {
            return;
        }

        $cardsInTransition = [];
        foreach ($this->players as $i => $p) {
            $cardsInTransition[$i] = $p->getCardsToPass($dirLabel);
        }

        foreach ($this->players as $i => $p) {
            switch ($dirLabel) {
                case 'left':
                    $j = ($i + 3) % 4;
                    break;
                case 'right':
                    $j = ($i + 1) % 4;
                    break;
                default:
                    $j = ($i + 2) % 4;
                    break;
            }

            $p->addCards($cardsInTransition[$j]);
            $p->showHand();
        }
    }

    protected function getPassDirectionLabel()
    {
        $a = ['left', 'right', 'across', 'hold'];
        return $a[($this->roundCount - 1) % 4];
    }
}
