<?php

namespace DidntPot\commands\types\internal;

use DidntPot\arenas\FFAArena;
use DidntPot\commands\parameters\BaseParameter;
use DidntPot\commands\parameters\Parameter;
use DidntPot\commands\parameters\SimpleParameter;
use DidntPot\commands\types\AdvancedCommand;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ArenaCommand extends AdvancedCommand
{
    public function __construct()
    {
        parent::__construct("arena", "", "/arena help");

        $parameters = [
            0 => [
                new BaseParameter("help", Parameter::NO_PERMISSION, "Lists all the kit commands.")
            ],

            1 => [
                new BaseParameter("create", Parameter::NO_PERMISSION, "Creates a new arena."),
                new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING),
                new SimpleParameter("arena-kit", Parameter::PARAMTYPE_STRING)
            ],

            2 => [
                new BaseParameter("delete", Parameter::NO_PERMISSION, "Deletes an existing arena."),
                new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING),
                new SimpleParameter("arena-kit", Parameter::PARAMTYPE_STRING)
            ],

            3 => [
                new BaseParameter("spawn", Parameter::NO_PERMISSION, "Sets the spawn position for the players in a duel arena."),
                new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING)
            ]
        ];

        $this->setParameters($parameters);
    }

    /**
     * @param CommandSender $sender
     * @param $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $commandLabel, array $args): bool
    {
        $msg = null;

        if ($sender instanceof Player and (!$sender->isOp())){
            $sender->sendMessage(TextFormat::RED . "Unknown command. Try /help for a list of commands");
            return false;
        }

        if (self::canExecute($sender, $args)) {
            $name = strval($args[0]);
            switch ($name) {
                case "help":
                    $msg = $this->getFullUsage();
                    break;
                case "create":
                    $aName = strval($args[1]);
                    $aKit = strval($args[2]);
                    $this->createArena($sender, $aName, $aKit);
                    break;
                case "delete":
                    $aName = strval($args[1]);
                    $aKit = strval($args[2]);
                    $this->deleteArena($sender, $aName, $aKit);
                    break;
                case "spawn":
                    $aName = strval($args[1]);
                    $this->setSpawn($sender, $aName);
                break;
            }
        }

        if (!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }

    private function createArena(CommandSender $sender, string $arenaName, string $arenaKit): void
    {
        $msg = null;
        if ($sender instanceof Player) {
            $arenaHandler = PracticeCore::getArenaManager();
            $kitHandler = PracticeCore::getKits();

            if ($kitHandler->isKit($arenaKit)) {
                $kit = $kitHandler->getKit($arenaKit);
                $arena = $arenaHandler->getArena($arenaName);

                if ($arena !== null) {
                    $msg = Utils::getPrefix() . "§cThe arena '%arena-name%' already exists.";
                    $msg = strval(str_replace("%arena-name%", $arenaName, $msg));
                }else{
                    $arenaHandler->createArena($arenaName, $kit->getLocalizedName(), $sender);
                    $msg = Utils::getPrefix() . "§aThe arena '%arena-name%' has been created.";
                    $msg = strval(str_replace("%arena-name%", $arenaName, $msg));
                }
            }else{
                $msg = Utils::getPrefix() . "§cThe kit '%kit-name%' does not exist.";
                $msg = strval(str_replace("%kit-name%", $arenaKit, $msg));
            }
        } else {
            $msg = "[CONSOLE] You can only use this command in-game.";
        }

        if ($msg !== null) $sender->sendMessage($msg);
    }

    private function deleteArena(CommandSender $sender, string $arenaName, string $arenaKit): void
    {
        $msg = null;

        if($sender instanceof Player) {
            $arenaHandler = PracticeCore::getArenaManager();
            $kitHandler = PracticeCore::getKits();

            $arena = $arenaHandler->getArena($arenaName);

            if($arena !== null and $arena instanceof FFAArena) {
                $arenaName = $arena->getName();

                $arenaHandler->deleteArena($arenaName);

                $msg = Utils::getPrefix() . "§cThe arena '%arena-name%' has been deleted.";
                $msg = strval(str_replace("%arena-name%", $arenaName, $msg));

            } else {
                $msg = Utils::getPrefix() . "§cThe arena '%arena-name%' does not exist.";
                $msg = strval(str_replace("%arena-name%", $arenaName, $msg));
            }

            if(isset($msg) and $msg !== null) {
                $sender->sendMessage($msg);
                return;
            }

        } else {
            $msg = "[CONSOLE] You can only use this command in-game.";
        }

        if ($msg !== null) $sender->sendMessage($msg);
    }

    private function setSpawn(CommandSender $sender, string $arenaName): void
    {
        $msg = null;

        if ($sender instanceof Player)
        {
            $arenaHandler = PracticeCore::getArenaManager();
            $arena = $arenaHandler->getArena($arenaName);

            if ($arena !== null and $arena instanceof FFAArena) {
                $arena->setSpawn($sender);
                $arenaHandler->editArena($arena);

                $msg = Utils::getPrefix() . TextFormat::GREEN . 'Successfully edited the arena.';
            } else {
                $msg = Utils::getPrefix() . "§cThe arena '%arena-name%' does not exist.";
                $msg = strval(str_replace("%arena-name%", $arenaName, $msg));
            }
        } else {
            $msg = "[CONSOLE] You can only use this command in-game.";
        }

        if (!is_null($msg)) $sender->sendMessage($msg);
    }
}