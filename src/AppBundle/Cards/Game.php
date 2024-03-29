<?php

namespace AppBundle\Cards;

class Game extends BaseProcess {
    protected $numberOfPlayers = 4;
    protected $numberOfCardsToDeal = 13;
    protected $maxRounds = 30;
    protected $maxScore = 100;
    protected $scores = [];
    protected $players = [];
    protected $round;
    protected $roundCount = 1;
    protected $gameOver = false;
    protected $winner = 'nobody';
    protected $playerData = [
        ['name' => 'Dilbert', 'riskTolerance' => 0.25],
        ['name' => 'Ululua', 'riskTolerance' => 0.3],
        ['name' => 'Sally', 'riskTolerance' => 0.2],
        ['name' => 'Smelch', 'riskTolerance' => 0.35],
    ];

    public function __construct($params)
    {
        $this->numberOfPlayers = $params['players'];
        $this->numberOfCardsToDeal = $params['cards'];
        $this->createPlayers();
    }

    public function createPlayers()
    {
        for ($i = 1; $i <= $this->numberOfPlayers; $i++) {
            $this->players[$i] = new Player($i, $this->playerData[$i-1]);
            $this->scores[$i] = 0;
        }
    }

    public function play()
    {
        while ($this->roundCount++ <= $this->maxRounds) {
            $this->writeln('');
            $this->writeln('ROUND '.($this->roundCount - 1));
            $this->writeln('');
            $this->round = new Round([
                'numberOfPlayers' => $this->numberOfPlayers,
                'numberOfCardsToDeal' => $this->numberOfCardsToDeal,
                'players' => $this->players,
                'scores' => $this->scores,
                'roundCount' => $this->roundCount - 1,
            ]);

            $this->round->start();

            while ($this->round->play());

            $this->scores = $this->round->getScores();
            $this->round->report();

            if ($this->checkGameOver()) {
                $this->endGame();
                break;
            }
        }
    }

    public function checkGameOver()
    {
        $maxScore = 0;
        $minScore = 1000;
        $winners = [];

        foreach($this->scores as $score) {
            if ($score > $maxScore) {
                $maxScore = $score;
            }
            if ($score < $minScore) {
                $minScore = $score;
            }
        }

        if ($maxScore >= $this->maxScore) {
            foreach ($this->scores as $playerId => $score) {
                if ($score === $minScore) {
                    $winners[] = $this->players[$playerId]->getName();
                }
            }

            if (count($winners) === 1) {
                $this->winner = $winners[0];
                return true;
            }
        }

        return false;
    }

    public function endGame()
    {
        $this->writeln("\nWinner: " . $this->winner);
        $this->gameOver = true;
    }
}
