<?php

namespace DidntPot\parties\events\types\match\data;

use DidntPot\parties\PracticeParty;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class PracticeTeam
{
    /* @var Player[]|array */
    private array $players;

    /* @var bool */
    private bool $eliminated;

    /* @var string */
    private string $teamColor;

    /**
     * PracticeTeam constructor.
     *
     * @param array|string[] $excludedColors
     */
    public function __construct(array $excludedColors = [])
    {
        $this->players = [];
        $this->eliminated = false;
        $this->teamColor = Utils::randomColor($excludedColors);
    }

    /**
     * @param Player $player
     */
    public function addToTeam(Player $player): void
    {
        $local = strtolower($player->getName());
        $this->players[$local] = $player;
    }

    /**
     * @param PracticeParty $party
     */
    public function addPartyToTeam(PracticeParty $party): void
    {
        $players = $party->getPlayers();
        foreach ($players as $player) {
            $local = strtolower($player->getName());
            $this->players[$local] = $player;
        }
    }

    /**
     * Sets the team as eliminated
     */
    public function setEliminated(): void
    {
        $this->eliminated = true;
    }

    /**
     * @return string
     */
    public function getTeamColor(): string
    {
        return $this->teamColor;
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param string|Player $player
     *
     * @return bool
     */
    #[Pure] public function isInTeam(Player|string $player): bool
    {
        $name = ($player instanceof Player) ? $player->getName() : $player;
        return isset($this->players[strtolower($name)]);
    }

    /**
     * @param string|Player $player
     *
     * @return void
     */
    public function removeFromTeam(Player|string $player): void
    {
        $name = ($player instanceof Player) ? $player->getName() : $player;
        $local = strtolower($name);
        if (isset($this->players[$local]))
            unset($this->players[$local]);
    }
}