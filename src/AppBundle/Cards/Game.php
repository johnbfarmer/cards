<?php

namespace AppBundle\Cards;

class Game extends BaseProcess {
	protected $numberOfPlayers = 4;
	protected $numberOfCardsToDeal = 13;
	protected $maxRounds = 16;
	protected $maxScore = 100;
	protected $players;
	protected $round;
	protected $roundCount = 1;
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

	public function play()
	{
		while ($this->roundCount++ <= $this->maxRounds) {
			$this->writeln('Round '.($this->roundCount - 1));
			$this->round = new Round([
				'numberOfPlayers' => $this->numberOfPlayers,
				'numberOfCardsToDeal' => $this->numberOfCardsToDeal,
				'players' => $this->players,
				'isPassRound' => $this->isPassRound,
			]);

			$this->round->start();

			while ($this->round->play());

			if ($this->round->getMaxScore() >= $this->maxScore) {
				break;
			}
		}
	}

	public function endGame()
	{
		$this->gameOver = true;
	}
}
