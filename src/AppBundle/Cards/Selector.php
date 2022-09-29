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

    public function __construct()
    {
        
    }

    public static function selectCard($eligibleCards)
    {
        if (count($eligibleCards) === 1) {
            return 0;
        }

        $singleSuit = true;
        $suit = null;
        foreach($eligibleCards as $idx => $c) {
            if (is_null($suit)) {
                $suit = $c->getSuit();
                $eligibleIdx = $idx; // we may need this yet
            }
            if ($c->getSuit() !== $suit) {
                $singleSuit = false;
                break;
            }
        }
        if ($singleSuit) {
            return 0;//$eligibleIdx;
        }

        return rand(0, count($eligibleCards) - 1);
    }

    public static function selectCardsToPass($cards)
    {
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
