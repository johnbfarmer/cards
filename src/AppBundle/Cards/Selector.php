<?php

namespace AppBundle\Cards;

class Selector extends BaseProcess {
    protected $hand;
    protected $trick;
    protected $round;
    protected $attemptToShootTheMoon = false;
    protected $scores = [];
    protected $strategyRound = 'avoidPoints';
    protected $strategyTrick = 'highestNoTake';
    const STRATEGIES_ROUND = ['avoidPoints', 'shootTheMoon', 'blockShootTheMoon', 'protectOther', 'attackOther'];
    const STRATEGIES_TRICK = ['highestNoTake', 'takeHigh', 'takeLow', 'dumpHigh', 'dumpLow'];

    public static function selectCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        $handStrategy = $data['handStrategy'];
        if (count($eligibleCards) === 1) {
            return 0;
        }

        $allEligibleAreSameSuit = true;
        $suit = null;
        foreach($eligibleCards as $idx => $c) {
            if (is_null($suit)) {
                $suit = $c->getSuit();
                $eligibleIdx = $idx; // tbi
            }
            if ($c->getSuit() !== $suit) {
                $allEligibleAreSameSuit = false;
                break;
            }
        }
        if ($allEligibleAreSameSuit) {
            if ($data['isFirstTrick']) {
                return $handStrategy === 'shootTheMoon' ? 0 : count($eligibleCards) - 1; // assuming you're not shooting the moon, throw the highest club
            }
            return 0;//$eligibleIdx;
        }

        return rand(0, count($eligibleCards) - 1);
    }

    public static function selectLeadCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        if (count($eligibleCards) === 1) {
            return 0;
        }

        $sortedEligibleCards = self::mostDangerous($eligibleCards, count($eligibleCards));

        switch ($data['handStrategy']) {
            case self::STRATEGIES_ROUND[0]:
                return array_keys($sortedEligibleCards)[0];
                return array_keys($sortedEligibleCards)[count($sortedEligibleCards) - 1];
            default:
                return rand(0, count($eligibleCards) - 1);
        }

    }

    // return hand indices in reverse order of 3 cards to pass
    public static function selectCardsToPass($data)
    {
        $cards = $data['hand'];
        $scores = $data['scores'];
        $strategy = $data['strategy']; // self::getRoundStrategy($cards, $scores);

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

    public static function mostDangerous($cards, $n)
    {
        uasort(
            $cards, 
            function($a, $b) {
                return -1*($a->getDanger() <=> $b->getDanger());
            }
        );

        return array_slice(array_keys($cards), 0, $n);
    }
}
