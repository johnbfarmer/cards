<?php

namespace AppBundle\Cards;

class Trick extends BaseProcess {
    protected $players;
    protected $numberOfPlayers;
    protected $leadPlayer;
    protected $isBrokenHearts;
    protected $roundOver = false;
    protected $cardsPlayed = [];

    public function __construct($params)
    {
        $this->players = $params['players'];
        $this->numberOfPlayers = $params['numberOfPlayers'];
        $this->leadPlayer = $params['leadPlayer'];
        $this->isBrokenHearts = $params['isBrokenHearts'];
        $this->isFirstTrick = $params['isFirstTrick'];
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
            $this->cardsPlayed[] = $this->players[$i]->playCard($this->cardsPlayed, $this->isBrokenHearts, $this->isFirstTrick);
            $this->players[$i]->showHand();
            if (!$this->players[$i]->hasCards()) {
                $this->endRound();
            }
        }

        foreach($this->players as $p) {
            $p->gatherInfo(['cardsPlayed' => $this->cardsPlayed]);
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
