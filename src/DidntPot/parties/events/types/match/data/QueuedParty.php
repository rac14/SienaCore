<?php

namespace DidntPot\parties\events\types\match\data;

use DidntPot\parties\PracticeParty;
use JetBrains\PhpStorm\Pure;

class QueuedParty
{
    /* @var string */
    private string $queue;

    /* @var PracticeParty */
    private PracticeParty $party;

    /* @var int */
    private int $size;

    #[Pure] public function __construct(PracticeParty $party, string $queue)
    {
        $this->queue = $queue;
        $this->party = $party;
        $this->size = $party->getPlayers(true);
    }

    /**
     * @return PracticeParty
     */
    public function getParty(): PracticeParty
    {
        return $this->party;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
}