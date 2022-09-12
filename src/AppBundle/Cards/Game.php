<?php

namespace AppBundle\Cards;

class Game extends BaseProcess {
	protected $numberOfPlayers = 4;
	protected $numberOfCardsToDeal = 13;
	protected $players;
	protected $round;
	protected $gameOver = false;
	protected $isPassRound = true;

	public function __construct($params)
	{
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
		// tbi allow for multiple rounds
		$this->round = new Round([
			'numberOfPlayers' => $this->numberOfPlayers,
			'numberOfCardsToDeal' => $this->numberOfCardsToDeal,
			'players' => $this->players,
			'isPassRound' => $this->isPassRound,
		]);

		$this->round->start();
	}

	public function play()
	{
		return $this->round->play();
	}

	public function endGame()
	{
		$this->gameOver = true;
	}
}
