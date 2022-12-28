<?php

namespace AppBundle\Cards;

class ShootTheMoonSelector extends BaseSelector {
	protected function selectLeadByStrategy($data) {
        $unplayedCards = $this->getCardsRemaining($data['cardsPlayedThisRound'], $data['cardsPlayedThisTrick'], $data['allCards']);
        $numUnplayed = count($unplayedCards[0]) + count($unplayedCards[1]) + count($unplayedCards[2]) + count($unplayedCards[3]);
        $probabilityOfSomeoneVoidInSuit = [];
        for ($i = 0; $i < 4; $i++) {
            $numUnplayedThisSuit = count($unplayedCards[$i]);
            $probabilityOfSomeoneVoidInSuit[$i] = $this->getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
        }
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
            $suit = $c->getSuit();
            $value = $c->getValue();
            $unplayedCardsHigher = 0;
            foreach ($unplayedCards[$suit] as $u) {
                if ($u < $value) {
                    $unplayedCardsHigher++;
                }
            }
            $ct = count($unplayedCards[$suit]);

            // we need here to consider:
            //      do I have a lot of this suit?
            //      should I try to get rid of vulnerable cards now or hold off?
            //      how likely is it I will need a fall-back plan?

            // try to get rid of low cards if relatively safe and you don't have many:
            $threshold = .12;
            if (!$ct || $probabilityOfSomeoneVoidInSuit[$suit] < $threshold) {
                // low is good
                $rating = 20 - 1 - $value;
            } else {
                $rating = 20 * $unplayedCardsHigher  / count($unplayedCards[$suit]);
            }
            $ratings[] = ['rating' => $rating, 'idx' => $idx];
            print $idx.': '.$c->getDisplay() . " rating $rating -- {$probabilityOfSomeoneVoidInSuit[$suit]}\n";
        }

        foreach ($ratings as $arr1) {
            $rating = $arr1['rating'];
            $insertIndex = 0;
            foreach ($sortedRatings as $localIdx => $arr2) {
                if ($rating <= $arr2['rating']) {
                    $insertIndex = $localIdx + 1;
                }
            }
            array_splice($sortedRatings, $insertIndex, 0, [$arr1]);
        }

        foreach ($sortedRatings as $arr) {
            $ret[] = $arr['idx'];
        }

        return $ret;
    }

    public function selectCardsToPass($data)
    {
        $cards = $data['hand'];
        $scores = $data['gameScores'];
        $strategy = $data['strategy'];

        $myCardCounts = $this->countMyCardsBySuit($cards);
        $h = [];
        foreach ($cards as $idx => $c) {
            $s = $c->getSuit();
            $v = $c->getValue();
            $dspl = $c->getDisplay();
            if (empty($h[$s])) {
                $h[$s] = [];
            }
            $h[$s][] = ['v' => $v, 'i' => $idx, 'd' => $dspl, 'danger' => 0];
        }

        foreach ($h as $suit => $arr) {
            $h[$suit] = $this->calculateDangerForShootingTheMoon($suit, $arr);
        }

        $sorted = [];

        foreach ($h as $suit => $arr) {
            foreach ($arr as $thing1) {
                $insertIndex = 0;
                foreach ($sorted as $i => $thing2) {
                    if ($thing1['danger'] < $thing2['danger']) {
                        $insertIndex = $i + 1;
                    }
                }
                array_splice($sorted, $insertIndex, 0, [$thing1]);
            }
        }
        $indexes = [];
        $ct = 0;
        foreach ($sorted as $thing2) {
            if ($ct++ < 3) {
                $indexes[] = $thing2['i'];
            }
        }
        arsort($indexes);

        return $indexes;
    }

    public function selectCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $handStrategy = $data['handStrategy'];
        $cardsPlayedThisRound = $data['cardsPlayedThisRound'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        if (count($eligibleCards) === 1) {
            return 0;
        }

        if ($this->allSameSuit($eligibleCards)) {
            if ($data['isFirstTrick']) {
                return 0; // lowest club
            }
            return $this->selectCardSingleSuit($data);
        }

        return $this->selectCardSingleSuit($data);
        // return $this->getIdxBestCardAvailable($data);
    }

    public function selectCardSingleSuit($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $idxToReturn = -1;

        // if there are points we have to take it
        // if not and we are last, throw low
        // if we don't know, look at probabilities
        $thisTrickHasPoints = $this->thisTrickHasPoints($cardsPlayedThisTrick);
        $amLastToPlay = count($cardsPlayedThisTrick) === 3;
$this->writeln("thisTrickHasPoints $thisTrickHasPoints amLastToPlay $amLastToPlay");
        if ($thisTrickHasPoints) {
        	$cardToTake = $amLastToPlay ? $this->lowestTakeCard($data) : $this->highestTakeCard($data);
        	if ($cardToTake > -1) {
        		return $cardToTake;
        	}

        	return $this->mostDangerous($eligibleCards)[0]; // abandon STM
        }
        if ($amLastToPlay) {
        	return $this->leastDangerous($eligibleCards)[0];	
        }

        $idx = $this->selectLeadByStrategy($data)[0];  // tbi

		return $idx;
    }
}