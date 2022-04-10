<?php

namespace DidntPot\commands;

use DidntPot\commands\types\basic\FlyCommand;
use DidntPot\commands\types\basic\PingCommand;
use DidntPot\commands\types\basic\RekitCommand;
use DidntPot\commands\types\basic\SpawnCommand;
use DidntPot\commands\types\internal\ArenaCommand;
use DidntPot\commands\types\internal\DebugCommand;
use DidntPot\commands\types\moderation\GameModeCommand;
use DidntPot\commands\types\moderation\rank\SetRankCommand;
use DidntPot\misc\AbstractManager;
use DidntPot\PracticeCore;

class CommandManager extends AbstractManager
{
    public const SENDER_HAS_NO_PERMISSION = "§cYou don't have permission, buy a rank at §6§l»§r §esiena.tebex.io/ §6§l«§r §cto gain access to this command.";
    private PracticeCore $plugin;

    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;

        parent::__construct(false);
    }

    protected function load(bool $async = false): void
    {
        $cmdMap = $this->plugin->getServer()->getCommandMap();

        $cmdMap->unregister($cmdMap->getCommand("kill"));
        $cmdMap->unregister($cmdMap->getCommand("me"));

        /*$cmdMap->unregister($cmdMap->getCommand("op"));
        $cmdMap->unregister($cmdMap->getCommand("deop"));*/

        $cmdMap->unregister($cmdMap->getCommand("enchant"));
        $cmdMap->unregister($cmdMap->getCommand("effect"));
        $cmdMap->unregister($cmdMap->getCommand("defaultgamemode"));
        $cmdMap->unregister($cmdMap->getCommand("difficulty"));
        $cmdMap->unregister($cmdMap->getCommand("spawnpoint"));
        $cmdMap->unregister($cmdMap->getCommand("setworldspawn"));
        $cmdMap->unregister($cmdMap->getCommand("title"));
        $cmdMap->unregister($cmdMap->getCommand("seed"));
        $cmdMap->unregister($cmdMap->getCommand("particle"));
        $cmdMap->unregister($cmdMap->getCommand("gamemode"));
        $cmdMap->unregister($cmdMap->getCommand("tell"));
        $cmdMap->unregister($cmdMap->getCommand("say"));
        $cmdMap->unregister($cmdMap->getCommand("about"));
        $cmdMap->unregister($cmdMap->getCommand("help"));

        $this->save(false);
    }

    public function save(bool $async = false): void
    {
        $cmdMap = $this->plugin->getServer()->getCommandMap();

        $cmdMap->register('gm', new GameModeCommand());
        $cmdMap->register('setrank', new SetRankCommand());
        $cmdMap->register('arena', new ArenaCommand());
        $cmdMap->register('debug', new DebugCommand());
        // TODO: $cmdMap->register('party', new PartyCommand());

        $cmdMap->register('spawn', new SpawnCommand());
        $cmdMap->register('ping', new PingCommand());
        $cmdMap->register('fly', new FlyCommand());
        $cmdMap->register('rekit', new RekitCommand());
    }
}