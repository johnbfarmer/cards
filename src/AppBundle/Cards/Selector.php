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
    const STRATEGIES_TRICK = ['highestNoTake', 'takeHigh', 'takeLow', 'dumpLow'];

    public static function selectCard($data)
    {
        $eligibleCards = $data['eligibleCards'];
        if (count($eligibleCards) === 1) {
            return 0;
        }

        $singleSuit = true;
        $suit = null;
        foreach($eligibleCards as $idx => $c) {
            if (is_null($suit)) {
                $suit = $c->getSuit();
                $eligibleIdx = $idx; // tbi
            }
            if ($c->getSuit() !== $suit) {
                $singleSuit = false;
                break;
            }
        }
        if ($singleSuit) {
            if ($data['isFirstTrick']) {
                return count($eligibleCards) - 1; // assuming you're not shooting the moon, throw the highest club
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

    public static function selectCardsToPass($data)
    {
        $cards = $data['hand'];
        $strategy = self::getStrategy($cards);

        switch ($strategy) {
            default:
                $ret = self::mostDangerous($cards, 3);
                arsort($ret); // if we don't reverse sort, as we pluck out the cards, the indexes will be wrong.
                return $ret;
        }
    }

    public static function getStrategy($cards)
    {
        return self::STRATEGIES_ROUND[0];
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
