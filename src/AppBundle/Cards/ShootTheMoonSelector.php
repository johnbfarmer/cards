<?php

namespace AppBundle\Cards;

class ShootTheMoonSelector extends BaseSelector {
    public function selectLeadCard($data) {
        if (count($data['eligibleCards']) === 1) {
            return array_keys($data['eligibleCards'])[0];
        }

        $unplayedCards = $this->getCardsRemaining($data['cardsPlayedThisRound'], [], $data['allCards']);
        $numUnplayed = count($unplayedCards[0]) + count($unplayedCards[1]) + count($unplayedCards[2]) + count($unplayedCards[3]);
        $probabilityOfSomeoneVoidInSuit = [];
        for ($i = 0; $i < 4; $i++) {
            $numUnplayedThisSuit = count($unplayedCards[$i]);
            $probabilityOfSomeoneVoidInSuit[$i] = $data['playersVoidInSuit'][$i] ? 1 : $this->getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
        }
        $numberOfPlayersAfterMe = 3;
        $data['probabilityOfSomeoneVoidInSuit'] = $probabilityOfSomeoneVoidInSuit;
        $data['probabilityOfPointsThisTrick'] = .5; // tbi, this depends on the card. perhaps handle in the fcn below on a suit-by-suit basis
        $dangerMatrix = $this->calculateDanger($data, $unplayedCards);
$this->writeln($dangerMatrix);
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
            $rating = $dangerMatrix[$idx]['rating'];
$this->writeln("idx $idx rating $rating");
            $ratings[] = ['rating' => $rating, 'idx' => $idx];
            print $idx.': '.$c->getDisplay() . " rating $rating\n";
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

        return $ret[0];
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
        if (count($eligibleCards) === 1) {
            return array_keys($eligibleCards)[0];
        }

        if ($this->allSameSuit($eligibleCards) && $data['isFirstTrick']) {
            return array_keys($eligibleCards)[0]; // lowest club <-- FIX; may not all be clubs. may want to select most dangerous
        }

        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $thisTrickHasPoints = $this->thisTrickHasPoints($cardsPlayedThisTrick);
        $amLastToPlay = count($cardsPlayedThisTrick) === 3;
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

        $idx = $this->selectCardByAnalysis($data)[0];

        return $idx;
    }

