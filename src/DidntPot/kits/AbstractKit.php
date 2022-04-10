<?php

namespace DidntPot\kits;

use DidntPot\player\sessions\PlayerHandler;
use DidntPot\utils\Utils;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\Player;

abstract class AbstractKit
{
    /** @var float */
    protected $xkb;

    /** @var float */
    protected $ykb;

    /** @var int */
    protected $speed;

    /* @var Item[]|array */
    protected $items;

    /* @var Item[]|array */
    protected $armor;

    /* @var EffectInstance[]|array */
    protected $effects;

    /** @var string */
    protected $name;
    /** @var bool */
    protected $duelOnly;
    /** @var bool */
    protected $ffaOnly;
    /** @var bool */
    protected $damageOthers;
    /** @var bool */
    protected $replaysEnabled;
    /** @var string */
    protected $worldType;
    /* @var string|null */
    private $texture;

    public function __construct(string $name, array $items = [], array $armor = [], array $effects = [], $xkb = 0.4, $ykb = 0.4, $speed = 10, string $texture = null)
    {
        $this->name = $name;
        $this->items = $items;
        $this->armor = $armor;
        $this->effects = $effects;
        $this->xkb = $xkb;
        $this->ykb = $ykb;
        $this->speed = $speed;
        $this->texture = $texture;
        $this->duelOnly = false;
        $this->ffaOnly = false;
        $this->damageOthers = true;
        $this->replaysEnabled = true;
        $this->worldType = "TYPE_DUEL";
    }

    /**
     * @return string
     *
     * Gets the world type, used for replays.
     */
    public function getWorldType(): string
    {
        return $this->worldType;
    }

    /**
     * @return bool
     *
     * Determines if replays are enabled.
     */
    public function isReplaysEnabled(): bool
    {
        return $this->replaysEnabled;
    }

    /**
     * @return bool
     *
     * Determines if players can damage others.
     */
    public function canDamageOthers(): bool
    {
        return $this->damageOthers;
    }

    /**
     * @return int
     *
     * Gets the attack delay.
     */
    public function getSpeed(): int
    {
        return $this->speed;
    }

    /**
     * @param int $speed
     * @return AbstractKit
     *
     * Sets the attack delay of the kit.
     */
    public function setSpeed(int $speed): self
    {
        $this->speed = $speed;
        return $this;
    }

    /**
     * @return float
     *
     * Gets the x kb of the kit.
     */
    public function getXKb(): float
    {
        return $this->xkb;
    }

    /**
     * @param float $value
     * @return AbstractKit
     *
     * Sets the x kb of the kit.
     */
    public function setXKB(float $value): self
    {
        $this->xkb = $value;
        return $this;
    }

    /**
     * @return float
     *
     * Gets the y kb of the kit.
     */
    public function getYKb(): float
    {
        return $this->ykb;
    }

    /**
     * @param float $value
     * @return AbstractKit
     *
     * Sets the y kb of the kit.
     */
    public function setYKB(float $value): self
    {
        $this->ykb = $value;
        return $this;
    }

    /**
     * @param Player $player
     * @param bool $msg
     *
     * Gives the kit to another player.
     */
    public function giveTo(Player $player, bool $msg = true): void
    {
        PlayerHandler::getSession($player)->setKit($this);

        $itemKeys = array_keys($this->items);

        $inventory = $player->getInventory();

        $armorInventory = $player->getArmorInventory();

        foreach ($itemKeys as $key) {
            $slot = intval($key);
            $item = $this->items[$slot];
            $inventory->setItem($slot, $item);
        }

        $armorKeys = array_keys($this->armor);

        foreach ($armorKeys as $keys) {
            $slot = intval($keys);
            $item = $this->armor[$slot];
            $armorInventory->setItem($slot, $item);
        }

        // Print effects.
        // var_dump($this->effects);

        foreach ($this->effects as $effect) {
            $player->addEffect($effect);
        }

        $message = Utils::getPrefix() . "§aYou equipped the " . $this->getName() . " kit.";

        if ($msg) {
            $player->sendMessage($message);
        }
    }

    /**
     * @return string
     *
     * Gets the name of the kit.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     *
     * Gets the localized name of the kit.
     */
    public function getLocalizedName(): string
    {
        return strtolower($this->name);
    }

    /**
     * @return string|null
     *
     * Gets the texture of the kit.
     */
    public function getTexture(): ?string
    {
        return $this->texture;
    }

    /**
     * @return bool
     *
     * Determines if the kit has a texture.
     */
    public function hasTexture(): bool
    {
        return $this->texture !== null;
    }

    /**
     * @return array
     *
     * Exports the kit to a format that can be saved.
     */
    public function export(): array
    {
        return [
            'xkb' => $this->xkb,
            'ykb' => $this->ykb,
            'speed' => $this->speed
        ];
    }

    /**
     * @return bool
     *
     * Determines if the kit is duel only.
     */
    public function duelOnly(): bool
    {
        return $this->duelOnly;
    }

    /**
     * @return bool
     *
     * Determines if the kit is ffa only.
     */
    public function ffaOnly(): bool
    {
        return $this->ffaOnly;
    }
}