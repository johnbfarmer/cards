<?php

namespace AppBundle\Cards;

class Game extends BaseProcess {
	protected $numberOfPlayers = 4;
	protected $numberOfCardsToDeal = 13;
	protected $deck;
	protected $players;
	protected $gameOver = false;
	protected $isPassRound = true;
	protected $leadPlayer = 0;

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

		foreach ($this->players as $i => $p) {
			if ($p->hasCard(0)) {
				$this->leadPlayer = $i;
			}
		}
	}

	public function play()
	{
		if ($this->isPassRound) {
			return $this->passCards();
		}

		$trick = new Trick($this->players, $this->numberOfPlayers, $this->leadPlayer);
		$trick->play();
		$trick->showCardsPlayed();
		$this->players = $trick->getPlayers();
		$this->leadPlayer = $trick->getLeadPlayer();

		return !$trick->getGameOver();
	}

	protected function passCards()
	{
		// tbi
		$this->isPassRound = false;
		return true;
	}

	public function endGame()
	{
		$this->gameOver = true;
	}
}
