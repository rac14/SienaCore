<?php

namespace DidntPot\events;

use DidntPot\arenas\EventArena;
use DidntPot\kits\Kits;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;

class EventManager
{
    const EVENT_TYPES = [
        Kits::SUMO => PracticeEvent::TYPE_SUMO,
        Kits::GAPPLE => PracticeEvent::TYPE_GAPPLE,
        Kits::NODEBUFF => PracticeEvent::TYPE_NODEBUFF
    ];

    /** @var PracticeEvent[]|array */
    private $events;

    /** @var PracticeCore */
    private $core;

    /** @var Server */
    private $server;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();
        $this->events = [];
        $this->initEvents();
    }

    /**
     * Initializes the events to the event handler.
     */
    private function initEvents(): void
    {
        $arenas = PracticeCore::getArenaManager()->getEventArenas();

        foreach($arenas as $arena)
        {
            $this->createEvent($arena);
        }
    }

    /**
     * @param EventArena $arena
     *
     * Creates a new event from an arena.
     */
    public function createEvent(EventArena $arena): void
    {
        $type = $arena->getKit()->getLocalizedName();

        if(isset(self::EVENT_TYPES[$type]))
        {
            $type = self::EVENT_TYPES[$type];
            $this->events[] = new PracticeEvent($type, $arena);
        }
    }

    /**
     * @param string $name
     *
     * Removes an event based on the arena name.
     */
    public function removeEventFromArena(string $name): void
    {
        foreach($this->events as $key => $event)
        {
            if($event->getArena()->getName() === $name)
            {
                unset($this->events[$key]);
                return;
            }
        }
    }

    /**
     * Updates the events.
     */
    public function update(): void
    {
        foreach($this->events as $event)
        {
            $event->update();
        }
    }

    /**
     * @return array Gets the events.
     *
     * Gets the events.
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param int $index
     * @return PracticeEvent|null
     *
     * Gets the event based on the name.
     */
    public function getEventFromIndex(int $index): ?PracticeEvent
    {
        if(isset($this->events[$index]))
        {
            return $this->events[$index];
        }

        return null;
    }

    /**
     * @param Player $player
     * @return PracticeEvent|null
     */
    #[Pure] public function getEventFromPlayer(Player $player): ?PracticeEvent
    {
        foreach($this->events as $event)
        {
            if($event->isPlayer($player))
            {
                return $event;
            }
        }

        return null;
    }
}