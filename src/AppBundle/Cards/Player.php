<?php

namespace AppBundle\Cards;

class Player extends BaseProcess {
    protected $hand;
    protected $id;
    protected $name;
    protected $riskTolerance = 0;
    protected $myScore = 0;
    protected $gameScores = [];
    protected $roundScores = [];
    protected $cardPlayed;
    protected $cardsPlayedThisRound = [];
    protected $playersVoidInSuit = [[],[],[],[]]; //playersVoidInSuit[0][2] means p2 void in suit 0
    protected $isHoldHand;
    protected $baseStrategy;
    protected $handStrategy;
    protected $trickStrategy;
    protected $selector;

    public function __construct($id, $name = null, $strategy = null)
    {
        if (is_null($name)) {
            $name = 'Player ' . $id;
        }

        $this->id = $id;
        $this->name = $name;
        $this->baseStrategy = is_null($strategy) ? 'default' : $strategy;
        $this->selector = new DefaultSelector([]);
        $this->riskTolerance = rand(1,100);
        print $this->name . ' has riskTolerance ' . $this->riskTolerance . " (not yet used)\n";
    }

    public function addHand($hand, $isHoldHand)
    {
        $this->hand = $hand;
        $this->isHoldHand = $isHoldHand;
        $this->handStrategy = $this->selector->getRoundStrategy($hand->getCards(), $isHoldHand, $this->gameScores);
        if ($this->handStrategy === 'shootTheMoon') {
            print $this->name . ' says I shall try to shoot the moon'."\n";
            $this->selector = new ShootTheMoonSelector([]);
        }
        $this->cardsPlayedThisRound = [];
        $this->cardPlayed = null;
    }

    public function showHand()
    {
        $s = $this->name;
        if ($this->cardPlayed) {
            $s .= ' plays the ' . $this->cardPlayed->getDisplay();
        }
        $this->writeln($s);
        $this->hand->show();
    }

    public function report()
    {
        $this->writeln($this->name . ' has ' . $this->myScore . ' points.');
    }

    public function playCard($cardsPlayedThisTrick, $isBrokenHearts, $isFirstTrick)
    {
        $this->trickStrategy = 'highestNoTake';
        if (empty($cardsPlayedThisTrick)) {
            if ($isFirstTrick && $this->hasCard(0)) {
                $cardToPlayIdx = 0;
            } else {
                $eligibleCards = $this->hand->getEligibleLeadCards($isBrokenHearts);
                $cardToPlayIdx = $this->selectLeadCard($eligibleCards, $isFirstTrick);
            }
        } else {
            $suit = array_values($cardsPlayedThisTrick)[0]->getSuit();
            $eligibleCards = $this->hand->getEligibleCards($suit, $isFirstTrick);
            $cardToPlayIdx = $this->selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick);
        }

        return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx);
    }

    public function getCardsToPass($dirLabel)
    {
        return $this->hand->getCardsToPass($this->selector->selectCardsToPass(['hand' => $this->hand->getCards(), 'gameScores' => $this->gameScores, 'strategy' => $this->handStrategy]));
    }

    public function receivePassedCards($c)
    {
        $this->hand->addCards($c);
        if ($this->handStrategy === 'shootTheMoon') {
            $this->handStrategy = $this->selector->getRoundStrategy($this->hand->getCards(), $this->isHoldHand, $this->gameScores);
            if ($this->handStrategy !== 'shootTheMoon') {
                print $this->name . ' says I shall no longer shoot the moon'."\n";
                $this->selector = new DefaultSelector([]);
            }
        }
    }

    protected function selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick)
    {
        return $this->selector->selectCard([
            'eligibleCards' => $eligibleCards,
            'allCards' => $this->hand->getCards(),
            'isFirstTrick' => $isFirstTrick,
            'cardsPlayedThisTrick' => $cardsPlayedThisTrick,
            'handStrategy' => $this->handStrategy,
            'trickStrategy' => $this->trickStrategy,
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
        ]);
    }

    protected function selectLeadCard($eligibleCards, $isFirstTrick)
    {
        return $this->selector->selectLeadCard([
            'allCards' => $this->hand->getCards(),
            'eligibleCards' => $eligibleCards,
            'isFirstTrick' => $isFirstTrick,
            'handStrategy' => $this->handStrategy,
            'trickStrategy' => $this->trickStrategy,
            'cardsPlayedThisTrick' => [],
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
        ]);
    }

    public function gatherInfo($info)
    {
        $leadSuit = null;
        $topValue = null;
        $this->gameScores = !empty($info['gameScores']) ? $info['gameScores'] : $this->gameScores;
        if (!empty($info['gameScores'])) {
            foreach ($this->gameScores as $id => $score) {
                $this->roundScores[$id] = 0;
            }
        }

        foreach ($info['cardsPlayed'] as $id => $c) {
            if (empty($this->cardsPlayedThisRound[$id])) {
                $this->cardsPlayedThisRound[$id] = [];
            }
            $this->cardsPlayedThisRound[$id][] = $c;
            if (is_null($leadSuit)) {
                $leadSuit = $c->getSuit();
                $topValue = $c->getValue();
                $takesTrick = $id;
                $points = 0;
            }
            $suit = $c->getSuit();
            $value = $c->getValue();
            if ($suit === 2) {
                $points++;
            }
            if ($suit === 3 && $value == 10) {
                $points += 13;
            }
            if ($suit === $leadSuit && $value > $topValue) {
                $takesTrick = $id;
                $topValue = $value;
            }
            if ($suit !== $leadSuit) {
                $this->playersVoidInSuit[$leadSuit] = $id;
// print "$id is void in $leadSuit\n";
            }
        }
        $this->gameScores[$takesTrick] += $points;
        $this->roundScores[$takesTrick] += $points;
        if ($this->handStrategy === 'shootTheMoon' && $points && $takesTrick !== $this->id) {
            $this->handStrategy = 'avoidPoints';
            print $this->name . ' says I shall no longer shoot the moon'."\n";
            $this->selector = new DefaultSelector([]);
        }
    }

    public function hasCards()
    {
        return $this->hand->hasCards();
    }

    public function hasCard($cardIdx)
    {
        return $this->hand->hasCard($cardIdx);
    }

    public function addPoints($pts)
    {
        $this->myScore += $pts;
    }

    public function getMyScore()
    {
        return $this->myScore;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}
