<?php

namespace DidntPot\player\info;

use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class PlayerDeviceInfo
{
    public array $allCtrs = ["Unknown", "Mouse", "Touch", "Controller"];
    public array $allOs = ["Unknown", "Android", "iOS", "macOS", "FireOS", "GearVR", "HoloLens", "Windows10", "Windows", "EducalVersion", "Dedicated", "PlayStation4", "Switch", "XboxOne"];
    public array $controls = [];
    public array $os = [];
    public array $device = [];

    public PracticeCore $plugin;

    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;
    }

    #[Pure] public function getPlayerControls(Player $player): ?string
    {
        if (!isset($this->controls[$player->getName()]) or $this->controls[$player->getName()] === null) {
            return null;
        }

        return $this->controls[$player->getName()];
    }

    #[Pure] public function getPlayerOs(Player $player): ?string
    {
        if (!isset($this->os[$player->getName()]) or $this->os[$player->getName()] === null) {
            return null;
        }

        return $this->os[$player->getName()];
    }

    #[Pure] public function getPlayerDevice(Player $player): ?string
    {
        if (!isset($this->device[$player->getName()]) or $this->device[$player->getName()] === null) {
            return null;
        }

        return $this->device[$player->getName()];
    }
}