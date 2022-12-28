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
            // print $probabilityOfSomeoneVoidInSuit[$suit] . ' * ' . $unplayedCardsHigher . ' / ' . count($unplayedCards[$suit]) . ") - .001 * $value\n";
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
$this->writeln("orderered: ".json_encode($ret));
        return $ret;
    }
}