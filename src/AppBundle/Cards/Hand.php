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
		usort($this->cards, function($a, $b) {
                return $a->getSortOrder() <=> $b->getSortOrder();
            }
        );
	}

	public function show()
	{
		$str = '';
		foreach ($this->cards as $c) {
			$str .= $c->display() . ' ';
		}

		$this->writeln("$str\n");
	}

	public function remove()
	{
		array_pop($this->cards);
	}

	public function hasCards()
	{
		return !empty($this->cards);
	}
}