    protected function selectCardByAnalysis($data) {
        $canTakeTrick = $this->canTakeThisTrick($data);
        if (!$canTakeTrick) {
            return $this->leastDangerous($data['eligibleCards']);
        }
        $unplayedCards = $this->getCardsRemaining($data['cardsPlayedThisRound'], $data['cardsPlayedThisTrick'], $data['allCards']);
        $numUnplayed = count($unplayedCards[0]) + count($unplayedCards[1]) + count($unplayedCards[2]) + count($unplayedCards[3]);
        $probabilityOfSomeoneVoidInSuit = [];
        for ($i = 0; $i < 4; $i++) {
            $numUnplayedThisSuit = count($unplayedCards[$i]);
            $probabilityOfSomeoneVoidInSuit[$i] = $data['playersVoidInSuit'][$i] ? 1 : $this->getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
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
        $data['probabilityOfPointsThisTrick'] = $probabilityOfPointsThisTrick;
        $data['probabilityOfSomeoneVoidInSuit'] = $probabilityOfSomeoneVoidInSuit;
        $dangerMatrix = $this->calculateDanger($data, $unplayedCards);
$this->writeln($dangerMatrix);
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
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
            $rating = $dangerMatrix[$idx]['rating'];
$this->writeln("idx $idx rating $rating");
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

    protected function calculateDanger($data, $unplayedCards)
    {
        $h = [];
        // $cards = $data['allCards'];
        $cards = $data['eligibleCards'];
        arsort($cards);
        foreach ($cards as $idx => $c) {
            $s = $c->getSuit();
            $v = $c->getValue();
            $dspl = $c->getDisplay();
            if (empty($h[$s])) {
                $h[$s] = [];
                $shields = 0;
                $naturalIndex = 0;
            }
            $unplayedCardsLower = 0;
            $naturalIndex++;
            foreach($unplayedCards[$s] as $uc) {
                if ($uc < $v) {
                    $unplayedCardsLower++;
                }
            }
            $ct = count($unplayedCards[$s]);
            $unplayedCardsHigher = $ct - $unplayedCardsLower;
            $probabilityOfTakingTrick = !$ct ? 1 : $unplayedCardsLower / $ct;
            $probabilityOfOthersTakingTrick = 1 - $probabilityOfTakingTrick;
            $numberOfOthersExpectedNonvoid = 3 * (1 - $data['probabilityOfSomeoneVoidInSuit'][$s]);
            if ($unplayedCardsHigher - $naturalIndex - $shields < 1) {
                $shields++;
            }
            $probabilityOfImprovingHand = !$numberOfOthersExpectedNonvoid ? 0 : ($ct / $numberOfOthersExpectedNonvoid) - 1;
            $riskTolerance = .2; // tbi
            $probabilityOfOthersTakingPoints = $data['probabilityOfPointsThisTrick'] * $probabilityOfOthersTakingTrick;
            $vulnerability = !$ct ? 0 : $ct > $shields ? ($unplayedCardsHigher - $shields) / ($ct - $shields) : 0;
            $shieldedness = min(1 - $vulnerability, 1);
            // probabilityOfImprovingHand .5 (i)
            // probabilityOfOthersTakingPoints .2 (t)
            // riskTolerance .8 (r)
            // shieldedness .67 (s)
            // rating formula: (t > 0 && s > .5) ? 1 - s : (i > t && r > t ? ri + (1-r)(1-t) : 1 - t)
            $rating = ($probabilityOfOthersTakingPoints > 0 && $shieldedness > .5) ? 1 - $shieldedness : ($probabilityOfImprovingHand > $probabilityOfOthersTakingPoints && $riskTolerance > $probabilityOfOthersTakingPoints ? $riskTolerance * $probabilityOfImprovingHand + (1-$riskTolerance) * (1-$probabilityOfOthersTakingPoints) : 1 - $probabilityOfOthersTakingPoints);
$this->writeln("idx $idx $dspl");
$this->writeln("probabilityOfOthersTakingPoints $probabilityOfOthersTakingPoints");
$this->writeln("shieldedness $shieldedness");
$this->writeln("probabilityOfImprovingHand $probabilityOfImprovingHand");
$this->writeln("riskTolerance $riskTolerance");
$this->writeln("part 1 probabilityOfOthersTakingPoints > 0 && shieldedness > .5:  ".($probabilityOfOthersTakingPoints > 0 && $shieldedness > .5));
$this->writeln("part 2 probabilityOfImprovingHand > probabilityOfOthersTakingPoints && riskTolerance > probabilityOfOthersTakingPoints: ".($probabilityOfImprovingHand > $probabilityOfOthersTakingPoints && $riskTolerance > $probabilityOfOthersTakingPoints));
            $h[$s][] = [
                'v' => $v,
                'i' => $idx,
                'd' => $dspl,
                'danger' => 0,
                'probabilityOfTakingTrick' => $probabilityOfTakingTrick,
                'numberOfOthersExpectedNonvoid' => $numberOfOthersExpectedNonvoid,
                'shieldedness' => $shields / ($naturalIndex + 1),
                'rating' => $rating,
            ];
        }

        $ret = [];

        foreach ($h as $suit => $arr) {
            foreach ($arr as $thing1) {
                $ret[$thing1['i']] = $thing1;
            }
        }

        return $ret;
    }

    protected function calculateDangerForSuit($suit, $arr, $unplayedCards)
    {
        // danger is a measure of how likely is the card to succeed now or later. example AK9 of a suit, they should all succeed eventually,
        // but use this in combination with other metrics to not throw the 9 too early
        rsort($arr);
        $sizeOfBase = 3;
        if (count($arr) < 3) {
            $sizeOfBase = 2;
        }
        if (count($arr) < 2) {
            $sizeOfBase = 1;
        }
        if (count($arr) < 2) {
            $sizeOfBase = 0;
        }
        switch ($suit) {
            case 0:
                $hasTheTwo = false;
                foreach ($arr as $idx => $vi) {
                    if (!$vi['v']) {
                        $hasTheTwo = true;
                    }
                }
                if ($hasTheTwo) { // note if not 2 of clubs, use the default
                    $base = 0;
                    $ct = 0;
                    foreach ($arr as $idx => $vi) {
                        if ($ct++ < $sizeOfBase - 1) {
                            $base += 12 - $idx - $vi['v'];
                        }
                    }
                    foreach ($arr as $idx => $vi) {
                        $arr[$idx]['danger'] = $vi['v'] ? $base * (12 - $vi['v']) : 0;
                    }
                    return $arr;
                }
            default:
                $base = 0;
                $ct = 0;
                foreach ($arr as $idx => $vi) {
                    if ($ct++ < $sizeOfBase) {
                        $base += 12 - $idx - $vi['v'];
                    }
                }
                foreach ($arr as $idx => $vi) {
                    $arr[$idx]['danger'] = $base * (12 - $vi['v']) / 100;
                }
                return $arr;
        }
    }
}