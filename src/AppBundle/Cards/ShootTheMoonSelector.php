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
$this->writeln("cardToTake $cardToTake");
        	if ($cardToTake > -1) {
        		return $cardToTake;
        	}

        	return $this->mostDangerous($eligibleCards)[0]; // abandon STM
        }
        if ($amLastToPlay) {
        	return $this->leastDangerous($eligibleCards)[0];
        }

        $idx = $this->selectCardByAnalysis($data)[0];

		return $idx;
    }

	protected function selectCardByAnalysis($data) {
        $canTakeTrick = $this->canTakeThisTrick($data);
        if (!$canTakeTrick) {
$this->writeln('i cant take it');
        	return $this->leastDangerous($data['eligibleCards']);
        }
        $unplayedCards = $this->getCardsRemaining($data['cardsPlayedThisRound'], $data['cardsPlayedThisTrick'], $data['allCards']);
        $numUnplayed = count($unplayedCards[0]) + count($unplayedCards[1]) + count($unplayedCards[2]) + count($unplayedCards[3]);
        $probabilityOfSomeoneVoidInSuit = [];
        for ($i = 0; $i < 4; $i++) {
            $numUnplayedThisSuit = count($unplayedCards[$i]);
            $probabilityOfSomeoneVoidInSuit[$i] = $this->getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
        }
        $suit = array_values($data['cardsPlayedThisTrick'])[0]->getSuit();
        $highestVal = 0;
        foreach ($data['cardsPlayedThisTrick'] as $c) {
        	$value = $c->getValue();
        	if ($value > $highestVal) {
        		$highestVal = $value;
        	}
        }
        $numberOfPlayersAfterMe = 3 - count($data['cardsPlayedThisTrick']);
        $probabilityOfPointsThisTrick = 1 - pow(1 - $probabilityOfSomeoneVoidInSuit[$suit], $numberOfPlayersAfterMe);
        $vulnerabilityMatrix = $this->calculateVulnerabilities($data, $unplayedCards);
$this->writeln($vulnerabilityMatrix);
// $this->writeln($probabilityOfSomeoneVoidInSuit[$suit]);
// $this->writeln(1-$probabilityOfSomeoneVoidInSuit[$suit]);
// $this->writeln(1 - pow(1 - $probabilityOfSomeoneVoidInSuit[$suit], $numberOfPlayersAfterMe));
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
        	// what should the rating be based upon?
        	// can I take this trick? if not throw low
        	// is it likely I need to take this trick?
        	// how does taking it affect my future hand?
        	// how does not taking it affect my future hand?
            $value = $c->getValue();
            $takesTrick = $value > $highestVal;
            $unplayedCardsLower = 0;
            foreach ($unplayedCards[$suit] as $u) {
                if ($u < $value) {
                    $unplayedCardsLower++;
                }
            }
            $ct = count($unplayedCards[$suit]);
            $probabilityOfTakingTrick = !$takesTrick ? 0 : (!$ct ? 1 : $unplayedCardsLower / $ct);
            $vulnerabilityDelta = $vulnerabilityMatrix[$idx];
$this->writeln("numberOfPlayersAfterMe $numberOfPlayersAfterMe probabilityOfPointsThisTrick $probabilityOfPointsThisTrick probabilityOfTakingTrick $probabilityOfTakingTrick vulnerabilityDelta $vulnerabilityDelta");

			$rating = $this->calculateRating([
				'vulnerabilityDelta' => $vulnerabilityDelta,
				'probabilityOfPointsThisTrick' => $probabilityOfPointsThisTrick,
				'probabilityOfTakingTrick' => $probabilityOfTakingTrick,
			]);
            // we need here to consider:
            //      do I have a lot of this suit?
            //      should I try to get rid of vulnerable cards now or hold off?
            //      how likely is it I will need a fall-back plan?

            // try to get rid of low cards if relatively safe and you don't have many:
            // $threshold = .12;
            // if (!$ct || $probabilityOfSomeoneVoidInSuit[$suit] < $threshold) {
            //     // low is good
            //     $rating = 20 - 1 - $value;
            // } else {
            //     $rating = 20 * $unplayedCardsLower  / count($unplayedCards[$suit]);
            // }
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

    protected function calculateVulnerabilities($data, $unplayedCards)
    {
    	// average of each card's vulnerability, which is the likelihood it will be overtaken by another card of the same suit
    	// v = 1 none of your cards can take anything; 0, they all can
    	$tmp = [];
    	foreach ($data['allCards'] as $idx => $c) {
    		$suit = $c->getSuit();
    		$ct = count($unplayedCards[$suit]);
    		if (!$ct) {
    			$tmp[$idx] = 0;
    			continue;
    		}
    		$value = $c->getValue();
            $unplayedCardsHigher = 0;
            foreach ($unplayedCards[$suit] as $u) {
                if ($u > $value) {
                    $unplayedCardsHigher++;
                }
            }
            $tmp[$idx] = $unplayedCardsHigher / $ct;
    	}

    	$totalVulnerability = 0;
    	foreach ($tmp as $idx => $v) {
    		$totalVulnerability += $v;
    	}

    	$ct = count($data['allCards']);
    	$tmp['total'] = $totalVulnerability / $ct;
    	if ($ct < 2) {
    		return $tmp;
    	}
    	foreach ($tmp as $idx => $v) {
    		if ($idx !== 'total') {
	    		$tmp[$idx] = ($totalVulnerability - $v) / ($ct - 1) - $tmp['total']; // vuln delta for each card in hand
    		}
    	}

    	return $tmp;
    }

    protected function calculateRating($data)
    {
    	$probabilityOfOthersTakingPoints = max($data['probabilityOfPointsThisTrick'] * (1 - $data['probabilityOfTakingTrick']), .001);
    	$this->writeln("calculateRating: probabilityOfOthersTakingPoints $probabilityOfOthersTakingPoints vulnerabilityDelta {$data['vulnerabilityDelta']}");
    	return -1 * $data['vulnerabilityDelta'] / $probabilityOfOthersTakingPoints;
    }
}