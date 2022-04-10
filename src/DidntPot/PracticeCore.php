<?php

namespace DidntPot;

use DidntPot\arenas\ArenaManager;
use DidntPot\commands\CommandManager;
use DidntPot\data\Database;
use DidntPot\duels\DuelManager;
use DidntPot\duels\level\classic\ClassicSpleefGen;
use DidntPot\duels\level\classic\ClassicSumoGen;
use DidntPot\duels\level\duel\BurntDuelGen;
use DidntPot\duels\level\duel\RiverDuelGen;
use DidntPot\events\EventManager;
use DidntPot\game\entities\SplashPotion;
use DidntPot\game\hologram\HologramHandler;
use DidntPot\game\level\gen\PracticeGenManager;
use DidntPot\kits\Kits;
use DidntPot\parties\PartyManager;
use DidntPot\player\info\PlayerClicksInfo;
use DidntPot\player\info\PlayerDeviceInfo;
use DidntPot\player\item\PlayerItemListener;
use DidntPot\player\PlayerListener;
use DidntPot\scoreboard\ScoreboardManager;
use DidntPot\tasks\types\duels\DuelTask;
use DidntPot\tasks\types\internal\SessionTask;
use DidntPot\utils\Utils;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class PracticeCore extends PluginBase
{
    /** @var string */
    public const NAME = "Siena";

    /** @var string */
    public const LOBBY = "lobby";

    /** @var float */
    public const LOBBY_X = 0.1;
    /** @var float */
    public const LOBBY_Y = 100.0;
    /** @var float */
    public const LOBBY_Z = 0.5;

    /** @var PracticeCore|null */
    private static ?PracticeCore $instance = null;

    /** @var string */
    private static string $dataFolder;

    /** @var string */
    private static string $resourceFolder;

    /** @var Database */
    private static Database $database;

    /** @var ScoreboardManager */
    private static ScoreboardManager $scoreboardManager;

    /** @var Kits */
    private static Kits $kits;

    /** @var ArenaManager */
    private static ArenaManager $arenaManager;

    /** @var DuelManager */
    private static DuelManager $duelManager;

    /** @var PartyManager */
    private static PartyManager $partyManager;

    /** @var EventManager */
    private static EventManager $eventManager;

    /** @var PracticeGenManager */
    private static PracticeGenManager $generatorManager;

    /** @var PlayerDeviceInfo */
    private static PlayerDeviceInfo $playerDeviceInfo;

    /** @var PlayerClicksInfo */
    private static PlayerClicksInfo $playerClicksInfo;

    /** @var HologramHandler */
    private static HologramHandler $hologramHandler;

    /**
     * @return PracticeCore
     *
     * Gets the instance of the core.
     */
    public static function getInstance(): PracticeCore
    {
        return self::$instance;
    }

    /**
     * @return PracticeGenManager
     *
     * Gets the instance of the PracticeGenManager.
     */
    public static function getGeneratorManager(): PracticeGenManager
    {
        return self::$generatorManager;
    }

    /**
     * @return ScoreboardManager
     *
     * Gets the instance of the ScoreboardManager.
     */
    public static function getScoreboardManager(): ScoreboardManager
    {
        return self::$scoreboardManager;
    }

    /**
     * @return Kits
     *
     * Gets the instance of the Kits.
     */
    public static function getKits(): Kits
    {
        return self::$kits;
    }

    /**
     * @return ArenaManager
     *
     * Gets the instance of the ArenaManager.
     */
    public static function getArenaManager(): ArenaManager
    {
        return self::$arenaManager;
    }

    /**
     * @return DuelManager
     *
     * Gets the instance of the DuelManager.
     */
    public static function getDuelManager(): DuelManager
    {
        return self::$duelManager;
    }

    /**
     * @return PartyManager
     *
     * Gets the instance of the PartyManager.
     */
    public static function getPartyManager(): PartyManager
    {
        return self::$partyManager;
    }

    /**
     * @return EventManager
     *
     * Gets the instance of the EventManager.
     */
    public static function getEventManager(): EventManager
    {
        return self::$eventManager;
    }

    /**
     * @return PlayerDeviceInfo
     *
     * Gets the instance of the PlayerDeviceInfo.
     */
    public static function getPlayerDeviceInfo(): PlayerDeviceInfo
    {
        return self::$playerDeviceInfo;
    }

    /**
     * @return PlayerClicksInfo
     *
     * Gets the instance of the PlayerClicksInfo.
     */
    public static function getPlayerClicksInfo(): PlayerClicksInfo
    {
        return self::$playerClicksInfo;
    }

    /**
     * @return HologramHandler
     *
     * Gets the instance of the HologramHandler.
     */
    public static function getHologramHandler(): HologramHandler
    {
        return self::$hologramHandler;
    }

    /**
     * @return string
     */
    public static function getRegionInfo(): string
    {
        $config = new Config(self::getInstance()->getDataFolder() . 'settings.yml', Config::YAML);

        if ($config->exists('region'))
            return (string)$config->get('region');

        return self::NAME;
    }

    /**
     * @return array
     */
    public static function getWebhookInfo(): array
    {
        $config = new Config(self::getInstance()->getDataFolder() . "settings.yml", Config::YAML);

        if ($config->exists("webhook"))
            return (array)$config->get("webhook");

        return ["ban" => "", "reports" => ""];
    }

    /**
     * @return string
     *
     * Gets the resources' folder.
     */
    public static function getResourcesFolder(): string
    {
        return self::$resourceFolder;
    }

    /**
     * @return string
     *
     * Gets the data folder.
     */
    public static function getDataFolderPath(): string
    {
        return self::$dataFolder;
    }

    /**
     * @return Database
     *
     * Gets the instance of the Database.
     */
    public function getDatabase(): Database
    {
        return self::$database;
    }

    /**
     * Called when the core is loaded, before calling onEnable().
     */
    public function onLoad(): void
    {
        self::$instance = $this;
    }

    /**
     * Called when the plugin is enabled.
     */
    public function onEnable(): void
    {
        if (self::$instance === null) {
            $this->getLogger()->critical("Core instance is NULL, disabling plugin.");
            $this->getServer()->shutdown();
        }

        self::$dataFolder = $this->getDataFolder();
        self::$resourceFolder = $this->getFile() . "resources/";

        $this->saveResource("settings.yml");

        $this->loadAllWorlds();

        self::$kits = new Kits(self::$instance);
        self::$arenaManager = new ArenaManager(self::$instance);
        self::$scoreboardManager = new ScoreboardManager(self::$instance);
        self::$duelManager = new DuelManager(self::$instance);
        self::$partyManager = new PartyManager(self::$instance);
        self::$eventManager = new EventManager(self::$instance);
        self::$generatorManager = new PracticeGenManager(self::$instance);
        new CommandManager(self::$instance);

        self::$hologramHandler= new HologramHandler(self::$instance);

        new PlayerListener(self::$instance);
        new PlayerItemListener(self::$instance);
        new PracticeListener(self::$instance);

        self::$playerDeviceInfo = new PlayerDeviceInfo(self::$instance);
        self::$playerClicksInfo = new PlayerClicksInfo(self::$instance);

        self::$database = new Database();

        //self::$generatorManager->registerGenerator(Utils::CLASSIC_DUEL_GEN, ClassicDuelGen::class, WorldReplayData::TYPE_DUEL);
        self::$generatorManager->registerGenerator(Utils::CLASSIC_SUMO_GEN, ClassicSumoGen::class, "type_sumo");
        self::$generatorManager->registerGenerator(Utils::CLASSIC_SPLEEF_GEN, ClassicSpleefGen::class, "type_spleef");

        self::$generatorManager->registerGenerator(Utils::RIVER_DUEL_GEN, RiverDuelGen::class, "type_duel");
        self::$generatorManager->registerGenerator(Utils::BURNT_DUEL_GEN, BurntDuelGen::class, "type_duel");

        $scheduler = $this->getScheduler();
        $scheduler->scheduleRepeatingTask(new PracticeTask(self::$instance), 1);

        Entity::registerEntity(SplashPotion::class, false, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);

        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "timings on", true);

        $this->getServer()->getNetwork()->setName(Utils::getThemeColor() . "§lSiena§r §8» §eS3§f");
    }

    public function loadAllWorlds()
    {
        foreach (array_diff(scandir($this->getServer()->getDataPath() . "worlds"), ["..", "."]) as $levelName) {
            $exclude = "none";

            $excludeArray = explode(",", $exclude);

            if (!in_array($levelName, $excludeArray)) {
                $server = $this->getServer();

                if (!$server->isLevelLoaded($levelName) && (!str_contains($levelName, '.')) && (!str_contains($levelName, '_'))) {
                    $server->loadLevel($levelName);
                    $server->getLevelByName($levelName)->setTime(0);
                    $server->getLevelByName($levelName)->stopTime();
                }

                if ($levelName === self::LOBBY) {
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "gamerule pvp false " . self::LOBBY, true);
                } else {
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "gamerule pvp true " . $levelName, true);
                }

                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "gamerule showcoordinates false " . $levelName, true);
            }
        }

        foreach ($this->getServer()->getLevels() as $worlds) {
            foreach ($worlds->getEntities() as $entity) {
                $entity->close();
            }
        }
    }

    /**
     * Called when the plugin is disabled.
     */
    public function onDisable(): void
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->transfer("sienamc.net");
        }

        foreach ($this->getServer()->getLevels() as $worlds) {
            foreach ($worlds->getEntities() as $entity) {
                $entity->close();
            }
        }
    }
}