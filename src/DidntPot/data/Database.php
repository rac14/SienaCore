<?php

namespace DidntPot\data;

use DidntPot\PracticeCore;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class Database
{
    const INIT_TABLE_STATS = "network.init.table.stats";
    const INIT_TABLE_ELO = "network.init.table.elo";
    const INIT_TABLE_SETTINGS = "network.init.table.settings";

    const LOAD_PLAYER_SETTINGS_DATA = "network.load.playerdata";
    const LOAD_PLAYER_ELO_DATA = "network.load.elodata";
    const LOAD_PLAYER_STATS_DATA = "network.load.statsdata";

    const SAVE_PLAYER_SETTINGS_DATA = "network.update.playerdata";
    const SAVE_PLAYER_ELO_DATA = "network.update.elodata";
    const SAVE_PLAYER_STATS_DATA = "network.update.statsdata";

    /**
     * @var Config
     */
    private Config $config;
    /**
     * @var DataConnector
     */
    private DataConnector $database;

    /**
     * Database constructor.
     */
    public function __construct()
    {
        $this->config = new Config(PracticeCore::getDataFolderPath() . "settings.yml");
        $this->database = libasynql::create(PracticeCore::getInstance(), $this->config->get("database"), [
            "mysql" => "mysql.sql"
        ]);

        $this->initTables();
    }

    /**
     * Inits the tables.
     */
    public function initTables(): void
    {
        $this->database->executeGeneric(self::INIT_TABLE_STATS);
        $this->database->executeGeneric(self::INIT_TABLE_SETTINGS);
        $this->database->executeGeneric(self::INIT_TABLE_ELO);
    }

    /**
     * @return DataConnector
     */
    public function getDatabase(): DataConnector
    {
        return $this->database;
    }
}