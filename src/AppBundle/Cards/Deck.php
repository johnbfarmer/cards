<?php

namespace AppBundle\Cards;

class Deck extends BaseProcess {
	protected $cards = [],
	$deck,
	$card,
	$suits = ['♣', '♦', '♥', '♠'],
	$faces = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];

	public function __construct($parameters = [])
	{
		for ($i = 0; $i < 52; $i++) {
			$this->cards[] = new Card($i);
		}
		$this->shuffle();
	}

	public function shuffle()
	{
		$this->deck = $this->cards;
	}

	protected function draw()
	{
		$cardIdx = rand(0, count($this->deck) - 1);
		$this->card = array_splice($this->deck, $cardIdx, 1)[0];
	}

	public function deal($num = 1)
	{
		$hand = [];
		for ($i = 1; $i <= $num; $i++) {
			$this->draw();
			$hand[] = $this->card;
		}
		return $hand;
	}
}
