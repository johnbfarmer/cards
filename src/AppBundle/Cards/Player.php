<?php

namespace AppBundle\Cards;

class Player extends BaseProcess {
    protected $hand;
    protected $id;
    protected $name;
    protected $myScore = 0;
    protected $gameScores = [];
    protected $roundScores = [];
    protected $cardPlayed;
    protected $cardsPlayedThisRound = [];
    protected $handStrategy;

    public function __construct($id, $name = null)
    {
        if (is_null($name)) {
            $name = 'Player ' . $id;
        }

        $this->id = $id;
        $this->name = $name;
    }

    public function addHand($hand, $isHoldHand)
    {
        $this->hand = $hand;
        $this->handStrategy = Selector::getRoundStrategy($hand->getCards(), $isHoldHand, $this->gameScores);
if ($this->handStrategy === 'shootTheMoon') {
    print $this->name . ' says I shall shoot the moon'."\n";
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
        if (empty($cardsPlayedThisTrick)) {
            if ($isFirstTrick && $this->hasCard(0)) {
                $cardToPlayIdx = 0;
            } else {
                $eligibleCards = $this->hand->getEligibleLeadCards($isBrokenHearts);
                $eligibleIdx = $this->selectLeadCard($eligibleCards, $isFirstTrick);
                $cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
            }
        } else {
            $suit = array_values($cardsPlayedThisTrick)[0]->getSuit();
            // $suit = $cardsPlayedThisTrick[0]->getSuit();
            $eligibleCards = $this->hand->getEligibleCards($suit, $isFirstTrick);
            $eligibleIdx = $this->selectCard($eligibleCards, $isFirstTrick);
            $cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
        }

        return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx);
    }

    public function getCardsToPass($dirLabel)
    {
        return $this->hand->getCardsToPass(Selector::selectCardsToPass(['hand' => $this->hand->getCards(), 'scores' => $this->gameScores, 'strategy' => $this->handStrategy]));
    }

    public function addCards($c)
    {
        return $this->hand->addCards($c);
    }

    protected function selectCard($eligibleCards, $isFirstTrick)
    {
        return Selector::selectCard([
            'eligibleCards' => $eligibleCards,
            'isFirstTrick' => $isFirstTrick,
            'handStrategy' => $this->handStrategy,
        ]);
    }

    protected function selectLeadCard($eligibleCards, $isFirstTrick)
    {
        return Selector::selectLeadCard([
            'eligibleCards' => $eligibleCards,
            'isFirstTrick' => $isFirstTrick,
            'handStrategy' => $this->handStrategy,
        ]);
    }

    public function gatherInfo($info)
    {
        $leadSuit = null;
        $topValue = null;
        $this->gameScores = !empty($info['scores']) ? $info['scores'] : $this->gameScores;
        if (!empty($info['scores'])) {
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
        }
        $this->gameScores[$takesTrick] += $points;
        $this->roundScores[$takesTrick] += $points;
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
