<?php

namespace AppBundle\Cards;

class Player extends BaseProcess {
	protected $hand;
	protected $name;
	protected $score = 0;
	protected $cardPlayed;

	public function __construct($id, $name = null)
	{
		if (is_null($name)) {
			$name = 'Player ' . $id;
		}

		$this->name = $name;
	}

	public function addHand($hand)
	{
		$this->hand = $hand;
	}

	public function showHand()
	{
		$s = $this->name . ':';
		if ($this->cardPlayed) {
			$s .= ' ' . $this->cardPlayed->getDisplay();
		}
		$this->writeln($s);
		$this->hand->show();
	}

	public function report()
	{
		$this->writeln($this->name . ': ' . $this->score);
	}

	public function playCard($cardsPlayed, $isBrokenHearts, $isFirstTrick)
	{
		if (empty($cardsPlayed)) {
			if ($isFirstTrick && $this->hasCard(0)) {
				$cardToPlayIdx = 0;
			} else {
				$eligibleCards = $this->hand->getEligibleLeadCards($isBrokenHearts);
				$eligibleIdx = $this->selectCard($eligibleCards);
				$cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
			}
		} else {
			$suit = $cardsPlayed[0]->getSuit();
			$eligibleCards = $this->hand->getEligibleCards($suit, $isFirstTrick);
			$eligibleIdx = $this->selectCard($eligibleCards);
			$cardToPlayIdx = array_keys($eligibleCards)[$eligibleIdx];
		}

		return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx);
	}

	protected function selectCard($eligibleCards)
	{
		return rand(0, count($eligibleCards) - 1);
	}

	public function hasCards()
	{
		return $this->hand->hasCards();
	}

	public function hasCard($cardIdx)
	{
		return $this->hand->hasCard($cardIdx);
	}

	public function addPoints($pts)
	{
		$this->score += $pts;
	}

	public function getScore()
	{
		return $this->score;
	}
}
