<?php

namespace DidntPot\arenas;

use DidntPot\kits\AbstractKit;
use DidntPot\kits\Kits;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class FFAArena extends Arena
{
    /* @var Vector3 */
    protected $center;

    /* @var AbstractKit */
    protected $kit;

    /* @var Level */
    protected $level;

    /** @var string */
    private $name;

    /** @var bool */
    private $open;

    /** @var Vector3 */
    private $spawn;

    /** @var int */
    private $size = 15;

    /**
     * FFAArena constructor.
     * @param string $name
     * @param Vector3 $center
     * @param Vector3 $spawn
     * @param Level $level
     * @param string|AbstractKit $kit
     */
    #[Pure] public function __construct(string $name, Vector3 $center, Vector3 $spawn, Level $level, AbstractKit|string $kit = Kits::FIST)
    {
        $kits = PracticeCore::getKits();
        $this->name = $name;
        $this->kit = ($kit instanceof AbstractKit) ? $kit : $kits->getKit($kit);
        $this->center = $center;
        $this->level = $level;
        $this->open = true;
        $this->spawn = $spawn;
    }

    /**
     * @return bool
     *
     * Determines if an arena is open or not.
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @return Level
     */
    public function getLevel(): Level
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
     * @param Player $player
     * @param bool $value
     */
    public function teleportPlayer(Player $player, bool $value = true): void
    {
        $this->kit->giveTo($player, false);
        $pos = Utils::toPosition($this->spawn, $this->level);
        $player->teleport($pos);

        $name = $this->getName();
        $name = str_replace("-FFA", "", $name);

        $message = Utils::getPrefix() . "§aYou have joined the " . $name. " FFA.";

        if ($value) {
            $player->sendMessage($message);
        }
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
     * @return bool
     *
     * Determines whether the player is in the protection.
     */
    public function isWithinProtection(Player $player): bool
    {
        $maxX = $this->spawn->x + $this->size;
        $minX = $this->spawn->x - $this->size;

        $maxY = 255;
        $minY = $this->spawn->y - 3;

        if ($minY <= 0) {
            $minY = 0;
        }

        $maxZ = $this->spawn->z + $this->size;
        $minZ = $this->spawn->z - $this->size;

        $position = $player->asVector3();

        $withinX = Utils::isWithinBounds($position->x, $maxX, $minX);
        $withinY = Utils::isWithinBounds($position->y, $maxY, $minY);
        $withinZ = Utils::isWithinBounds($position->z, $maxZ, $minZ);

        return $withinX and $withinY and $withinZ;
    }

    /**
     * @param Player $player
     *
     * Sets the spawn of the arena.
     */
    public function setSpawn(Player $player): void
    {
        $this->spawn = $player->asVector3();
    }

    /**
     * @return array
     */
    #[Pure] public function getData(): array
    {
        $kit = $this->getKit();
        $kitStr = ($kit !== null) ? $kit->getName() : null;
        $posArr = Utils::posToArray($this->center);
        $spawnArr = Utils::posToArray($this->spawn);
        $level = ($this->level !== null) ? $this->level->getName() : null;
        return [
            'kit' => $kitStr,
            'center' => $posArr,
            'spawn' => $spawnArr,
            'level' => $level,
            'type' => self::TYPE_FFA
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
     * @return string
     */
    #[Pure] public function getTexture(): string
    {
        $texture = '';

        if ($this->kit !== null)
            $texture = $this->kit->getTexture();

        return $texture;
    }
}