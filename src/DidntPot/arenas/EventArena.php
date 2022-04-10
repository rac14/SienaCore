<?php

namespace DidntPot\arenas;

use DidntPot\kits\AbstractKit;
use DidntPot\kits\Kits;
use DidntPot\player\PlayerExtensions;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class EventArena extends Arena
{
    const P1 = 'p1';
    const P2 = 'p2';

    /** @var Vector3 */
    protected $spectatorsSpawn;

    /** @var Vector3 */
    protected $center;

    /** @var Vector3 */
    protected $p1Spawn, $p2Spawn;

    /** @var Level|null */
    protected $level;

    /** @var string */
    private $name;

    /** @var AbstractKit */
    private $kit;

    /**
     * EventArena constructor.
     * @param string $name
     * @param Vector3 $center
     * @param string|Level $level
     * @param string|AbstractKit $kit
     * @param Vector3|null $p1Spawn
     * @param Vector3|null $p2Spawn
     * @param Vector3|null $specSpawn
     */
    public function __construct(string $name, Vector3 $center, Level|string $level, AbstractKit|string $kit = Kits::SUMO, Vector3 $p1Spawn = null, Vector3 $p2Spawn = null, Vector3 $specSpawn = null)
    {
        $kits = PracticeCore::getKits();
        $this->name = $name;
        $this->kit = $kit instanceof AbstractKit ? $kit : $kits->getKit($kit);
        $this->center = $center;
        $this->spectatorsSpawn = $specSpawn ?? $center;
        $this->p1Spawn = $p1Spawn ?? $center;
        $this->p2Spawn = $p2Spawn ?? $center;
        $this->level = $level instanceof Level ? $level : Server::getInstance()->getLevelByName($level);
    }

    /**
     * @return Level|null
     */
    public function getLevel(): ?Level
    {
        return $this->level;
    }

    /**
     * @return Vector3
     */
    public function getCenter(): Vector3
    {
        return $this->center;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Player $player
     * @param bool $value
     */
    public function teleportPlayer(Player $player, bool $value = false): void
    {
        $spawn = $this->spectatorsSpawn;

        $getKit = false;

        if ($value === self::P1) {
            $spawn = $this->p1Spawn;
            $getKit = true;
        }

        if ($value === self::P2) {
            $spawn = $this->p2Spawn;
            $getKit = true;
        }

        if (!$getKit) {
            PlayerExtensions::clearAll($player);
            // TODO:
            /*$itemManager = PracticeCore::getItemHandler();
            $itemManager->spawnEventItems($player);*/
        } elseif ($this->kit !== null) {
            $this->kit->giveTo($player, false);
        }

        if ($this->level !== null) {
            $pos = Utils::toPosition($spawn, $this->level);
            $player->teleport($pos);
        }
    }

    /**
     * @return array
     */
    #[Pure] public function getData(): array
    {
        $kit = $this->getKit();
        $kit = $kit?->getName();
        $center = Utils::posToArray($this->center);
        $specSpawn = Utils::posToArray($this->spectatorsSpawn);
        $p1Spawn = Utils::posToArray($this->p1Spawn);
        $p2Spawn = Utils::posToArray($this->p2Spawn);
        $level = ($this->level !== null) ? $this->level->getName() : null;

        return [
            'level' => $level,
            'center' => $center,
            'spawn' => $specSpawn,
            'kit' => $kit,
            'p1' => $p1Spawn,
            'p2' => $p2Spawn,
            'type' => self::TYPE_EVENT
        ];
    }

    /**
     * @return AbstractKit|null
     */
    public function getKit(): ?AbstractKit
    {
        return $this->kit;
    }

    /**
     * @param Vector3 $pos
     */
    public function setP1SpawnPos(Vector3 $pos): void
    {
        $this->p1Spawn = $pos;
    }


    /**
     * @param Vector3 $pos
     */
    public function setP2SpawnPos(Vector3 $pos): void
    {
        $this->p2Spawn = $pos;
    }

    /**
     * @param Vector3 $pos
     */
    public function setSpawn(Vector3 $pos): void
    {
        $this->spectatorsSpawn = $pos;
    }

    /**
     * @return string
     *
     * Gets the texture used by the kit.
     */
    #[Pure] public function getTexture(): string
    {
        $texture = '';

        if ($this->kit !== null) {
            $texture = $this->kit->getTexture();
        }

        return $texture;
    }
}