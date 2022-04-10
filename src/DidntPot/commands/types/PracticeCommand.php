<?php

namespace DidntPot\commands\types;

use pocketmine\command\Command;

abstract class PracticeCommand extends Command
{
    public function __construct(string $name, string $description = "", string $usageMessage = null, $aliases = [])
    {
        parent::__construct($name, $description, $usageMessage, $aliases);
    }
}