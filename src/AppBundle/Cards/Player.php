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
    protected $handStrategy;
    protected $trickStrategy;
    protected $selector;

    public function __construct($id, $data = null)
    {
        $name = $data['name'];
        if (is_null($name)) {
            $name = 'Player ' . $id;
        }

        $this->id = $id;
        $this->name = $name;
        $this->selector = new DefaultSelector([]);
        $this->riskTolerance = !empty($data['riskTolerance']) ? $data['riskTolerance'] : rand(1,40)/100;
        $this->writeln($this->name . ' has riskTolerance ' . $this->riskTolerance);
    }

    public function addHand($hand, $isHoldHand)
    {
        $this->hand = $hand;
        $this->isHoldHand = $isHoldHand;
        $this->handStrategy = $this->selector->getRoundStrategy($hand->getCards(), $isHoldHand, $this->gameScores, $this->riskTolerance);
        if ($this->handStrategy === 'shootTheMoon') {
            print $this->name . ' says I shall try to shoot the moon'."\n";
            $this->selector = new ShootTheMoonSelector([]);
        } else {
            $this->selector = new DefaultSelector([]);
        }
        $this->cardsPlayedThisRound = [];
        $this->cardPlayed = null;
        $this->playersVoidInSuit = [[],[],[],[]];
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
        $newHandStrategy = $this->selector->getRoundStrategy($this->hand->getCards(), true, $this->gameScores, $this->riskTolerance);
        if ($this->handStrategy !== $newHandStrategy) {
            $this->handStrategy = $newHandStrategy;
            $this->selector = $this->getSelector();
            $this->writeln($this->name . ' says I shall change my strategy to ' . $this->handStrategy);
        }
        // if ($this->handStrategy === 'shootTheMoon') {
        //     $this->handStrategy = $this->selector->getRoundStrategy($this->hand->getCards(), $this->isHoldHand, $this->gameScores, $this->riskTolerance);
        //     if ($this->handStrategy !== 'shootTheMoon') {
        //         print $this->name . ' says I shall no longer shoot the moon'."\n";
        //         $this->selector = new DefaultSelector([]);
        //     }
        // }
    }

    protected function getSelector()
    {
        switch ($this->handStrategy) {
            case 'shootTheMoon':
                return new ShootTheMoonSelector([]);
            default:
                return new DefaultSelector([]);
        }
    }

    protected function selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick)
    {
        return $this->selector->selectCard([
            'riskTolerance' => $this->riskTolerance,
            'eligibleCards' => $eligibleCards,
            'allCards' => $this->hand->getCards(),
            'isFirstTrick' => $isFirstTrick,
            'cardsPlayedThisTrick' => $cardsPlayedThisTrick,
            'handStrategy' => $this->handStrategy,
            'trickStrategy' => $this->trickStrategy,
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
            'playersVoidInSuit' => $this->playersVoidInSuit,
            'yetToPlay' => array_values(array_diff(array_diff([1,2,3,4], array_keys($cardsPlayedThisTrick)), [$this->id])),
        ]);
    }

    protected function selectLeadCard($eligibleCards, $isFirstTrick)
    {
        return $this->selector->selectLeadCard([
            'riskTolerance' => $this->riskTolerance,
            'eligibleCards' => $eligibleCards,
            'allCards' => $this->hand->getCards(),
            'isFirstTrick' => $isFirstTrick,
            'handStrategy' => $this->handStrategy,
            'trickStrategy' => $this->trickStrategy,
            'cardsPlayedThisTrick' => [],
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
            'playersVoidInSuit' => $this->playersVoidInSuit,
            'yetToPlay' => array_values(array_diff([1,2,3,4], [$this->id])),
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
            if ($suit !== $leadSuit && !in_array($id, $this->playersVoidInSuit[$leadSuit])) {
                $this->playersVoidInSuit[$leadSuit][] = $id;
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
