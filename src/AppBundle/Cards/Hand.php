<?php

namespace AppBundle\Cards;

class Hand extends BaseProcess {
	protected $cards = [];

	public function __construct($cards)
	{
		$this->cards = $cards;
		$this->sort();
	}

	public function sort()
	{
		usort(
			$this->cards, 
			function($a, $b) {
                return $a->getSortOrder() <=> $b->getSortOrder();
            }
        );
	}

	public function show()
	{
		$str = '';
		foreach ($this->cards as $c) {
			$str .= $c->getDisplay() . ' ';
		}

		$this->writeln("$str\n");
	}

	public function remove($idx)
	{
		unset($this->cards[$idx]);
	}

	public function getCard($idx)
	{
		$card = $this->cards[$idx];
		unset($this->cards[$idx]);
		$this->cards = array_values($this->cards);
		return $card;
	}

	public function getCardCount()
	{
		return count($this->cards);
	}

	public function hasCard($cardIdx)
	{
		foreach($this->cards as $card) {
			if ($card->getIdx() == $cardIdx) {
				return true;
			}
		}

		return false;
	}

	public function hasCards()
	{
		return !empty($this->cards);
	}

	public function getEligibleCards($suit, $isFirstTrick)
	{
		$eligible = [];
		foreach($this->cards as $idx => $card) {
			if ($card->getSuit() == $suit) {
				$eligible[$idx] = $card;
			}
		}

		if (empty($eligible)) {
			if ($isFirstTrick) {
				$cards = $this->cards;
				$eligible = [];
				foreach($cards as $c) {
					if ($c->getSuit() === 2 || ($c->getSuit() === 2 && $c->getValue() === 10)) {
						continue;
					}
					$eligible[] = $c;
				}
				return $eligible;
			}

			return $this->cards;
		}

		return $eligible;
	}

	public function getEligibleLeadCards($isBrokenHearts)
	{
		$eligible = [];
		foreach($this->cards as $idx => $card) {
			if ($isBrokenHearts || $card->getSuit() !== 2) {
				$eligible[$idx] = $card;
			}
		}

		if (empty($eligible)) {
			return $this->cards;
		}

		return $eligible;
	}
}
