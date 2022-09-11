<?php

namespace AppBundle\Cards;

class Player extends BaseProcess {
	protected $hand;
	protected $name;
	protected $cardPlayed = '';

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
		$this->writeln($this->name . ': ' . $this->cardPlayed);
		$this->hand->show();
	}

	public function playCard()
	{
		$cardToPlayIdx = rand(0, $this->hand->getCardCount() - 1);
		return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx)->display();
	}

	public function hasCards()
	{
		return $this->hand->hasCards();
	}

	public function hasCard($cardIdx)
	{
		return $this->hand->hasCard($cardIdx);
	}
}
