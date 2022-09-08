<?php

namespace AppBundle\Cards;

class Player extends BaseProcess {
	protected $hand;
	protected $name;

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
		$this->writeln($this->name . ':');
		$this->hand->show();
	}

	public function playCard()
	{
		$this->hand->remove();
	}

	public function hasCards()
	{
		return $this->hand->hasCards();
	}
}
