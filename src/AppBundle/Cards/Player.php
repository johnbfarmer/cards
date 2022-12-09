<?php

namespace AppBundle\Cards;

class Player extends BaseProcess {
    protected $hand;
    protected $name;
    protected $score = 0;
    protected $cardPlayed;
    protected $cardsPlayedThisRound = [];
    protected $handStrategy;

    public function __construct($id, $name = null)
    {
        if (is_null($name)) {
            $name = 'Player ' . $id;
        }

        $this->name = $name;
    }

    public function addHand($hand)
    {
        $this->hand = $hand;
        $this->handStrategy = Selector::getStrategy(['hand' => $hand]);
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
        $this->writeln($this->name . ' has ' . $this->score . ' points.');
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
            $suit = $cardsPlayedThisTrick[0]->getSuit();
            $eligibleCards = $this->hand->getEligibleCards($suit, $isFirstTrick);
            $eligibleIdx = $this->selectCard($eligibleCards, $isFirstTrick);
            $cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
        }

        return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx);
    }

    public function getCardsToPass($dirLabel)
    {
        return $this->hand->getCardsToPass(Selector::selectCardsToPass(['hand' => $this->hand->getCards()]));
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
        if (isset($info['cardsPlayed'])) {
            $this->cardsPlayedThisRound = array_merge($this->cardsPlayedThisRound, $info['cardsPlayed']);
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
        $this->score += $pts;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function getName()
    {
        return $this->name;
    }
}
