<?php

namespace DidntPot\parties\requests;

use DidntPot\parties\PracticeParty;
use pocketmine\Player;

class RequestHandler
{
    /* @var PartyRequest[]|array */
    private array $requests;

    public function __construct()
    {
        $this->requests = [];
    }

    /**
     * @param Player $from
     * @param Player $to
     * @param PracticeParty $party
     *
     * Sends a party request from a player to another.
     */
    public function sendRequest(Player $from, Player $to, PracticeParty $party): void
    {
        $fromMsg = "§aYou have sent a party invite to §e" . $to->getName() . "§a.";

        $from->sendMessage($party::getPrefix() . $fromMsg);

        $toMsg = "§aYou have received a party invite from §e" . $from->getName() . "§a.";
        $key = $from->getName() . ':' . $to->getName();

        $to->sendMessage($party::getPrefix() . $toMsg);

        $this->requests[$key] = new PartyRequest($from, $to, $party);
    }

    /**
     * @param Player $player
     *
     * @return array
     *
     * Gets the requests of a player.
     */
    public function getRequestsOf(Player $player): array
    {
        $result = [];

        $name = $player->getName();

        foreach ($this->requests as $request) {
            $from = $request->getFrom();
            if ($request->getTo()->getName() === $name && $from->isOnline()) {
                $result[$from->getName()] = $request;
            }
        }

        return $result;
    }

    /**
     * @param string|Player $player
     *
     * Removes all requests with the player's name.
     */
    public function removeAllRequestsWith(Player|string $player): void
    {
        $name = $player instanceof Player ? $player->getName() : $player;

        foreach ($this->requests as $key => $request) {
            if ($request->getFromName() === $name || $request->getToName() === $name) {
                unset($this->requests[$key]);
            }
        }
    }

    /**
     * @param PartyRequest $request
     *
     * Accepts a party request.
     */
    public function acceptRequest(PartyRequest $request): void
    {
        $from = $request->getFrom();
        $to = $request->getTo();

        $toMsg = "§aYou have accepted " . $from->getName() . "'s party invite.";

        $to->sendMessage(PracticeParty::getPrefix() . $toMsg);

        $fromMsg = "§a" . $to->getName() . " has accepted your party invite.";

        $from->sendMessage(PracticeParty::getPrefix() . $fromMsg);

        unset($this->requests[$request->getFromName() . ':' . $request->getToName()]);
    }
}