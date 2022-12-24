<?php

namespace AppBundle\Cards;

class Selector extends BaseProcess {
    const STRATEGIES_ROUND = ['avoidPoints', 'shootTheMoon', 'blockShootTheMoon', 'protectOther', 'attackOther'];
    const STRATEGIES_TRICK = ['highestNoTake', 'takeHigh', 'takeLow', 'dumpHigh', 'dumpLow'];

    public static function selectCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $handStrategy = $data['handStrategy'];
        $cardsPlayedThisRound = $data['cardsPlayedThisRound'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        if (count($eligibleCards) === 1) {
            return 0;
        }

        if (self::allSameSuit($eligibleCards)) {
            if ($data['isFirstTrick']) {
                return $handStrategy === 'shootTheMoon' ? 0 : count($eligibleCards) - 1; // assuming you're not shooting the moon, throw the highest club
            }
            return self::getIdxBestCardAvailableOneSuit($data);
        }

        return self::getIdxBestCardAvailable($data);
    }

    public static function allSameSuit($cards)
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

        return $allAreSameSuit;
    }

    public static function selectLeadCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        if (count($eligibleCards) === 1) {
            return 0;
        }

        // $sortedEligibleCards = self::mostDangerous($eligibleCards);
        $sortedEligibleCards = self::sortByIdxForFewestPoints($data);

        switch ($data['handStrategy']) {
            case 'avoidPoints':
                return array_keys($sortedEligibleCards)[0];
                // return array_keys($sortedEligibleCards)[count($sortedEligibleCards) - 1];
            default:
                return rand(0, count($eligibleCards) - 1);
        }

    }

    public static function getIdxBestCardAvailable($data)
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
// foreach ($eligibleCards as $idx => $c) {
//     print "elg $idx ".$c->getDisplay() . "\n";
// }
                // we do not have to follow suit, so throw most dangerous
                $dangerous = self::mostDangerous($eligibleCards);
                print json_encode($dangerous)." <-- dangerous ordered\n";
                // return $dangerous[0];
                return array_keys($eligibleCards)[$dangerous[0]];
                // return self::mostDangerous($eligibleCards)[0];
        }
    }

    public static function getIdxBestCardAvailableOneSuit($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $handStrategy = $data['handStrategy'];
        $trickStrategy = $data['trickStrategy'];
        $cardsPlayedThisRound = $data['cardsPlayedThisRound'];
        $cardsPlayedThisTrick = $data['cardsPlayedThisTrick'];
        $idxToReturn = -1;

        // get the probability of taking points with each card
        $data = self::rateDanger($data, true); // tbi here
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
                foreach ($eligibleCards as $cardIdx => $c) {
                    if ($c->getValue() < $highestVal) {
                        $idxToReturn++;
                    }
                }
                return max($idxToReturn, 0);
        }
    }

    // return hand indices in reverse order of 3 cards to pass
    public static function selectCardsToPass($data)
    {
        $cards = $data['hand'];
        $scores = $data['gameScores'];
        $strategy = $data['strategy'];

        switch ($strategy) {
            case self::STRATEGIES_ROUND[1]:
                return self::selectCardsToPassForShootingTheMoon($cards);
            default:
                return self::selectCardsToPassForAvoidingPoints($cards);
        }
    }

    protected static function selectCardsToPassForAvoidingPoints($cards)
    {
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
            $h[$suit] = self::calculateDangerForAvoidingPoints($suit, $arr);
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

    protected static function selectCardsToPassForShootingTheMoon($cards, $all = false)
    {
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
            $h[$suit] = self::calculateDangerForShootingTheMoon($suit, $arr);
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
        if ($all) {
            return $sorted;
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

    protected static function calculateDangerForAvoidingPoints($suit, $arr)
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

    protected static function calculateDangerForShootingTheMoon($suit, $arr)
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
                if ($hasTheTwo) {
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
                    }
                }
                foreach ($arr as $idx => $vi) {
                    $arr[$idx]['danger'] = $base * (12 - $vi['v']);
                }
                return $arr;
        }
    }

    public static function getRoundStrategy($cards, $isHoldHand, $scores)
    {
        if (self::shouldShootTheMoon($cards, $isHoldHand, $scores)) {
            return self::STRATEGIES_ROUND[1];
        }
        return self::STRATEGIES_ROUND[0];
    }

    public static function shouldShootTheMoon($cards, $isHoldHand, $scores)
    {
        $crds = self::selectCardsToPassForShootingTheMoon($cards, true);
        $ct = 0;
        $numberOfCardsToPass = $isHoldHand ? 0 : 3;
        foreach ($crds as $c) {
            if (++$ct >  $numberOfCardsToPass && $c['danger'] > 100) {
                return false;
            }
        }

        return true;
    }

    protected static function sortByIdxForFewestPoints($data) {
        $unplayedCards = self::getCardsRemaining($data['cardsPlayedThisRound'], $data['cardsPlayedThisTrick'], $data['allCards']);
        $numUnplayed = count($unplayedCards[0]) + count($unplayedCards[1]) + count($unplayedCards[2]) + count($unplayedCards[3]); // - count($data['allCards']);
        $probabilityOfSomeoneVoidInSuit = [];
        for ($i = 0; $i < 4; $i++) {
            $numUnplayedThisSuit = count($unplayedCards[$i]);
            $probabilityOfSomeoneVoidInSuit[$i] = self::getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
        }
        $ratings = [];
        $sortedRatings = [];
        $ret = [];
        foreach ($data['eligibleCards'] as $idx => $c) {
            $suit = $c->getSuit();
            $value = $c->getValue();
            $unplayedCardsLower = 0;
            foreach ($unplayedCards[$suit] as $u) {
                if ($u < $value) {
                    $unplayedCardsLower++;
                }
            }
            $ct = count($unplayedCards[$suit]);
            $rating = self::calculateExpectedPoints(
                $suit,
                $value,
                [
                    'unplayedCards' => $unplayedCards,
                    'probabilityOfSomeoneVoidInSuit' => $probabilityOfSomeoneVoidInSuit[$suit]
                ]
            );
            $ratings[] = ['rating' => $rating, 'idx' => $idx];
            print $idx.': '.$c->getDisplay() . " rating $rating (potential points * ";
            print $probabilityOfSomeoneVoidInSuit[$suit] . ' * ' . $unplayedCardsLower . ' / ' . count($unplayedCards[$suit]) . ")\n";
        }

        foreach ($ratings as $arr1) {
            $rating = $arr1['rating'];
            $insertIndex = 0;
            foreach ($sortedRatings as $localIdx => $arr2) {
                if ($rating >= $arr2['rating']) {
                    $insertIndex = $localIdx + 1;
                }
            }
            array_splice($sortedRatings, $insertIndex, 0, [$arr1]);
        }

        foreach ($sortedRatings as $arr) {
            $ret[] = $arr['idx'];
        }
var_dump(json_encode($ret));
        return $ret;
    }

    protected static function getProbabilityOfSomeoneVoidInSuit($numUnplayed, $numUnplayedThisSuit)
    {
        $thoseWithThatSuit = 2 / 3 * $numUnplayed; // there are three other players, so 2/3 of the cards will contain all those cards of the suit
// print "numUnplayed $numUnplayed numUnplayedThisSuit $numUnplayedThisSuit thoseWithThatSuit $thoseWithThatSuit\n";
        $num = 1;
        $denom = 1;
        for ($i = 0; $i < $numUnplayedThisSuit; $i++) {
            $num *= ($thoseWithThatSuit - $i);
            $denom *= ($numUnplayed - $i);
        }
        if ($denom <= 0) {
            return 1;
        }
// print "get prob of a particular player void in this suit: ".round($num / $denom, 3)." ($num $denom $numUnplayed $numUnplayedThisSuit)\n";
        $p1 = ($num / $denom);
        return 1 - pow(1 - $p1, 3);
    }

    protected static function calculateExpectedPoints($suit, $value, $data)
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

    public static function mostDangerous($cards, $n = null)
    {
        uasort(
            $cards,
            function($a, $b) {
                return -1*($a->getDanger() <=> $b->getDanger());
            }
        );
print "gonna return ".json_encode(array_keys($cards))."\n";
        if (!$n) {
            return array_keys($cards);
        }

        return array_slice(array_keys($cards), 0, $n);
    }

    protected static function getCardsRemaining($cardsPlayedThisRound, $cardsPlayedThisTrick, $myCards)
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

    protected static function rateDanger($data, $singleSuit = false)
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
        $unplayedCards = self::getCardsRemaining($cardsPlayedThisRound, $cardsPlayedThisTrick, $allCards);

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
