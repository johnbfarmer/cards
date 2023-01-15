<?php

namespace AppBundle\Cards;

class BaseSelector extends BaseProcess {
    protected $importanceGivenToVoid = .25;
    protected $avpThreshold = 440; // if avp > this and stm < stmThreshold, go for it
    protected $stmThreshold = 150;
    protected $handAnalysis = ['AVP' => [[],[],[],[]], 'STM' => [[],[],[],[]]];

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
            $probabilityOfSomeoneVoidInSuit[$i] = $data['playersVoidInSuit'][$i] ? 1 : $this->getProbabilityOfOneVoidInSuit($numUnplayed, $numUnplayedThisSuit);
        }
        $suit = array_values($data['cardsPlayedThisTrick'])[0]->getSuit();
        $numberOfPlayersAfterMe = 3 - count($data['cardsPlayedThisTrick']);
        $probabilityOfPointsThisTrick = $data['isFirstTrick'] ? 0 : 1 - pow(1 - $probabilityOfSomeoneVoidInSuit[$suit], $numberOfPlayersAfterMe);
        $highestVal = 0;
        foreach ($data['cardsPlayedThisTrick'] as $c) {
            $value = $c->getValue();
            $s = $c->getSuit();
            if ($value > $highestVal && $s == $suit) {
                $highestVal = $value;
            }
            if ($s == 2 || ($s == 3 && $value == 10)) {
                $probabilityOfPointsThisTrick = 1;
            }
            if ($s == 3 && $value > 10 && !empty($unplayedCards[3][10])) {
                $probabilityOfPointsThisTrick = .5 * $numberOfPlayersAfterMe;
            }
        }
        $data['probabilityOfPointsThisTrick'] = $probabilityOfPointsThisTrick;
        $data['probabilityOfSomeoneVoidInSuit'] = $probabilityOfSomeoneVoidInSuit;
        $data['leadSuit'] = $suit;
        $data['highestVal'] = $highestVal;
        $data['numberOfPlayersAfterMe'] = $numberOfPlayersAfterMe;
        $ratingsMatrix = $this->calculateRatings($data, $unplayedCards);
