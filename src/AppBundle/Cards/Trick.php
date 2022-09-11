<?php

namespace AppBundle\Cards;

class Trick extends BaseProcess {
	protected $players;
	protected $numberOfPlayers;
	protected $leadPlayer;
	protected $gameOver = false;
	protected $cardsPlayed = [];

	public function __construct($players, $numberOfPlayers, $leadPlayer)
	{
		$this->players = $players;
		$this->numberOfPlayers = $numberOfPlayers;
		$this->leadPlayer = $leadPlayer;
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
			$this->cardsPlayed[] = $this->players[$i]->playCard();
			$this->players[$i]->showHand();
			if (!$this->players[$i]->hasCards()) {
				$this->endGame();
			}
		}

		return $this->gameOver;
	}

	public function showCardsPlayed()
	{
		$this->writeln('Cards Played: ' . implode(' ', $this->cardsPlayed));
		$this->writeln('');
		$this->writeln('');
		$this->writeln('');
	}

	public function getCardsPlayed()
	{
		return $this->cardsPlayed;
	}

	public function endGame()
	{
		$this->gameOver = true;
	}

	public function getPlayers()
	{
		return $this->players;
	}

	public function getleadPlayer()
	{
		return $this->leadPlayer;
	}

	public function getGameOver()
	{
		return $this->gameOver;
	}
}
