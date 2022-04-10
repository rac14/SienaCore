<?php

namespace DidntPot\commands\types\basic;

use DidntPot\commands\types\PracticeCommand;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class PingCommand extends PracticeCommand
{
    public function __construct()
    {
        parent::__construct("ping", "", "/ping", ["ms", "latency"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {

            if (!isset($args[0])) {
                $sender->sendMessage(Utils::getPrefix() . "§aYour latency to the server is approximately " . $sender->getPing() . "ms.");
                return;
            }

            if (isset($args[0]) and ($target = Server::getInstance()->getPlayer($args[0])) === null) {
                $sender->sendMessage(Utils::getPrefix() . "§c" . $args[0] . " isn't online on your current region.");
                return;
            }

            $target = Server::getInstance()->getPlayer($args[0]);

            if ($target instanceof Player) {
                $sender->sendMessage(Utils::getPrefix() . "§a" . $target->getName() . "'s latency to the server is approximately " . $target->getPing() . "ms.");
            }
        }
    }
}