<?php

namespace DidntPot\parties\events;

use DidntPot\kits\DefaultKits;
use DidntPot\parties\events\types\match\data\QueuedParty;
use DidntPot\parties\events\types\PartyDuel;
use DidntPot\parties\events\types\PartyVsParty;
use DidntPot\parties\PracticeParty;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use SplQueue;

class PartyEventManager
{
    /* @var QueuedParty[]|array */
    private array $queuedPartys;
    /* @var PartyEvent[]|array */
    private array $partyEvents;
    /** @var SplQueue */
    private SplQueue $inactivePartyEvents;
    /* @var Server */
    private Server $server;

    /* @var PracticeCore */
    private PracticeCore $core;

    #[Pure] public function __construct(PracticeCore $core)
    {
        $this->partyEvents = [];
        $this->inactivePartyEvents = new SplQueue();
        $this->queuedPartys = [];
        $this->core = $core;
        $this->server = $core->getServer();
    }

    /**
     * @param PracticeParty $party
     *
     * @return PartyEvent|null
     */
    #[Pure] public function getPartyEvent(PracticeParty $party): ?PartyEvent
    {
        $event = null;
        foreach ($this->partyEvents as $match) {
            if ($match->isParty($party))
                $event = $match;
        }

        return $event;
    }

    ////////////////////////////////////////////////////PARTY DUEL//////////////////////////////////////////////////////

    /**
     * @param PracticeParty $party
     * @param string $queue
     * @param int $size
     */
    public function placeInQueue(PracticeParty $party, string $queue, int $size): void
    {
        if ($party->getPlayers(true) < 2 || $party->getPlayers(true) < $size) {
            $owner = $party->getOwner();
            $msg = PracticeParty::getPrefix() . TextFormat::RED . "You need at have least 2 players to start party duel.";

            $owner->sendMessage($msg);
            return;
        } elseif ($party->getPlayers(true) > $size) {
            $owner = $party->getOwner();
            $msg = PracticeParty::getPrefix() . TextFormat::RED . "You can't have more than " . $size . " players for a party duel.";
            $owner->sendMessage($msg);
            return;
        }

        $local = strtolower($party->getName());
        if (isset($this->queuedPartys[$local])) {
            unset($this->queuedPartys[$local]);
        }

        $theQueue = new QueuedParty($party, $queue);
        $this->queuedPartys[$local] = $theQueue;

        DefaultKits::sendPartyQueueKit($party);

        $members = $party->getPlayers();

        foreach ($members as $member) {
            $msg = "\n§e§lParty " . $queue . "\n§r§e * Size: §6" . $party->getPlayers(true) . "\n§r  §7§oSearching for a match ...\n\n";
            $member->sendMessage($msg);
        }

        if (($matched = $this->findMatch($theQueue)) !== null && $matched instanceof QueuedParty) {
            $matchedLocal = strtolower($matched->getParty()->getName());
            unset($this->queuedPartys[$local], $this->queuedPartys[$matchedLocal]);
            $this->placeInDuel($party, $matched->getParty(), $queue);
        }
    }

    /**
     * @param QueuedParty $party
     *
     * @return QueuedParty|null
     */
    #[Pure] public function findMatch(QueuedParty $party): ?QueuedParty
    {
        $p = $party->getParty();

        foreach ($this->queuedPartys as $queue) {
            $queuedParty = $queue->getParty();

            $isMatch = false;

            if ($p->getName() === $queuedParty->getName() || $party->getSize() !== $queue->getSize()) {
                continue;
            }

            if ($party->getQueue() === $queue->getQueue()) {
                $isMatch = true;
            }

            if ($isMatch) {
                return $queue;
            }
        }

        return null;
    }

