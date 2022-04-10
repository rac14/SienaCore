<?php

namespace DidntPot\duels\requests;

use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class DuelRequest
{
    /* @var Player */
    private $from;

    /* @var Player */
    private $to;

    /* @var string */
    private $queue;

    /* @var bool|null */
    private $ranked;

    /* @var string */
    private $fromName;

    /* @var string */
    private $toName;

    /* @var string */
    private $texture;

    /* @var string */
    private $toDisplayName;

    /* @var string */
    private $fromDisplayName;

    /** @var string */
    private $duelGenerator;

    #[Pure] public function __construct(Player $from, Player $to, string $queue, bool $ranked, string $duelGenerator)
    {
        $this->from = $from;
        $this->to = $to;
        $this->queue = $queue;
        $this->ranked = $ranked;
        $this->toName = $to->getName();
        $this->fromName = $from->getName();
        $this->toDisplayName = $to->getDisplayName();
        $this->fromDisplayName = $from->getDisplayName();
        $kit = PracticeCore::getKits()->getKit($queue);
        $this->texture = ($kit !== null) ? $kit->getTexture() : '';
        $this->duelGenerator = $duelGenerator;
    }

    /**
     * @return string
     *
     * Gets the duel generator name.
     */
    public function getGeneratorName(): string
    {
        return $this->duelGenerator;
    }


    /**
     * @return string
     */
    public function getTexture(): string
    {
        return $this->texture;
    }

    /**
     * @return Player
     */
    public function getFrom(): Player
    {
        return $this->from;
    }

    /**
     * @return Player
     */
    public function getTo(): Player
    {
        return $this->to;
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

    /**
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @return string
     */
    public function getToName(): string
    {
        return $this->toName;
    }


    /**
     * @return string
     */
    public function getFromDisplayName(): string
    {
        return $this->fromDisplayName;
    }

    /**
     * @return string
     */
    public function getToDisplayName(): string
    {
        return $this->toDisplayName;
    }
}