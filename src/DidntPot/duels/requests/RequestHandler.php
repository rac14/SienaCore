<?php

namespace DidntPot\duels\requests;

use DidntPot\duels\level\classic\ClassicSpleefGen;
use DidntPot\player\info\replay\data\WorldReplayData;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use Grpc\Server;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class RequestHandler
{
    /* @var DuelRequest[]|array */
    private $requests;

    /* @var Server */
    private $server;

    /* @var PracticeCore */
    private $core;

    #[Pure] public function __construct(PracticeCore $core)
    {
        $this->server = $core->getServer();
        $this->requests = [];
        $this->core = $core;
    }

    /**
     * @param Player $from
     * @param Player $to
     * @param string $queue
     * @param bool $ranked
     * @param string|null $generator
     *
     * Sends a duel request from a player to another.
     */
    public function sendRequest(Player $from, Player $to, string $queue, bool $ranked, string $generator = null): void
    {
        if ($generator == null) {
            $kit = PracticeCore::getKits()->getKit($queue);

            $generator = match ($kit->getWorldType()) {
                WorldReplayData::TYPE_SUMO => Utils::randomizeSumoArenas(),
                WorldReplayData::TYPE_SPLEEF => ClassicSpleefGen::class,
                default => Utils::randomizeDuelArenas(),
            };
        }

        // TODO:
        /*$fromMsg = $from->getLanguage()->generalMessage(Language::SENT_REQUEST, ["name" => $to->getDisplayName()]);
        $from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

        $toMsg = $to->getLanguage()->generalMessage(Language::RECEIVE_REQUEST, ["name" => $from->getDisplayName()]);*/
        $key = $from->getName() . ':' . $to->getName();

        $send = true;

        if (isset($this->requests[$key])) {
            /** @var DuelRequest $oldRequest */
            $oldRequest = $this->requests[$key];
            $send = $oldRequest->getQueue() !== $queue or $oldRequest->isRanked() !== $ranked;
        }

        if ($send) {
            /*$to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);*/
        }

        $this->requests[$key] = new DuelRequest($from, $to, $queue, $ranked, $generator);
    }

    /**
     * @param Player $player
     * @return array Gets the requests of a player.
     *
     * Gets the requests of a player.
     */
    public function getRequestsOf(Player $player): array
    {
        $result = [];

        $name = $player->getName();

        foreach ($this->requests as $request) {
            $from = $request->getFrom();

            if ($request->getTo()->getName() === $name and $from->isOnline()) {
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
            if ($request->getFromName() === $name or $request->getToName() === $name) {
                unset($this->requests[$key]);
            }
        }
    }

    /**
     * @param DuelRequest $request
     *
     * Accepts a duel request.
     */
    public function acceptRequest(DuelRequest $request): void
    {
        $from = $request->getFrom();
        $to = $request->getTo();

        // TODO:
        /*$toMsg = $to->getLanguage()->generalMessage(Language::DUEL_ACCEPTED_REQUEST_TO, ["name" => $request->getFromDisplayName()]);
        $to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);

        $fromMsg = $from->getLanguage()->generalMessage(Language::DUEL_ACCEPTED_REQUEST_FROM, ["name" => $request->getToDisplayName()]);
        $from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);*/

        unset($this->requests[$request->getFromName() . ':' . $request->getToName()]);
    }
}