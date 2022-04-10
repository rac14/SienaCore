<?php

namespace DidntPot\parties;

use DidntPot\kits\DefaultKits;
use DidntPot\parties\events\PartyEventManager;
use DidntPot\parties\requests\RequestHandler;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class PartyManager
{
    /* @var PracticeParty[]|array */
    private array $parties;

    /* @var PartyEventManager */
    private PartyEventManager $eventManager;

    /** @var RequestHandler */
    private RequestHandler $requestHandler;

    #[Pure] public function __construct(PracticeCore $core)
    {
        $this->parties = [];
        $this->requestHandler = new RequestHandler();
        $this->eventManager = new PartyEventManager($core);
    }

    /**
     * @return RequestHandler
     */
    public function getRequestHandler(): RequestHandler
    {
        return $this->requestHandler;
    }

    /**
     * @param Player $owner
     * @param string $name
     * @param int $maxPlayers
     * @param bool $open
     */
    public function createParty(Player $owner, string $name, int $maxPlayers, bool $open = true): void
    {
        $name = trim($name);

        $ownerName = $owner->getName();

        $local = strtolower($ownerName) . ":$name";

        if (!isset($this->parties[$local])) {
            $this->parties[$local] = new PracticeParty($owner, $name, $maxPlayers, $open);
            $owner->sendMessage(PracticeParty::getPrefix() . "§eYou have created a party.");
            DefaultKits::sendPartyKit($owner);
            PlayerHandler::getSession($owner)->setParty(true);
            PracticeCore::getScoreboardManager()->sendSpawnScoreboard($owner, false, [], true, PracticeCore::getPartyManager()->getPartyFromPlayer($owner));
        }
    }

    /**
     * @param Player $player
     *
     * @return PracticeParty|null
     */
    #[Pure] public function getPartyFromPlayer(Player $player): ?PracticeParty
    {
        $result = null;

        foreach ($this->parties as $party) {
            if ($party->isPlayer($player)) {
                $result = $party;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return PracticeParty|null
     */
    public function getPartyFromName(string $name): ?PracticeParty
    {
        $name = strtolower($name);

        $keys = array_keys($this->parties);

        $result = null;

        foreach ($keys as $key) {
            $partyName = strtolower(explode(':', $key)[1]);
            if ($partyName === $name) {
                $result = $this->parties[$key];
                break;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getParties(): array
    {
        return $this->parties;
    }

    /**
     * @return PartyEventManager
     */
    public function getEventManager(): PartyEventManager
    {
        return $this->eventManager;
    }

    /**
     * @param PracticeParty $party
     */
    public function endParty(PracticeParty $party): void
    {
        $local = $party->getLocalName();

        if (isset($this->parties[$local]))
            unset($this->parties[$local]);
    }

    /**
     * @param string $oldLocal
     * @param string $newLocal
     *
     * Only used for promoting a new owner.
     */
    public function swapLocal(string $oldLocal, string $newLocal)
    {
        if (isset($this->parties[$oldLocal])) {
            $party = $this->parties[$oldLocal];
            unset($this->parties[$oldLocal]);
            $this->parties[$newLocal] = $party;
        }
    }
}