<?php

namespace DidntPot\parties\events;

use pocketmine\Player;

abstract class PartyEvent
{
    /** @var string */
    public const EVENT_TOURNAMENT = 'event.tournament';

    /** @var string */
    public const EVENT_PARTY_VS_PARTY = 'event.party-vs-party';

    /** @var string */
    public const EVENT_DUEL = 'event.duel';

    /* @var string */
    protected string $eventType;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->eventType = $type;
    }

    /**
     * @return string
     * Specifies which party event it is.
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Updates the party event each tick.
     */
    abstract public function update(): void;

    /**
     * @param Player $player
     */
    abstract public function removeFromEvent(Player $player): void;
}