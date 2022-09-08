<?php

namespace AppBundle\Cards;

class Game extends BaseProcess {
	protected $numberOfPlayers = 4;
	protected $numberOfCardsToDeal = 13;
	protected $deck;
	protected $players;
	protected $gameOver = false;

	public function __construct($params)
	{
		$this->deck = new Deck();
		$this->numberOfPlayers = $params['players'];
		$this->numberOfCardsToDeal = $params['cards'];
		$this->createPlayers();
	}

	public function createPlayers()
	{
		for ($i = 1; $i <= $this->numberOfPlayers; $i++) {
			$this->players[] = new Player($i);
		}
	}

	public function start()
	{
		for ($i = 0; $i < $this->numberOfPlayers; $i++) {
			$hand = new Hand($this->deck->deal($this->numberOfCardsToDeal));
			$this->players[$i]->addHand($hand);
			$this->players[$i]->showHand();
		}
	}

	public function play()
	{
		for ($i = 0; $i < $this->numberOfPlayers; $i++) {
			$this->players[$i]->playCard();
			$this->players[$i]->showHand();
			if (!$this->players[$i]->hasCards()) {
				$this->endGame();
			}
		}

		return !$this->gameOver;
	}

	public function endGame()
	{
		$this->gameOver = true;
	}
}
