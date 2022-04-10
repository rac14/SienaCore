<?php

namespace DidntPot\commands\types\moderation\rank;

use DidntPot\commands\types\PracticeCommand;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\utils\StaffUtils;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SetRankCommand extends PracticeCommand
{
    public function __construct()
    {
        parent::__construct("setrank", "", "/setrank [string: rankName] [string: playerName]");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {

            $session = PlayerHandler::getSession($sender);
            $ranks = ["player", "knight", "duke", "siena", "nitro", "media", "famous", "helper", "moderator", "admin", "manager", "owner"];

            $usage = "Usage: /setrank [string: rankName] [string: playerName]";

            if ((!$sender->isOp())){
                $sender->sendMessage(TextFormat::RED . "Unknown command. Try /help for a list of commands");
                return;
            }

            if (!isset($args[0])) {
                $sender->sendMessage(Utils::getPrefix() . $usage);
                return;
            }

            if (!isset($args[1])) {
                $sender->sendMessage(Utils::getPrefix() . $usage);
                return;
            }

            if(!in_array(strtolower($args[0]), $ranks))
            {
                $sender->sendMessage(Utils::getPrefix() . "The provided rank (" . $args[0] . ") does not exist.");
                return;
            }

            if (isset($args[1]) and ($target = Server::getInstance()->getPlayer($args[1])) === null) {
                $sender->sendMessage(Utils::getPrefix() . "§c" . $args[1] . " isn't online on your current region.");
                return;
            }

            $target = Server::getInstance()->getPlayer($args[1]);

            if ($target instanceof Player) {
                $targetSession = PlayerHandler::getSession($target);

                $targetSession->setRank($args[0]);
                $target->setNameTag(Utils::formatNameTag($target, $args[0]));

                $message = StaffUtils::sendStaffNotification("rank_change");

                $message = str_replace("{name}", $sender->getName(), $message);
                $message = str_replace("{target}", $target->getName(), $message);
                $message = str_replace("{rank}", $args[0], $message);

                foreach (Server::getInstance()->getOnlinePlayers() as $online) {
                    if ($online->hasPermission("practice.staff.notification")) {
                        $online->sendMessage($message);
                    }
                }

                $sender->sendMessage(Utils::getPrefix() . "§a" . $target->getName() . "'s rank was updated to " . $targetSession->getRank() . ".");
                $target->sendMessage(Utils::getPrefix() . "§aYour rank was updated to " . $targetSession->getRank() . ".");
            }
        }
    }
}