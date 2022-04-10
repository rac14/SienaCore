<?php

namespace DidntPot\commands\types\basic;

use DidntPot\commands\CommandManager;
use DidntPot\commands\types\PracticeCommand;
use DidntPot\parties\PracticeParty;
use DidntPot\player\PlayerExtensions;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class FlyCommand extends PracticeCommand
{
    public function __construct()
    {
        parent::__construct("fly", "", "/fly [bool: on|off]", ["flight"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {
            $player = $sender;
            $session = PlayerHandler::getSession($player);
            $usage = "Usage: /fly [bool: on|off]";

            if (!isset($args[0])) {
                $player->sendMessage(Utils::getPrefix() . $usage);
                return;
            }

            if ($session->isInDuelQueue()) {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in queue.");
                return;
            }

            if ($session->isInDuel()) {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in a duel.");
                return;
            }

            if ($session->hasParty()) {
                $player->sendMessage(PracticeParty::getPrefix() . "§cYou can't use this command while in a party.");
                return;
            }

            if ($session->isInSpawn()) {
                if (!$player->hasPermission("practice.command.flight")) {
                    $player->sendMessage(Utils::getPrefix() . "§cYou don't have permission, buy a rank at > https://sienamc.tebex.io/ to gain access to this command.");
                    $player->sendMessage(Utils::getPrefix() . CommandManager::SENDER_HAS_NO_PERMISSION);
                    return;
                }

                if ($args[0] === "on") {
                    PlayerExtensions::enableFlying($player, true);
                    $player->sendMessage(Utils::getPrefix() . "§aYou have enabled flight.");
                } elseif ($args[0] === "off") {
                    PlayerExtensions::enableFlying($player, false);
                    $player->sendMessage(Utils::getPrefix() . "§cYou have disabled flight.");
                } else {
                    $player->sendMessage(Utils::getPrefix() . $usage);
                    return;
                }
            } else {
                $player->sendMessage(Utils::getPrefix() . "§cYou can only use this command in spawn.");
                return;
            }
        }
    }
}