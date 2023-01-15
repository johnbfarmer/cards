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
            $probabilityOfSomeoneVoidInSuit[$i] = $data['playersVoidInSuit'][$i] ? 1 : $this->getProbabilityOfOneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
        }
        $data['numberOfPlayersAfterMe'] = 3;
        $data['probabilityOfSomeoneVoidInSuit'] = $probabilityOfSomeoneVoidInSuit;
        $data['probabilityOfPointsThisTrick'] = .5; // tbi, this depends on the card. perhaps handle in the fcn below on a suit-by-suit basis
        $ratingsMatrix = $this->calculateRatings($data, $unplayedCards);
// $this->writeln($ratingsMatrix);
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
            $rating = $ratingsMatrix[$idx]['rating'];
$this->writeln("idx $idx ".$c->getDisplay()." rating $rating (shootTheMoon)");
            $ratings[] = ['rating' => $rating, 'idx' => $idx];
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

    public function selectCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        if (count($eligibleCards) === 1) {
            return array_keys($eligibleCards)[0];
        }

        [$singleSuit, $suit] = $this->allSameSuit($eligibleCards);
        if ($singleSuit && $data['isFirstTrick']) {
            if ($suit == 0) {
                return array_keys($eligibleCards)[0];
            }

            // unlikely, but suppose you have only spades or diamonds and point cards...
            return array_keys($eligibleCards)[0];
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
            $probabilityOfSomeoneVoidInSuit[$i] = $data['playersVoidInSuit'][$i] ? 1 : $this->getProbabilityOfOneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
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
        $data['numberOfPlayersAfterMe'] = $numberOfPlayersAfterMe;
        $data['leadSuit'] = $suit;
        $data['highestVal'] = $highestVal;
        $data['numberOfPlayersAfterMe'] = $numberOfPlayersAfterMe;
        $ratingsMatrix = $this->calculateRatings($data, $unplayedCards);
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
            $rating = $ratingsMatrix[$idx]['rating'];
$this->writeln("idx $idx ".$c->getDisplay()." rating $rating (shootTheMoon)");
            $ratings[] = ['rating' => $rating, 'idx' => $idx];
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

    protected function calculateRatings($data, $unplayedCards)
    {
        $h = [];
        $cards = $data['eligibleCards'];
        $probabilityOfPointsThisTrick = $data['probabilityOfPointsThisTrick'];
        arsort($cards);
        foreach ($cards as $idx => $c) {
            $s = $c->getSuit();
            $v = $c->getValue();
            if ($s == 2 || ($s == 3 && $v == 10)) {
                $probabilityOfPointsThisTrick = 1;
            } else {
                $probabilityOfPointsThisTrick = $data['probabilityOfPointsThisTrick'];
            }
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
            $probabilityOfSomeoneVoidInSuit = $data['probabilityOfSomeoneVoidInSuit'][$s];
            if ($unplayedCardsHigher - $naturalIndex - $shields < 1) {
                $shields++;
            }
            $probabilityOfImprovingHand = !$ct ? 0 : $unplayedCardsHigher / $ct;
            $riskTolerance = $data['riskTolerance'];
            $vulnerability = !$ct ? 0 : $ct > $shields ? ($unplayedCardsHigher - $shields) / ($ct - $shields) : 0;
            $shieldedness = min(1 - $vulnerability, 1);
$this->writeln("idx $idx $dspl");
            if (!empty($data['leadSuit'])) {
                $isLeadSuit = $s == $data['leadSuit'];
                $takesTrick = $isLeadSuit && $v > $data['highestVal'];
                $yetToPlayAndVoid = array_values(array_intersect($data['yetToPlay'], $data['playersVoidInSuit'][$s]));
                $nonvoidYetToPlay = $data['numberOfPlayersAfterMe'] - count($yetToPlayAndVoid);
                $probabilityOtherTakesTrick = !$takesTrick ? 1 : (!$ct ? 0 : 1 - pow($unplayedCardsLower/$ct, $nonvoidYetToPlay));
                $probabilityOfTakingTrick = 1 - $probabilityOtherTakesTrick;
            }
            $probabilityOfOthersTakingPoints = $probabilityOfPointsThisTrick * $probabilityOfOthersTakingTrick;
$this->writeln("probabilityOfOthersTakingPoints $probabilityOfOthersTakingPoints");
// $this->writeln("probabilityOfSomeoneVoidInSuit $probabilityOfSomeoneVoidInSuit");
// $this->writeln("shieldedness $shieldedness");
$this->writeln("probabilityOfImprovingHand $probabilityOfImprovingHand");

            $rating = $probabilityOfImprovingHand > $probabilityOfOthersTakingPoints && $riskTolerance > $probabilityOfOthersTakingPoints ? $riskTolerance * $probabilityOfImprovingHand + (1-$riskTolerance) * (1-$probabilityOfOthersTakingPoints) : 1 - $probabilityOfOthersTakingPoints;
            $ratingCalc = $probabilityOfImprovingHand > $probabilityOfOthersTakingPoints && $riskTolerance > $probabilityOfOthersTakingPoints ? "$riskTolerance * $probabilityOfImprovingHand + ".(1-$riskTolerance)." * ".(1-$probabilityOfOthersTakingPoints) : "1 - $probabilityOfOthersTakingPoints";
$this->writeln("rating $rating ($ratingCalc)");
            $h[$s][] = [
                'v' => $v,
                'i' => $idx,
                'd' => $dspl,
                'danger' => 0,
                'probabilityOfTakingTrick' => $probabilityOfTakingTrick,
                'shieldedness' => $shields / ($naturalIndex + 1),
                'rating' => $rating + rand(-50,50)/10000,
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
}