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

    public function __construct($id, $name = null)
    {
        if (is_null($name)) {
            $name = 'Player ' . $id;
        }

        $this->id = $id;
        $this->name = $name;
        $this->riskTolerance = rand(1,100);
        print $this->name . ' has riskTolerance ' . $this->riskTolerance . " (not yet used)\n";
    }

    public function addHand($hand, $isHoldHand)
    {
        $this->hand = $hand;
        $this->isHoldHand = $isHoldHand;
        $this->handStrategy = Selector::getRoundStrategy($hand->getCards(), $isHoldHand, $this->gameScores);
        if ($this->handStrategy === 'shootTheMoon') {
            print $this->name . ' says I shall try to shoot the moon'."\n";
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
// foreach ($eligibleCards as $idx => $c) {
//     print "lead elg $idx ".$c->getDisplay() . "\n";
// }
                $eligibleIdx = $this->selectLeadCard($eligibleCards, $isFirstTrick);
                $cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
// print "lead card selected idx $eligibleIdx $cardToPlayIdx\n";
            }
        } else {
            $suit = array_values($cardsPlayedThisTrick)[0]->getSuit();
            $eligibleCards = $this->hand->getEligibleCards($suit, $isFirstTrick);
// foreach ($eligibleCards as $idx => $c) {
//     print "elg $idx ".$c->getDisplay() . "\n";
// }
            $eligibleIdx = $this->selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick);
            // $cardToPlayIdx = $eligibleCards[$eligibleIdx];
            $cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
// print "selected idx $eligibleIdx $cardToPlayIdx\n";
        }

        return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx);
    }

    public function getCardsToPass($dirLabel)
    {
        return $this->hand->getCardsToPass(Selector::selectCardsToPass(['hand' => $this->hand->getCards(), 'gameScores' => $this->gameScores, 'strategy' => $this->handStrategy]));
    }

    public function receivePassedCards($c)
    {
        $this->hand->addCards($c);
        if ($this->handStrategy === 'shootTheMoon') {
            $this->handStrategy = Selector::getRoundStrategy($this->hand->getCards(), $this->isHoldHand, $this->gameScores);
            if ($this->handStrategy !== 'shootTheMoon') {
                print $this->name . ' says I shall no longer shoot the moon'."\n";
            }
        }
    }

    protected function selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick)
    {
        return Selector::selectCard([
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
        return Selector::selectLeadCard([
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
