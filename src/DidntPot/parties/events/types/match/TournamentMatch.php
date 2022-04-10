<?php

namespace DidntPot\parties\events\types\match;

use DidntPot\parties\events\types\PartyTournament;
use pocketmine\Player;

class TournamentMatch
{
    /* @var int */
    private int $currentTick;

    /* @var PartyTournament */
    private PartyTournament $tournament;

    /* @var Player */
    private Player $player1;

    /* @var Player */
    private Player $player2;

    /* @var bool */
    private bool $started;

    /* @var bool */
    private bool $ended;

    /* @var bool */
    private bool $close;

    public function __construct(Player $p1, Player $p2, PartyTournament $tournament)
    {
        $this->tournament = $tournament;

        $this->player1 = $p1;
        $this->player2 = $p2;

        $this->currentTick = 0;

        $this->started = false;

        $this->ended = false;
    }

    public function update(): void
    {
        // TODO
    }

    /**
     * @return bool
     */
    public function canClose(): bool
    {
        return $this->close;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isPlayer(string $name): bool
    {
        $result = false;

        if ($this->player1 !== null)
            $result = $this->player1->getName() === $name;

        if ($this->player2 !== null)
            $result = $this->player2->getName() === $name;

        return $result;
    }

    /**
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->ended;
    }
}