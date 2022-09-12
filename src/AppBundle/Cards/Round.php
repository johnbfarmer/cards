<?php

namespace AppBundle\Cards;

class Round extends BaseProcess {
	protected $numberOfPlayers = 4;
	protected $numberOfCardsToDeal = 13;
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
$this->writeln($this->numberOfPlayers);
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

		$trick = new Trick($this->players, $this->numberOfPlayers, $this->leadPlayer, $this->isBrokenHearts);
		$trick->play();
		$this->handleTrickResult($trick);

		return !$trick->getRoundOver();
	}

	protected function handleTrickResult($trick)
	{
		$trick->show();
		$this->players = $trick->getPlayers();
		$cards = $trick->getCardsPlayed();
		$leadSuit = $cards[0]->getSuit();
		$topValue = $cards[0]->getValue();
		$takesTrick = $this->leadPlayer;
		foreach($cards as $idx => $card) {
			$suit = $card->getSuit();
			$value = $card->getValue();
			if ($suit === 2) {
				$this->isBrokenHearts = true;
			}
			if ($suit === $leadSuit && $value > $topValue) {
				$topValue = $value;
				$takesTrick = ($this->leadPlayer + $idx) % $this->numberOfPlayers;
			}
		}

		$this->leadPlayer = $takesTrick;
	}

	protected function passCards()
	{
		// tbi
		$this->isPassRound = false;
		return true;
	}

	public function endGame()
	{
		$this->roundOver = true;
	}
}
