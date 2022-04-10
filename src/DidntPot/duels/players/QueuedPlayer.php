<?php

namespace DidntPot\duels\players;

use pocketmine\Player;

class QueuedPlayer
{
    /* @var string */
    private $queue;

    /* @var bool */
    private $ranked;

    /* @var Player */
    private $player;

    /* @var bool */
    private $peOnly;

    public function __construct(Player $player, string $queue, bool $ranked = false)
    {
        $this->ranked = $ranked;
        $this->queue = $queue;
        $this->player = $player;
        // TODO:
        $this->peOnly = false;
    }

    /**
     * @return bool
     */
    public function isPeOnly(): bool
    {
        return $this->peOnly;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return bool
     */
    public function isRanked(): bool
    {
        return $this->ranked;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }
}