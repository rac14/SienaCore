<?php

namespace DidntPot\parties;

use DidntPot\kits\DefaultKits;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;

class PracticeParty
{
    public const MAX_PLAYERS = 4;

    /* @var int */
    private int $maxPlayers;

    /* @var Player[]|array */
    private array $players;

    /* @var Player */
    private Player $owner;

    /* @var string */
    private string $name;

    /* @var string */
    private string $lowername;

    /* @var bool */
    private bool $open;

    /* @var string[]|array */
    private array $blacklisted;

    #[Pure] public function __construct(Player $owner, string $name, int $maxPlayers, bool $open = true)
    {
        $this->owner = $owner;
        $this->name = $name;
        $this->maxPlayers = $maxPlayers;
        $local = strtolower($owner->getName());
        $this->players = [$local => $owner];
        $this->open = $open;
        $this->blacklisted = [];
        $this->lowername = strtolower($name);
    }

    /**
     * @param Player $player
     */
    public function addPlayer(Player $player): void
    {
        $name = $player->getName();

        $local = strtolower($name);

        if (!isset($this->players[$local])) {
            $this->players[$local] = $player;

            $duelHandler = PracticeCore::getDuelManager();

            DefaultKits::sendPartyKit($player);

            if ($duelHandler->isInQueue($player))
                $duelHandler->removeFromQueue($player, false);

            foreach ($this->players as $member) {
                if ($member->isOnline()) {
                    $msg = PracticeParty::getPrefix() . "§e" . $player->getName() . "§a has joined the party.";
                    PlayerHandler::getSession($member)->setParty(true);
                    PlayerHandler::getSession($player)->setParty(true);
                    $member->sendMessage($msg);
                }
            }
        }
    }

    /**
     * @return string
     */
    public static function getPrefix(): string
    {
        return "§6Party §7§l» §r";
    }