    /**
     * @param PracticeParty $p1
     * @param PracticeParty $p2
     * @param string $queue
     */
    public function placeInDuel(PracticeParty $p1, PracticeParty $p2, string $queue): void
    {
        $worldId = 0;

        $dataPath = $this->server->getDataPath() . '/worlds';

        while (isset($this->partyEvents[$worldId]) || is_dir($dataPath . '/party' . $worldId)) {
            $worldId++;
        }

        $kit = PracticeCore::getKitManager()->getKit($queue);
        $arena = PracticeCore::getArenaManager()->getDuelArena($kit->getName());

        $create = Utils::createLevel($worldId, $arena->getLevel()->getName(), "party");

        if ($create) {
            $this->partyEvents[$worldId] = new PartyDuel($worldId, $p1, $p2, $queue, $arena);

            $members1 = $p1->getPlayers();
            $members2 = $p2->getPlayers();

            $size = $p1->getPlayers(true);

            foreach ($members1 as $member) {
                $msg = PracticeParty::getPrefix() . "§e§lParty " . $queue . "§r\n§e * Party Owner: " . $p2->getName() . "\n§e * Party Size: " . $size;
                $member->sendMessage($msg);
            }

            foreach ($members2 as $member) {
                $msg = PracticeParty::getPrefix() . "§e§lParty " . $queue . "§r\n§e * Party Owner: " . $p1->getName() . "\n§e * Party Size: " . $size;
                $member->sendMessage($msg);
            }
        }
    }

    /**
     * @param string|PracticeParty $party
     *
     * @return bool
     */
    #[Pure] public function isInQueue(PracticeParty|string $party): bool
    {
        $name = $party instanceof PracticeParty ? $party->getName() : $party;
        return isset($this->queuedPartys[strtolower($name)]);
    }

    /**
     * @param PracticeParty $party
     * @param bool $sendMessage
     */
    public function removeFromQueue(PracticeParty $party, bool $sendMessage = true): void
    {
        $local = strtolower($party->getName());

        if (!isset($this->queuedPartys[$local])) {
            return;
        }

        /** @var QueuedParty $queue */
        $queue = $this->queuedPartys[$local];
        unset($this->queuedPartys[$local]);

        foreach ($party->getPlayers() as $player) {
            DefaultKits::sendPartyKit($player);
        }

        if ($sendMessage) {
            $members = $party->getPlayers();

            foreach ($members as $member) {
                $msg = PracticeParty::getPrefix() . "§cYou have left the queue for Party " . $queue->getQueue() . " (" . $queue->getSize() . "vs" . $queue->getSize() . ").";
                $member->sendMessage($msg);
            }
        }
    }

    /**
     * @param int $size
     * @param string|null $queue
     *
     * @return int
     */
    #[Pure] public function getPartysInQueue(int $size, string $queue = null): int
    {
        $count = 0;

        foreach ($this->queuedPartys as $pQueue) {
            if ($queue === null && $pQueue->getSize() === $size) {
                $count++;
            } elseif ($queue === $pQueue->getQueue() && $pQueue->getSize() === $size) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string|PracticeParty $party
     *
     * @return null|QueuedParty
     */
    #[Pure] public function getQueueOf(PracticeParty|string $party): ?QueuedParty
    {
        $name = $party instanceof PracticeParty ? $party->getName() : $party;

        if (isset($this->queuedPartys[strtolower($name)])) {
            return $this->queuedPartys[strtolower($name)];
        }

        return null;
    }

    /**
     * @return int
     */
    public function getEveryoneInQueues(): int
    {
        return count($this->queuedPartys);
    }

    /**
     * @param int $key
     *
     * Removes a duel with the given key.
     */
    public function removeDuel(int $key): void
    {
        if (isset($this->partyEvents[$key])) {
            $this->inactivePartyEvents->push($key);
        }
    }

    ////////////////////////////////////////////////////PARTY GAMES//////////////////////////////////////////////////////

    /**
     * @param PracticeParty $party
     * @param string $arena
     * @param int $size
     */
    public function placeInGames(PracticeParty $party, string $arena, int $size = 1): void
    {
        $worldId = 0;
        $dataPath = $this->server->getDataPath() . '/worlds';

        while (isset($this->partyEvents[$worldId]) || is_dir($dataPath . '/party' . $worldId)) {
            $worldId++;
        }

        $arena = PracticeCore::getArenaManager()->findGamesArena($arena);
        $create = Utils::createLevel($worldId, $arena->getLevel(), "party");

        if ($create) {
            $this->partyEvents[$worldId] = new PartyVsParty($worldId, $party, $size, $arena);
            //TODO MESSAGE ON START PG
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the party events.
     */
    public function update(): void
    {
        while (!$this->inactivePartyEvents->isEmpty()) {
            $id = $this->inactivePartyEvents->pop();
            unset($this->partyEvents[$id]);
        }

        $count = count($this->partyEvents);
        $partyEventKeys = array_keys($this->partyEvents);
        for ($i = $count - 1; $i >= 0; $i--) {
            $event = $this->partyEvents[$partyEventKeys[$i]];
            $event->update();
        }
    }
}