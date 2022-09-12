<?php

namespace AppBundle\Cards;

class Trick extends BaseProcess {
	protected $players;
	protected $numberOfPlayers;
	protected $leadPlayer;
	protected $isBrokenHearts;
	protected $roundOver = false;
	protected $cardsPlayed = [];

	public function __construct($players, $numberOfPlayers, $leadPlayer, $isBrokenHearts)
	{
		$this->players = $players;
		$this->numberOfPlayers = $numberOfPlayers;
		$this->leadPlayer = $leadPlayer;
		$this->isBrokenHearts = $isBrokenHearts;
	}

	protected function getPlayerOrder()
	{
		$order = [];
		$i = $this->leadPlayer;
		while (count($order) < $this->numberOfPlayers) {
			$order[] = $i;
			$i = ($i + 1) % $this->numberOfPlayers; 
		}

		return $order;
	}

	public function play()
	{
		$this->writeln('------');
		$this->writeln('');

		foreach ($this->getPlayerOrder() as $i) {
			$this->cardsPlayed[] = $this->players[$i]->playCard($this->cardsPlayed, $this->isBrokenHearts);
			$this->players[$i]->showHand();
			if (!$this->players[$i]->hasCards()) {
				$this->endRound();
			}
		}

		return $this->roundOver;
	}

	public function show()
	{
		$s = 'Cards Played: ';
		foreach($this->cardsPlayed as $c) {
			$s .= $c->getDisplay(). ' ';
		}
		$this->writeln($s);
		$this->writeln('');
	}

	public function getCardsPlayed()
	{
		return $this->cardsPlayed;
	}

	public function endRound()
	{
		$this->roundOver = true;
	}

	public function getPlayers()
	{
		return $this->players;
	}

	public function getleadPlayer()
	{
		return $this->leadPlayer;
	}

	public function getRoundOver()
	{
		return $this->roundOver;
	}
}
