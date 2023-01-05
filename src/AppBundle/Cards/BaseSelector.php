<?php

namespace AppBundle\Cards;

class BaseSelector extends BaseProcess {
    protected $importanceGivenToVoid = .5;

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
        $ratingsMatrix = $this->calculateRatings($data, $unplayedCards);
$this->writeln($ratingsMatrix);
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
            $rating = $ratingsMatrix[$idx]['rating'];
$this->writeln("idx $idx ".$c->getDisplay()." rating $rating (avoidPoints)");
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
                return array_keys($eligibleCards)[count($eligibleCards) - 1];
            }

            // unlikely, but suppose you have only spades or diamonds and point cards...
            return array_keys($eligibleCards)[count($eligibleCards) - 1];
        }

        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $thisTrickHasPoints = $this->thisTrickHasPoints($cardsPlayedThisTrick);
        $amLastToPlay = count($cardsPlayedThisTrick) === 3;
        $data['thisTrickHasPoints'] = $thisTrickHasPoints;
        if (!$thisTrickHasPoints && $amLastToPlay) {
            return $this->mostDangerous($eligibleCards)[0];
        }

        $idx = $this->selectCardByAnalysis($data)[0];

        return $idx;
    }

    protected function selectCardByAnalysis($data) {
        $canTakeTrick = $this->canTakeThisTrick($data);
        if (!$canTakeTrick) {
            return $this->mostDangerous($data['eligibleCards']);
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
        $probabilityOfPointsThisTrick = $data['isFirstTrick'] ? 0 : 1 - pow(1 - $probabilityOfSomeoneVoidInSuit[$suit], $numberOfPlayersAfterMe);
        $data['probabilityOfPointsThisTrick'] = $probabilityOfPointsThisTrick;
        $data['probabilityOfSomeoneVoidInSuit'] = $probabilityOfSomeoneVoidInSuit;
        $ratingsMatrix = $this->calculateRatings($data, $unplayedCards);
$this->writeln($ratingsMatrix);
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
            $rating = $ratingsMatrix[$idx]['rating'];
$this->writeln("idx $idx ".$c->getDisplay()." rating $rating (avoid pts)");
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

    public function allSameSuit($cards)
    {
        $allAreSameSuit = true;
        $suit = null;
        foreach($cards as $idx => $c) {
            if (is_null($suit)) {
                $suit = $c->getSuit();
            }
            if ($c->getSuit() !== $suit) {
                $allAreSameSuit = false;
                break;
            }
        }

        return [$allAreSameSuit, $suit];
    }

    public function getIdxBestCardAvailable($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $handStrategy = $data['handStrategy'];
        $trickStrategy = $data['trickStrategy'];
        $cardsPlayedThisRound = $data['cardsPlayedThisRound'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $idxToReturn = 0;
        switch($trickStrategy) {
            case 'highestNoTake':
            default:
                $suit = null;
                $highestVal = -1;
                $takingTrick = 0;
                foreach ($cardsPlayedThisTrick as $playerId => $c) {
                    if (is_null($suit)) {
                        $suit = $c->getSuit();
                    }
                    if ($c->getSuit() === $suit && $c->getValue() > $highestVal) {
                        $highestVal = $c->getValue();
                        $takingTrick = $playerId;
                    }
                }
                // we do not have to follow suit, so throw most dangerous
                $dangerous = $this->mostDangerous($eligibleCards);

                return $dangerous[0]; // <-- huh? what about all the work above?
        }
    }

    protected function lowestTakeCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $suit = null;
        $highestVal = null;
        foreach ($cardsPlayedThisTrick as $c) {
            $suit = is_null($suit) ? $c->getSuit() : $suit;
            $val = $c->getValue();
            $highestVal = is_null($highestVal) ? $val : ($val > $highestVal ? $val : $highestVal);
        }
        $idxToReturn = -1;
        foreach ($eligibleCards as $idx => $c) {
            $idxToReturn++;
            if ($c->getSuit() != $suit) {
                continue;
            }
            if ($c->getValue() > $highestVal) {
$this->writeln("lowestTakeCard idxToReturn $idx ");
                return $idx;
            }
        }
        return -1;
    }

    protected function highestTakeCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $suit = null;
        $highestVal = null;
        foreach ($cardsPlayedThisTrick as $c) {
            $suit = is_null($suit) ? $c->getSuit() : $suit;
            $val = $c->getValue();
            $highestVal = is_null($highestVal) ? $val : ($val > $highestVal ? $val : $highestVal);
        }
        $idxToReturn = -1;
        arsort($eligibleCards);
        $iCanTakeIt = false;
        foreach ($eligibleCards as $idx => $c) {
            if ($c->getSuit() == $suit && $c->getValue() > $highestVal) {
                $idxToReturn = $idx;
                break;
            }
        }
$this->writeln(array_keys($eligibleCards));
$this->writeln("highestTakeCard idxToReturn $idxToReturn ");
        return $idxToReturn;
    }

    // return hand indices in reverse order of 3 cards to pass
    public function selectCardsToPass($data)
    {
        $cards = $data['hand'];
        $scores = $data['gameScores'];
        $strategy = $data['strategy'];
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
            $h[$suit] = $this->calculateDangerForAvoidingPoints($suit, $arr);
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

    protected function selectCardsToPassForShootingTheMoon($cards, $all = false)
    {
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

        return $sorted;
    }

    protected function calculateDangerForAvoidingPoints($suit, $arr)
    {
        switch ($suit) {
            case 3:
                foreach ($arr as $idx => $vi) {
                    $arr[$idx]['danger'] = $vi['v'] === 10 && count($arr) < 4 ? 1000 : ($vi['v'] >= 10 && count($arr) < 3 ? 900 + $vi['v'] : 0);
                }
                return $arr;
            case 0:
                $hasTheTwo = false;
                foreach ($arr as $idx => $vi) {
                    if (!$vi['v']) {
                        $hasTheTwo = true;
                    }
                }
                if ($hasTheTwo) {
                    $base = 0;
                    $ct = 0;
                    foreach ($arr as $idx => $vi) {
                        if ($ct++ < 4) {
                            $base += $vi['v'] - $idx;
                        }
                    }
                    foreach ($arr as $idx => $vi) {
                        $arr[$idx]['danger'] = $base * ($vi['v'] - $idx);
                    }
                    return $arr;
                }
            default:
                $base = 0;
                $ct = 0;
                foreach ($arr as $idx => $vi) {
                    if ($ct++ < 3) {
                        $base += $vi['v'] - $idx;
                    }
                }
                if ($suit === 2) {
                    $base *= 2;
                }
                foreach ($arr as $idx => $vi) {
                    $arr[$idx]['danger'] = $base * ($vi['v'] - $idx);
                }
                return $arr;
        }
    }

    protected function calculateDangerForShootingTheMoon($suit, $arr)
    {
        rsort($arr);
        switch ($suit) {
            case 2:
                foreach ($arr as $idx => $vi) {
                    $arr[$idx]['danger'] = 100 * (12 - $idx - $vi['v']) + 12 - $vi['v'];
                }
                return $arr;
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
                        if ($ct++ < 2) {
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
                    if ($ct++ < 3) {
                        $base += 12 - $idx - $vi['v'];
// let's analyze this. spose I have 345JQA of some suit. base will be 12-12 + 12-1-10 + 12-2-9 = 2
// danger will then be 0,2,4,18,20,22
// now if we have 45 of a suit base is 12-3 + 11-2 = 18
// danger 162,180
                    }
                }
                foreach ($arr as $idx => $vi) {
                    $arr[$idx]['danger'] = $base * (12 - $vi['v']);
                }
                return $arr;
        }
    }

    protected function countMyCardsBySuit($cards)
    {
        $ret = [0,0,0,0];
        foreach ($cards as $c) {
            $ret[$c->getSuit()]++;
        }

        return $ret;
    }

    protected function thisTrickHasPoints($cards)
    {
        foreach ($cards as $c) {
            $suit = $c->getSuit();
            if ($suit == 2 || ($suit == 3 && $c->getValue() == 10)) {
                return true;
            }
        }

        return false;
    }

    protected function canTakeThisTrick($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $suit = null;
        $highestVal = null;
        foreach ($cardsPlayedThisTrick as $c) {
            $suit = is_null($suit) ? $c->getSuit() : $suit;
            $val = $c->getValue();
            $highestVal = is_null($highestVal) ? $val : ($val > $highestVal ? $val : $highestVal);
        }
        rsort($eligibleCards);
        foreach ($eligibleCards as $c) {
            if ($c->getSuit() == $suit && $c->getValue() > $highestVal) {
                return true;
            }
        }

        return false;
    }

    public function getRoundStrategy($cards, $isHoldHand, $scores)
    {
        if ($this->shouldShootTheMoon($cards, $isHoldHand, $scores)) {
            return 'shootTheMoon';
        }
        return 'avoidPoints';
    }

    public function shouldShootTheMoon($cards, $noPassing, $scores)
    {
        $crds = $this->selectCardsToPassForShootingTheMoon($cards, true);
        $ct = 0;
        $numberOfCardsToPass = $noPassing ? 0 : 3;
        foreach ($crds as $c) {
            if (++$ct >  $numberOfCardsToPass && $c['danger'] > 100) {
                return false;
            }
        }

        return true;
    }

    protected function calculateRatings($data, $unplayedCards)
    {
        $h = [];
        $cards = $data['eligibleCards'];
        $myCardCounts = $this->countMyCardsBySuit($cards);
        $riskTolerance = $data['riskTolerance'];
        foreach ($cards as $idx => $c) {
            $s = $c->getSuit();
            $v = $c->getValue();
            $dspl = $c->getDisplay();
            if (empty($h[$s])) {
                $h[$s] = [];
                $shields = 0;
                $naturalIndex = 0;
                $shieldedness = 0;
            }
            $unplayedCardsLower = 0;
            foreach($unplayedCards[$s] as $uc) {
                if ($uc < $v) {
                    $unplayedCardsLower++;
                }
            }
            $ct = count($unplayedCards[$s]);
            $unplayedCardsHigher = $ct - $unplayedCardsLower;
            $probabilityOfTakingTrick = !$ct ? 1 : $unplayedCardsLower / $ct;
            $probabilityOfSomeoneVoidInSuit = $data['probabilityOfSomeoneVoidInSuit'][$s];
            $numberOfOthersExpectedNonvoid = 3 * $probabilityOfSomeoneVoidInSuit;
$this->writeln("unplayedCardsLower $unplayedCardsLower naturalIndex $naturalIndex shields $shields");
            if ($unplayedCardsLower - $naturalIndex++ - $shields < 1) {
                $shields++;
            }
            if ($ct) {
                $shields += ($ct - $unplayedCardsLower) / $ct;
                $shieldedness = !$unplayedCardsLower ? 0 : ($shields + $naturalIndex) / $unplayedCardsLower;
            }
            $probabilityOfImprovingHand = !$numberOfOthersExpectedNonvoid || !$ct ? 0 : (1 - $this->importanceGivenToVoid) * ($unplayedCardsLower / $ct) + $this->importanceGivenToVoid * (($ct - $myCardCounts[$s]) / $ct);
            $probabilityOfTakingPoints = $data['probabilityOfPointsThisTrick'] * $probabilityOfTakingTrick;
            // $vulnerability = !$ct ? 0 : $ct > $shields ? ($unplayedCardsHigher - $shields) / ($ct - $shields) : 0;
            // $shieldedness = min(1 - $vulnerability, 1);
            $naturalIndex++;
            // probabilityOfImprovingHand .5 (i)
            // probabilityOfOthersTakingPoints .2 (t)
            // riskTolerance .8 (r)
            // shieldedness .67 (s)
            // rating formula: (t > 0 && s > .5) ? 1 - s : (i > t && r > t ? ri + (1-r)(1-t) : 1 - t)
            $rating = ($probabilityOfTakingPoints === 0 && $shieldedness > .5) ? 1 - $shieldedness : ($probabilityOfImprovingHand > $probabilityOfTakingPoints && $riskTolerance < $probabilityOfTakingPoints ? $riskTolerance * $probabilityOfImprovingHand + (1-$riskTolerance) * (1-$probabilityOfTakingPoints) : .5*(1 - $probabilityOfTakingPoints) + .5 * $probabilityOfImprovingHand);
$this->writeln("idx $idx $dspl");
$this->writeln("probabilityOfTakingPoints $probabilityOfTakingPoints");
$this->writeln("shieldedness $shieldedness");
$this->writeln("probabilityOfSomeoneVoidInSuit ".$data['probabilityOfSomeoneVoidInSuit'][$s]);
$this->writeln("numberOfOthersExpectedNonvoid $numberOfOthersExpectedNonvoid");
$this->writeln("unplayedCardsHigher $unplayedCardsHigher");
$this->writeln("probabilityOfImprovingHand $probabilityOfImprovingHand");
$this->writeln("riskTolerance $riskTolerance");
$this->writeln("part 1 probabilityOfTakingPoints === 0 && shieldedness > .5:  ".($probabilityOfTakingPoints === 0 && $shieldedness > .5));
$this->writeln("part 2 probabilityOfImprovingHand > probabilityOfTakingPoints && riskTolerance < probabilityOfTakingPoints: ".($probabilityOfImprovingHand > $probabilityOfTakingPoints && $riskTolerance < $probabilityOfTakingPoints));
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

    protected function getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit)
    {
        $thoseWithThatSuit = 2 / 3 * $numUnplayed; // there are three other players, so 2/3 of the cards will contain all those cards of the suit
        $num = 1;
        $denom = 1;
        for ($i = 0; $i < $numUnplayedThisSuit; $i++) {
            $num *= ($thoseWithThatSuit - $i);
            $denom *= ($numUnplayed - $i);
        }
        if ($denom <= 0) {
            return 1;
        }

        $p1 = ($num / $denom);
$this->writeln("There is a $p1 chance that one player has none given $numUnplayedThisSuit | $numUnplayed");
        return 1 - pow(1 - $p1, 3);
    }

    protected function calculateExpectedPoints($suit, $value, $data)
    {
        $unplayedCards = $data['unplayedCards'];
        $probabilityOfSomeoneVoidInSuit = $data['probabilityOfSomeoneVoidInSuit'];
        $unplayedCardsLower = 0;
        foreach ($unplayedCards[$suit] as $u) {
            if ($u < $value) {
                $unplayedCardsLower++;
            }
        }
        $ct = count($unplayedCards[$suit]);
        $probabilityThatThisCardTakes = $ct === 0 ? 1 : $probabilityOfSomeoneVoidInSuit * $unplayedCardsLower / $ct;
        $queenAtLarge = !empty($unplayedCards[3][10]);
        $heartsLimit = $queenAtLarge ? 2 : 3;
        $maxHearts = min($heartsLimit, $unplayedCards[2]);
        $points = $maxHearts + $queenAtLarge ? 13 : 0;
        $expectedPoints = $probabilityThatThisCardTakes * $points;
        switch($suit) {
            case 3:
                $points = $maxHearts;
                if ($queenAtLarge || $value == 10) {
                    $points += $value >= 10 ? 13 : 0;
                }
                return $probabilityThatThisCardTakes * $points;
            case 2:
                $probabilityThatThisCardTakes = $ct === 0 ? 1 : $unplayedCardsLower / $ct;
                $points = $ct === 0 ? 1 : $maxHearts * $probabilityThatThisCardTakes;
                return ($points + $probabilityOfSomeoneVoidInSuit * 13) * $probabilityThatThisCardTakes;
            default:
                return $expectedPoints;
        }
    }

    public function mostDangerous($cards)
    {
        $this->writeln('mostDangerous');
        uasort(
            $cards,
            function($a, $b) {
                return -1*($a->getDanger() <=> $b->getDanger());
            }
        );

        return array_keys($cards);
    }

    public function leastDangerous($cards)
    {
        $this->writeln('leastDangerous');
        uasort(
            $cards,
            function($a, $b) {
                return ($a->getDanger() <=> $b->getDanger());
            }
        );

        return array_keys($cards);
    }

    protected function getCardsRemaining($cardsPlayedThisRound, $cardsPlayedThisTrick, $myCards)
    {
        $allCards = [
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
        ];
        $playedCards = [[],[],[],[],];
        foreach ($cardsPlayedThisRound as $id => $cards) {
            foreach ($cards as $c) {
                $playedCards[$c->getSuit()][] = $c->getValue();
            }
        }
        foreach ($cardsPlayedThisTrick as $id => $c) {
            $playedCards[$c->getSuit()][] = $c->getValue();
        }
        foreach ($myCards as $id => $c) {
            $playedCards[$c->getSuit()][] = $c->getValue();
        }

        $unplayedCards = [];

        for ($suit=0; $suit<4; $suit++) {
            $unplayedCards[$suit] = array_diff($allCards[$suit], $playedCards[$suit]);
        }

        return $unplayedCards;
    }

    protected function rateDanger($data, $singleSuit = false)
    {
        $allCards = $data['allCards'];
        $eligibleCards = $data['eligibleCards'];
        $handStrategy = $data['handStrategy'];
        $trickStrategy = $data['trickStrategy'];
        $cardsPlayedThisRound = $data['cardsPlayedThisRound'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $numberOfCardsPlayedThisTrick = count($cardsPlayedThisTrick);
        $leadPlayerId = array_keys($cardsPlayedThisTrick)[0];
        $leadSuit = $cardsPlayedThisTrick[$leadPlayerId]->getSuit();
        $points = 0;
        $numberUnplayed = [13, 13, 13, 13];
        $unplayedCards = $this->getCardsRemaining($cardsPlayedThisRound, $cardsPlayedThisTrick, $allCards);

        foreach ($cardsPlayedThisTrick as $id => $c) {
            $suit = $c->getSuit();
            $value = $c->getValue();
            if ($suit === 2) {
                $points++;
            }
            if ($suit === 3 && $value == 10) {
                $points += 13;
            }
        }
        foreach ($eligibleCards as $c) {
            $suit = $c->getSuit();
            $value = $c->getValue();
            // print count($unplayedCards[$suit]) . " of suit " . $suit . " left\n";
            // print 'unplayed : ' . implode(' ', $unplayedCards[$suit]) . "\n";
        }

        return $data;
    }
}
