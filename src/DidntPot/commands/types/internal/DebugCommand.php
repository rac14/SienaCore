<?php

namespace DidntPot\commands\types\internal;

use DidntPot\commands\types\PracticeCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DebugCommand extends PracticeCommand
{
    public function __construct()
    {
        parent::__construct("debug", "", "/debug", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {
            $player = $sender;

            if ($sender instanceof Player and (!$sender->isOp())){
                $sender->sendMessage(TextFormat::RED . "Unknown command. Try /help for a list of commands");
                return false;
            }

            $player->jump();

            $player->sendMessage("[DEBUG] Executed.");
        }
    }
}