// $this->writeln($ratingsMatrix);
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
// $this->writeln('new alg says pass');
// $this->writeln($this->selectCardsToPassNew()['AVP']);
return $this->selectCardsToPassNew()['AVP'];
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
$this->writeln('passing');
$this->writeln($indexes);
        return $indexes;
    }

    protected function selectCardsToPassNew()
    {
        $ret = [];
        foreach ($this->handAnalysis as $strategy => $arr) {
            $topThree = [];
            foreach ($arr as $idx => $danger) {
                $topThree[$idx] = $danger;
                arsort($topThree);
                $topThree = array_slice($topThree, 0, 3, true);
            }
            $idxs = array_keys($topThree);
            rsort($idxs);
            $ret[$strategy] = $idxs;
        }

        return $ret;
    }

    protected function selectCardsToPassForShootingTheMoon($cards, $all = false)
    {
        $this->writeln('new says pass if shooting');
        $this->writeln($this->selectCardsToPassNew()['STM']);
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

    public function getRoundStrategy($cards, $isHoldHand, $scores, $riskTolerance)
    {
        $this->analyzeHand($cards);
        if ($this->shouldShootTheMoon($cards, $isHoldHand, $scores, $riskTolerance)) {
            return 'shootTheMoon';
        }
        return 'avoidPoints';
    }

    protected function analyzeHand($cards)
    {
        $unplayedCards = $this->getCardsRemaining([], [], $cards);
        $myCardCounts = $this->countMyCardsBySuit($cards);
        $s = -1;
        foreach ($cards as $idx => $c) {
            if ($s != $c->getSuit()) {
                $s = $c->getSuit();
                $myCardsLower = 0;
                $ct = count($unplayedCards[$s]);
            }
            $v = $c->getValue();
            $unplayedCardsLower = 0;
            foreach($unplayedCards[$s] as $uc) {
                if ($uc < $v) {
                    $unplayedCardsLower++;
                } else {
                    break;
                }
            }
            $unplayedCardsHigher = $ct - $unplayedCardsLower;
            $myCardsHigher = $myCardCounts[$s] - $myCardsLower - 1;
            $avpDanger = $unplayedCardsLower * max($unplayedCardsLower - $myCardsLower, 0);
            $stmDanger = $unplayedCardsHigher * max($unplayedCardsHigher - $myCardsHigher, 0);
            if ($v == 0 && $s == 0) {
                $myCardsLower--; // 2 of clubs does not count as a myCardsLower
                $avpDanger = 0;
                $stmDanger = 0;
            }
            if ($s == 3 && $v >= 10) {
                $avpDanger *= 3;
            }
            if ($s == 3 && $v < 10) {
                $avpDanger = 0; // we love low spades for avp
            }
            if ($s == 2) {
                $stmDanger *= 8; // low hearts are deadly
            }
            $this->handAnalysis['AVP'][$idx] = $avpDanger;
            $this->handAnalysis['STM'][$idx] = $stmDanger;
            // $this->handAnalysis['AVP'][$s][$v] = $unplayedCardsLower * max($unplayedCardsLower - $myCardsLower, 0);
            // $this->handAnalysis['STM'][$s][$v] = $unplayedCardsHigher * max($unplayedCardsHigher - $myCardsHigher, 0);
            $myCardsLower++;
        }
$this->writeln($this->handAnalysis);
    }

    protected function combo($a, $b) {
        if ($a < $b) {
            return null; // TBI?
        }

        if ($b === $a || !$b) {
            return 1;
        }

        if ($b === $a - 1 || $b === 1) {
            return $a;
        }

        $x = max($b, $a-$b);
        $y = min($b, $a-$b);
        $num = $a; $denom = $y;
        $tmp = $denom;
        while (--$tmp) {$denom *= $tmp;}
        $tmp = $num;
        while (--$tmp > $x) {$num *= $tmp;}

        return $num/$denom;
    }

    protected function probabilityOfNumSuit($numUnknown, $numUnknownInSuit, $targetNumber) {
        if ($targetNumber > $numUnknownInSuit || $targetNumber < 0) {
            return 0;
        }
        $players = 3;
        $numCardsOtherPlayers = ($players - 1) / $players * $a;
        return combo($numUnknownInSuit, $targetNumber) * combo($numCardsOtherPlayers, $numUnknownInSuit-$targetNumber) / combo($numUnknown, $numUnknownInSuit);
    }

    public function shouldShootTheMoon($cards, $noPassing, $scores, $riskTolerance)
    {
        $riskSumAvp = 0;
        $riskSumStm = 0;
        foreach ($this->handAnalysis['AVP'] as $danger) {
            $riskSumAvp += $danger;
        }
        foreach ($this->handAnalysis['STM'] as $danger) {
            $riskSumStm += $danger;
        }
        if (!$noPassing) {
            $cardsToPass = $this->selectCardsToPassNew();
            foreach ($cardsToPass['AVP'] as $idx) {
                $riskSumAvp -= $this->handAnalysis['AVP'][$idx];
            }
            foreach ($cardsToPass['STM'] as $idx) {
                $riskSumStm -= $this->handAnalysis['STM'][$idx];
            }
        } else {
            $this->writeln("shouldShootTheMoon $riskTolerance, $riskSumAvp, $riskSumStm");
        }

        return ($riskSumAvp > $this->avpThreshold && $riskSumStm < $this->stmThreshold) || ($riskTolerance * $riskSumAvp > $riskSumStm);
    }

    protected function calculateRatings($data, $unplayedCards)
    {
        $h = [];
        $cards = $data['eligibleCards'];
        $myCardCounts = $this->countMyCardsBySuit($cards);
        $riskTolerance = $data['riskTolerance'];
        $numberOfPlayersAfterMe = $data['numberOfPlayersAfterMe'];
        $queenOfSpadesAtLarge = in_array(10, $unplayedCards[3]);
        foreach ($cards as $idx => $c) {
            $s = $c->getSuit();
            $v = $c->getValue();
            $isQueenOfSpades = $s == 3 && $v == 10;
            $takesQueenOfSpades = $s == 3 && $v > 10;
            $probabilityOfPointsThisTrick = $data['probabilityOfPointsThisTrick'];
            if ($s == 2 || $isQueenOfSpades) {
                $probabilityOfPointsThisTrick = 1;
            }
            if ($takesQueenOfSpades) {
                $probabilityOfPointsThisTrick = .5 * $numberOfPlayersAfterMe;
            }
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
            // justification: if leading, ct = 0 means nobody has this suit
$this->writeln("idx $idx $dspl");
            $probabilityOfTakingTrick = !$ct ? 1 : $unplayedCardsLower / $ct;
            if (!empty($data['leadSuit'])) {
// $this->writeln("JBF");
// $this->writeln(array_keys($data['cardsPlayedThisTrick']));
// $this->writeln($data['yetToPlay']);
// $this->writeln($data['playersVoidInSuit'][$s]);
// $this->writeln(array_values(array_intersect($data['yetToPlay'], $data['playersVoidInSuit'][$s])));
                // $stillToPlay = array_diff(array_diff([1,2,3,4], array_keys($data['cardsPlayedThisTrick'])), []);
                $isLeadSuit = $s == $data['leadSuit'];
                $takesTrick = $isLeadSuit && $v > $data['highestVal'];
                $yetToPlayAndVoid = array_values(array_intersect($data['yetToPlay'], $data['playersVoidInSuit'][$s]));
                $nonvoidYetToPlay = $numberOfPlayersAfterMe - count($yetToPlayAndVoid);
                $probabilityOtherTakesTrick = !$takesTrick ? 1 : (!$ct ? 0 : 1 - pow($unplayedCardsLower/$ct, $nonvoidYetToPlay));
                $probabilityOfTakingTrick = 1 - $probabilityOtherTakesTrick;
            }
            $probabilityOfSomeoneVoidInSuit = $data['probabilityOfSomeoneVoidInSuit'][$s];
// $this->writeln("unplayedCardsLower $unplayedCardsLower naturalIndex $naturalIndex shields $shields");
            if ($unplayedCardsLower - $naturalIndex++ - $shields < 1) {
                $shields++;
            }
            if ($ct) {
                $shields += ($ct - $unplayedCardsLower) / $ct;
                $shieldedness = !$unplayedCardsLower ? 0 : ($shields + $naturalIndex) / $unplayedCardsLower / $myCardCounts[$s];
            }
            $probabilityOfImprovingHand = !$ct ? 0 : (1 - $this->importanceGivenToVoid) * ($unplayedCardsLower / $ct) + $this->importanceGivenToVoid * ($myCardCounts[$s] < 2 ? 1 : 0);
            if ($isQueenOfSpades || $queenOfSpadesAtLarge && $takesQueenOfSpades) {
                $probabilityOfImprovingHand = 1;
            }
            $probabilityOfTakingPoints = $probabilityOfPointsThisTrick * $probabilityOfTakingTrick;
            $naturalIndex++;
            $rating = $riskTolerance * $probabilityOfImprovingHand + (1-$riskTolerance) * (1-$probabilityOfTakingPoints);
// $this->writeln("probabilityOfTakingPoints $probabilityOfTakingPoints");
// $this->writeln("probabilityOfTakingTrick $probabilityOfTakingTrick");
// $this->writeln("shieldedness $shieldedness");
$this->writeln("probabilityOfSomeoneVoidInSuit ".$data['probabilityOfSomeoneVoidInSuit'][$s]);
// $this->writeln("unplayedCardsHigher $unplayedCardsHigher");
$this->writeln("probabilityOfImprovingHand $probabilityOfImprovingHand");
$this->writeln("rating $rating ($riskTolerance * $probabilityOfImprovingHand + (".(1-$riskTolerance).") * (".(1-$probabilityOfTakingPoints)."))");
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

    protected function getProbabilityOfOneVoidInSuit($numUnplayed, $numUnplayedThisSuit)
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

        $p1 = round($num / $denom, 3);
$this->writeln("There is a ".(100 * $p1)."% chance that one player has none given $numUnplayedThisSuit | $numUnplayed");
        return $p1;
        // return 1 - pow(1 - $p1, 3);
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
            $unplayedCards[$suit] = array_values(array_diff($allCards[$suit], $playedCards[$suit]));
        }

        return $unplayedCards;
    }

    protected function rateHandBySuit($data, $unplayedCards, $suit)
    {
        // [], 2, 23, 234 perfect score for example
        // rate for normal and for STM here ?
        // score is shieldedness
        $cards = $data['eligibleCards'];
        $shields = 0;
        foreach ($cards as $idx => $c) {
            if ($c->getSuit() != $suit) {
                continue;
            }
            $v = $c->getValue();
            $dspl = $c->getDisplay();
            $void = $data['playersVoidInSuit'][$s];
            $naturalIndex = 0;
            $shieldedness = 0;
            $unplayedCardsLower = 0;
            foreach($unplayedCards[$s] as $uc) {
                if ($uc < $v) {
                    $unplayedCardsLower++;
                }
            }
            $ct = count($unplayedCards[$s]);
            $unplayedCardsHigher = $ct - $unplayedCardsLower;
            if ($unplayedCardsLower - $naturalIndex++ - $shields < 1) {
                $shields++;
            }
            if ($ct) {
                $shields += ($ct - $unplayedCardsLower) / $ct;
                $shieldedness = !$unplayedCardsLower ? 0 : ($shields + $naturalIndex) / $unplayedCardsLower / $myCardCounts[$s];
            }
        }
    }
}
