<?php

namespace AppBundle\Cards;

class Round extends BaseProcess {
	protected $numberOfPlayers = 4;
	protected $numberOfCardsToDeal = 13;
	protected $isStarting = true;
	protected $deck;
	protected $players;
	protected $roundOver = false;
	protected $isPassRound = true;
	protected $leadPlayer = 0;
	protected $isBrokenHearts = false;

	public function __construct($params)
	{
		$this->deck = new Deck();
		$this->numberOfPlayers = $params['numberOfPlayers'];
		$this->numberOfCardsToDeal = $params['numberOfCardsToDeal'];
		$this->players = $params['players'];
		$this->isPassRound = $params['isPassRound'];
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

		$trick = new Trick([
			'players' => $this->players,
			'numberOfPlayers' => $this->numberOfPlayers,
			'leadPlayer' => $this->leadPlayer,
			'isBrokenHearts' => $this->isBrokenHearts,
			'isFirstTrick' => $this->isStarting,
		]);
		$trick->play();
		$this->handleTrickResult($trick);
		$this->isStarting = false;

		$isOver = $trick->getRoundOver();
		if ($isOver) {
			$this->report();
		}
		return !$isOver;
	}

	public function report()
	{
		$this->writeln('Score: ');
		foreach($this->players as $player) {
			$player->report();
		}
	}

	public function getMaxScore()
	{
		$maxScore = 0;
		foreach($this->players as $player) {
			$score = $player->getScore();
			if ($score > $maxScore) {
				$maxScore = $score;
			}
		}

		return $maxScore;
	}

	protected function handleTrickResult($trick)
	{
		$trick->show();
		$this->players = $trick->getPlayers();
		$cards = $trick->getCardsPlayed();
		$leadSuit = $cards[0]->getSuit();
		$topValue = $cards[0]->getValue();
		$takesTrick = $this->leadPlayer;
		$points = 0;
		foreach($cards as $idx => $card) {
			$suit = $card->getSuit();
			$value = $card->getValue();
			if ($suit === 2) {
				$this->isBrokenHearts = true;
				$points++;
			}
			if ($suit === 3 && $value == 10) {
				$points += 13;
			}
			if ($suit === $leadSuit && $value > $topValue) {
				$topValue = $value;
				$takesTrick = ($this->leadPlayer + $idx) % $this->numberOfPlayers;
			}
		}

		$this->players[$takesTrick]->addPoints($points);
		$this->leadPlayer = $takesTrick;
		$this->writeln($points . ' for P' . $takesTrick);
	}

	protected function passCards()
	{
		// tbi
		$this->isPassRound = false;
		return true;
	}
}
