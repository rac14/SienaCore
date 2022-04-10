<?php

namespace DidntPot\player\sessions;

use pocketmine\Player;

class PlayerHandler
{
    /** @var Session[] */
    static public array $sessions = [];

    /**
     * @return Session[]
     */
    static public function getSessions(): array
    {
        return self::$sessions;
    }

    static public function getSession(Player $player): ?Session
    {
        return self::getSessionByXuid($player->getUniqueId()->toString()) ?? null;
    }

    static public function getSessionByXuid(string $xuid): ?Session
    {
        return self::$sessions[$xuid] ?? null;
    }

    static public function createSession(Player $player): void
    {
        if (!self::hasSession($player)) {
            self::$sessions[$player->getUniqueId()->toString()] = new Session($player);
        }
    }

    static public function hasSession(Player $player): bool
    {
        return self::hasSessionByXuid($player->getUniqueId()->toString());
    }

    static public function hasSessionByXuid(string $xuid): bool
    {
        return array_key_exists($xuid, self::$sessions);
    }

    static public function removeSession(Player $player): void
    {
        if (self::hasSession($player)) {
            unset(self::$sessions[$player->getUniqueId()->toString()]);
        }
    }
}