    /**
     * @param Player $player
     * @param string $reason
     * @param bool $blacklist
     */
    public function removePlayer(Player $player, string $reason = '', bool $blacklist = false): void
    {
        $name = $player->getName();
        $duelHandler = PracticeCore::getDuelManager();

        $kicked = $reason !== '';

        $local = strtolower($name);

        if (isset($this->players[$local])) {
            PracticeCore::getPartyManager()->getEventManager()->removeFromQueue($this);

            if ($this->isOwner($player)) {
                $partyManager = PracticeCore::getPartyManager();

                $partyEvent = $partyManager->getEventManager()->getPartyEvent($this);

                if ($partyEvent !== null) {
                    foreach ($this->players as $p) {
                        if ($p->isOnline()) {
                            $partyEvent->removeFromEvent($p);
                        }
                    }
                }

                foreach ($this->players as $member) {
                    if ($member->isOnline()) {
                        $inHub = Utils::areLevelsEqual($member->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY));

                        if (!$inHub) Utils::resetPlayer($member, true, true);

                        DefaultKits::sendSpawnKit($member);
                        $msg = PracticeParty::getPrefix() . "§e" . $player->getName() . " §cdisbanded the party.";
                        PlayerHandler::getSession($member)->setParty(false);
                        PracticeCore::getScoreboardManager()->sendSpawnScoreboard($member);
                        $member->sendMessage($msg);
                    }
                }

                $partyManager->endParty($this);
                return;
            }

            $inHub = Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY));

            if (!$inHub)
                Utils::resetPlayer($player, false, true);

            DefaultKits::sendSpawnKit($player);

            if ($kicked && $blacklist)
                $this->addToBlacklist($player);

            foreach ($this->players as $member) {
                if ($member->isOnline()) {
                    $msg = PracticeParty::getPrefix() . "§e" . $player->getName() . "§c has left the party.";
                    PlayerHandler::getSession($player)->setParty(false);
                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($member, false, [], true, PracticeCore::getPartyManager()->getPartyFromPlayer($member));
                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player);
                    $member->sendMessage($msg);
                }
            }

            unset($this->players[$local]);
        }
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    #[Pure] public function isOwner(Player $player): bool
    {
        if ($player->getName() === $this->owner->getName()) {
            return true;
        }

        return false;
    }

    /**
     * @param string|Player $player
     */
    public function addToBlacklist(Player|string $player): void
    {
        $name = ($player instanceof Player) ? $player->getName() : $player;
        $this->blacklisted[] = $name;

        if ($player instanceof Player) {
            $name = $player->getDisplayName();
        } else {
            if (($player = Utils::getPlayer($name)) !== null && $player instanceof Player) {
                $name = $player->getDisplayName();
            }
        }

        foreach ($this->players as $member) {
            if ($member->isOnline()) {
                $msg = PracticeParty::getPrefix() . "§e" . $name . "§a was added to the blacklist.";
                $member->sendMessage($msg);
            }
        }
    }

    /**
     * @param string|Player $player
     *
     * @return bool
     */
    #[Pure] public function isPlayer(Player|string $player): bool
    {
        $local = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);

        if (isset($this->players[$local])) {
            return true;
        }

        foreach ($this->players as $player) {
            $displayName = strtolower($player->getDisplayName());
            if ($displayName === $local) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PracticeParty $party
     *
     * @return bool
     */
    #[Pure] public function equalsParty(PracticeParty $party): bool
    {
        if ($party !== null && $party instanceof PracticeParty)
            return $party->getName() === $this->getName();
        return false;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLowerName(): string
    {
        return $this->lowername;
    }

    /**
     * @param bool $int
     *
     * @return array|int
     */
    public function getPlayers(bool $int = false): array|int
    {
        return ($int) ? count($this->players) : $this->players;
    }

    /**
     * @return int
     */
    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    /**
     * @param int $players
     */
    public function setMaxPlayers(int $players): void
    {
        $this->maxPlayers = $players;
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @param bool $open
     */
    public function setOpen(bool $open = true): void
    {
        $this->open = $open;
    }

    /**
     * @param Player $player
     */
    public function promoteToOwner(Player $player): void
    {
        $max = 4;
        $session = PlayerHandler::getSession($player);
        $rank = $session->getRank();
        $rank = strtolower($rank);

        if ($rank === "owner" || $rank === "admin" || $rank === "famous" || $rank === "siena") {
            if ($max < 10) $max = 10;
        } elseif ($rank === "moderator" || $rank === "media" || $rank === "duke") {
            if ($max < 8) $max = 8;
        } elseif ($rank === "knight") {
            if ($max < 6) $max = 6;
        }

        if ($this->maxPlayers > $max) {
            $this->owner->sendMessage(PracticeParty::getPrefix() . "§cYou cannot promote §e" . $player->getName() . "§c because the party is full.");
            return;
        }

        $oldLocal = $this->getLocalName();

        $oldOwner = $this->owner;

        $this->owner = $player;

        foreach ($this->players as $member) {
            if ($member->isOnline()) {
                $msg = PracticeParty::getPrefix() . "§e" . $player->getName() . "§a got promoted to Owner.";
                $member->sendMessage($msg);
            }
        }

        $partyManager = PracticeCore::getPartyManager();

        $newLocal = $this->getLocalName();

        DefaultKits::sendPartyKit($oldOwner);
        DefaultKits::sendPartyKit($this->owner);

        $partyManager->swapLocal($oldLocal, $newLocal);
    }

    /**
     * @return string
     */
    #[Pure] public function getLocalName(): string
    {
        return strtolower($this->owner->getName()) . ':' . $this->getName();
    }

    /**
     * @return Player
     */
    public function getOwner(): Player
    {
        return $this->owner;
    }

    /**
     * @param string $name
     *
     * @return Player|null
     */
    #[Pure] public function getPlayer(string $name): ?Player
    {
        $local = strtolower($name);
        if (isset($this->players[$local])) {
            return $this->players[$local];
        }

        foreach ($this->players as $player) {
            $displayName = strtolower($player->getDisplayName());
            if ($displayName === $name) {
                return $player;
            }
        }
        return null;
    }

    /**
     * @param string|Player $player
     *
     * @return bool
     */
    #[Pure] public function isBlackListed(Player|string $player): bool
    {
        $name = $player instanceof Player ? $player->getName() : $player;
        return in_array($name, $this->blacklisted);
    }

    /**
     * @param string|Player $player
     */
    public function removeFromBlacklist(Player|string $player): void
    {
        $name = ($player instanceof Player) ? $player->getName() : $player;

        if (in_array($name, $this->blacklisted)) {
            $index = array_search($name, $this->blacklisted);
            unset($this->blacklisted[$index]);
            $this->blacklisted = array_values($this->blacklisted);

            if ($player instanceof Player) {
                $name = $player->getDisplayName();
            } else {
                if (($player = Utils::getPlayer($name)) !== null && $player instanceof Player) {
                    $name = $player->getDisplayName();
                }
            }

            foreach ($this->players as $member) {
                if ($member->isOnline()) {
                    $msg = PracticeParty::getPrefix() . "§e" . $name . "§a was removed from the blacklist.";
                    $member->sendMessage($msg);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getBlacklisted(): array
    {
        return $this->blacklisted;
    }
}