<?php

namespace AppBundle\Cards;

class Selector extends BaseProcess {
	protected $hand;
	protected $trick;
	protected $round;
	protected $attemptToShootTheMoon = false;
	protected $scores = [];

	public function __construct()
	{
		
	}

	public function selectCard($eligibleCards)
	{
// var_dump(array_keys($eligibleCards));
		if (count($eligibleCards) === 1) {
			return 0;//array_keys($eligibleCards)[0];
		}
		// pick the lowest one if all one suit
		$singleSuit = true;
		$suit = null;
		foreach($eligibleCards as $idx => $c) {
			if (is_null($suit)) {
				$suit = $c->getSuit();
				$eligibleIdx = $idx;
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
}
