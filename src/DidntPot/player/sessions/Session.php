<?php

namespace DidntPot\player\sessions;

use DidntPot\arenas\Arena;
use DidntPot\arenas\FFAArena;
use DidntPot\kits\AbstractKit;
use DidntPot\kits\DefaultKits;
use DidntPot\player\info\DuelInfo;
use DidntPot\player\info\PermissionInfo;
use DidntPot\player\PlayerExtensions;
use DidntPot\PracticeCore;
use DidntPot\scoreboard\ScoreboardUtils;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Attribute;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Session
{
    /** @var string */
    public const TAG_COMBAT = 'tag.combat';
    /** @var string */
    public const TAG_NORMAL = 'tag.normal';

    /** @var string */
    private $tagType = self::TAG_NORMAL;

    /** @var int */
    public const MAX_ENDERPEARL_SECONDS = 11 * 20;

    /** @var int */
    public const ANDROID = 1;
    /** @var int */
    public const IOS = 2;
    /** @var int */
    public const OSX = 3;
    /** @var int */
    public const FIREOS = 4;
    /** @var int */
    public const VRGEAR = 5;
    /** @var int */
    public const VRHOLOLENS = 6;
    /** @var int */
    public const WINDOWS_10 = 7;
    /** @var int */
    public const WINDOWS_32 = 8;
    /** @var int */
    public const DEDICATED = 9;
    /** @var int */
    public const TVOS = 10;
    /** @var int */
    public const PS4 = 11;
    /** @var int */
    public const SWITCH = 12;
    /** @var int */
    public const XBOX = 13;
    /** @var int */
    public const LINUX = 20;

    /** @var int */
    public const KEYBOARD = 1;
    /** @var int */
    public const TOUCH = 2;
    /** @var int */
    public const CONTROLLER = 3;
    /** @var int */
    public const MOTION_CONTROLLER = 4;

    /** @var string[] */
    private $deviceOSVals = [
        self::ANDROID => 'Android',
        self::IOS => 'iOS',
        self::OSX => 'OSX',
        self::FIREOS => 'FireOS',
        self::VRGEAR => 'VRGear',
        self::VRHOLOLENS => 'VRHololens',
        self::WINDOWS_10 => 'Win10',
        self::WINDOWS_32 => 'Win32',
        self::DEDICATED => 'Dedicated',
        self::TVOS => 'TVOS',
        self::PS4 => 'PS4',
        self::SWITCH => 'Nintendo Switch',
        self::XBOX => 'Xbox',
        self::LINUX => 'Linux'
    ];

    /** @var string[] */
    private $inputVals = [
        self::KEYBOARD => 'Keyboard',
        self::TOUCH => 'Touch',
        self::CONTROLLER => 'Controller',
        self::MOTION_CONTROLLER => 'Motion-Controller'
    ];

    public $deviceOS;
    public $inputAtLogin;
    public $clientRandomID;

    /** @var int */
    public int $last_chat_time = 0;
    /**
     * @var int|mixed
     */
    public mixed $combatSecs = 0;
    /**
     * @var mixed|int
     */
    public mixed $enderpearlSecs = 0;
    /**
     * @var int
     */
    protected int $cpsflags = 0;
    /**
     * @var int
     */
    protected int $reachflags = 0;
    /**
     * @var int
     */
    protected int $veloflags = 0;
    /**
     * @var int
     */
    protected int $timerflags = 0;
    /**
     * @var bool
     */
    protected bool $frozen = false;
    /**
     * @var bool
     */
    protected bool $chatcooldown = false;
    /**
     * @var bool
     */
    protected bool $staffmode = false;
    /**
     * @var bool
     */
    protected bool $vanished = false;
    /**
     * @var bool
     */
    protected bool $disguised = false;
    /**
     * @var bool
     */
    protected bool $messages = false;
    /**
     * @var bool
     */
    protected bool $coords = false;
    /**
     * @var bool
     */
    protected bool $anticheat = true;
    /**
     * @var bool
     */
    protected bool $combat = false;
    /**
     * @var bool
     */
    protected bool $canPearl = true;
    /**
     * @var Player
     */
    private Player $player;
    /**
     * @var string
     */
    private string $playerName;
    /**
     * @var String
     */
    public string $rank = "Player";

    /**
     * @var int
     */
    private int $kills = 0;
    /**
     * @var int
     */
    private int $deaths = 0;
    /**
     * @var int
     */
    private int $kdr = 0;
    /**
     * @var int
     */
    private int $killstreak = 0;
    /**
     * @var int
     */
    private int $bestkillstreak = 0;

    /**
     * @var int
     */
    private int $nodebuffElo = 1000;
    /**
     * @var int
     */
    private int $boxingElo = 1000;
    /**
     * @var int
     */
    private int $gappleElo = 1000;
    /**
     * @var int
     */
    private int $sumoElo = 1000;
    /**
     * @var int
     */
    private int $buildUHCElo = 1000;
    /**
     * @var int
     */
    private int $fistElo = 1000;
    /**
     * @var int
     */
    private int $comboElo = 1000;
    /**
     * @var int
     */
    private int $spleefElo = 1000;

    /** @var bool */
    private bool $scoreboard = true;
    /** @var bool */
    private bool $cpscounter = true;
    /** @var bool */
    private bool $autorequeue = false;
    /** @var bool */
    private bool $autorekit = false;
    /** @var bool */
    private bool $bloodfx = true;

    /** @var bool */
    private bool $dead = false;

    /**
     * @var int|mixed
     */
    private mixed $currentSec = 0;
    /**
     * @var array
     */
    private array $cps = [];
    /** @var string|null */
    private ?string $target = null;
    /* @var array */
    private $duelHistory = [];
    /** @var bool */
    private bool $party;
    /** @var FFAArena|Arena */
    private FFAArena|Arena $arena;
    private $kit;

    #[Pure] public function __construct(Player $player)
    {
        $this->player = $player;
        $this->playerName = $player->getName();
        $this->party = false;
        $this->kit = null;
    }

    # ------------------------------------------------------------------------------------------------------------ #
    #                                               INTERNAL PART                                                  #
    # ------------------------------------------------------------------------------------------------------------ #

    /**
     * @return void
     */
    public function updatePlayer(): void
    {
        $player = $this->player;

        $this->updateScoreboard();

        // !$this->isInDuel() disabled, no hunger loss.
        $player->setFood($player->getMaxFood());
        $player->setSaturation(Attribute::getAttribute(Attribute::SATURATION)->getMaxValue());

        if($this->isInDuel()) $this->setCombatNameTag();
        if($this->isInSpawn()) $this->setNormalNameTag();

        if ($this->isCombat()) {
            $this->combatSecs--;
            $this->setCombatNameTag();

            if ($this->combatSecs <= 0) {
                $this->setCombat(false);
            }
        }
    }

    public function updatePearlcooldown(): void
    {
        if(!$this->canThrowPearl())
        {
            $this->removeSecInThrow();

            if($this->enderpearlSecs <= 0)
                $this->setThrowPearl(true);
        }
    }

    public function updateScoreboard()
    {
        $player = $this->player;

        if (Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY)))
        {
            if ($player->spawned === false) return;
            if (PlayerHandler::hasSession($player) === false) return;
            if ($player === null) return;

            if (ScoreboardUtils::isPlayerSetDuelQueueScoreboard($player))
            {
                if (PracticeCore::getDuelManager()->isInQueue($player))
                {
                    if (is_null(PracticeCore::getDuelManager()->getQueueOf($player))) return;

                    $isRanked = PracticeCore::getDuelManager()->getQueueOf($player)->isRanked();

                    if ($isRanked) $ranked = "Ranked";
                    else $ranked = "Unranked";

                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player, true,
                        [
                            "isRanked" => $ranked,
                            "queue" => PracticeCore::getDuelManager()->getQueueOf($player)->getQueue()
                        ]
                    );

                    return;
                }
            }
        }

        if (ScoreboardUtils::isPlayerSetFFAScoreboard($player))
        {
            if ($this->isCombat()) PracticeCore::getScoreboardManager()->sendFFAScoreboard($player, true, $this->combatSecs);
        }
    }

    public function hasParty(): bool
    {
        return $this->party;
    }

    /**
     * @return Player|null
     */
    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    /**
     * @return void
     */
    private function removeSecInThrow(): void
    {
        $this->enderpearlSecs--;
        $maxSecs = self::MAX_ENDERPEARL_SECONDS;
        $sec = $this->enderpearlSecs;

        if($sec < 0) $sec = 0;
        else $sec = $sec / 20;

        $percent = floatval($this->enderpearlSecs / $maxSecs);

        if($this->getPlayer()->isOnline())
        {
            $p = $this->getPlayer();
            $p->setXpLevel($sec);
            $p->setXpProgress($percent);
        }
    }

    /**
     * @return bool
     */
    public function canThrowPearl(): bool
    {
        return $this->canPearl;
    }

    /**
     * @param bool $res
     * @return void
     */
    public function setThrowPearl(bool $res): void
    {
        if($res === false)
        {
            $this->enderpearlSecs = self::MAX_ENDERPEARL_SECONDS;

            if($this->getPlayer()->isOnline())
            {
                $p = $this->getPlayer();

                if($this->canPearl === true)
                    $p->sendMessage(Utils::getPrefix() . "§cEnderpearl cooldown has started.");

                $p->setXpProgress(1.0);
                $p->setXpLevel(self::MAX_ENDERPEARL_SECONDS);
            }
        } else
        {
            $this->enderpearlSecs = 0;

            if($this->getPlayer()->isOnline())
            {
                $p = $this->getPlayer();

                if($this->canPearl === false)
                    $p->sendMessage(Utils::getPrefix() . "§aEnderpearl cooldown has ended.");

                $p->setXpLevel(0);
                $p->setXpProgress(0);
            }
        }

        $this->canPearl = $res;
    }

    /**
     * @return bool
     */
    public function isCombat(): bool
    {
        return $this->combat !== false;
    }

    /**
     * @param bool $value
     * @param bool $msg
     */
    public function setCombat(bool $value, bool $msg = true)
    {
        $player = $this->player;

        if ($value === true) {
            $this->combat = true;
            $this->combatSecs = 15;
            $this->setCombatNameTag();
            if ($msg === true) $player->sendMessage(Utils::getPrefix() . "§cYou are now in combat, do not log out.");
            if ($msg === true) $player->sendPopup("§cCombat tagged");
        } else {
            $this->combat = false;
            $this->combatSecs = 0;
            $this->setNormalNameTag();
            if ($msg === true) $player->sendMessage(Utils::getPrefix() . "§aYou are now out of combat.");
            if ($msg === true) $player->sendPopup("§6Combat tag reduced");
            PracticeCore::getScoreboardManager()->sendFFAScoreboard($player);

            if ($this->hasTarget()) $this->setnoTarget();
        }
    }

    #[Pure] public function isInDuel(): bool
    {
        $duelManager = PracticeCore::getDuelManager();
        $duel = $duelManager->getDuel($this->getPlayer());
        return $duel !== null;
    }

    public function updateCps(): void
    {
        $cps = $this->cps;
        $player = $this->player;

        $microtime = microtime(true);

        $keys = array_keys($cps);

        foreach ($keys as $key) {
            $thecps = floatval($key);
            if ($microtime - $thecps > 1)
                unset($cps[$key]);
        }

        $this->cps = $cps;
        $yourCPS = count($this->cps);

        //$this->player->sendActionBarMessage("§r" . Utils::getThemeColor() . $yourCPS);
    }

    /**
     * @param bool $clickedBlock
     */
    public function addCps(bool $clickedBlock): void
    {
        $microtime = microtime(true);

        $keys = array_keys($this->cps);

        $size = count($keys);

        foreach ($keys as $key) {
            $cps = floatval($key);
            if ($microtime - $cps > 1)
                unset($this->cps[$key]);
        }

        if ($clickedBlock === true and $size > 0) {
            $index = $size - 1;
            $lastKey = $keys[$index];
            $cps = floatval($lastKey);

            if (isset($this->cps[$lastKey])) {
                $val = $this->cps[$lastKey];
                $diff = $microtime - $cps;
                if ($val === true and $diff <= 0.05)
                    unset($this->cps[$lastKey]);
            }
        }

        $this->cps["$microtime"] = $clickedBlock;

        $yourCPS = count($this->cps);
        $this->player->sendActionBarMessage("§r" . Utils::getThemeColor() . $yourCPS);
    }

    public function addCpsFlag()
    {
        $this->cpsflags = $this->cpsflags + 1;
    }

    /**
     * @return int
     */
    public function getCpsFlags(): int
    {
        return $this->cpsflags;
    }

    /**
     * @param int $int
     */
    public function setCpsFlags(int $int)
    {
        $this->cpsflags = $int;
    }

    public function addVeloFlag()
    {
        $this->veloflags = $this->veloflags + 1;
    }

    /**
     * @return int
     */
    public function getVeloFlags(): int
    {
        return $this->veloflags;
    }

    /**
     * @param int $int
     */
    public function setVeloFlags(int $int)
    {
        $this->veloflags = $int;
    }

    public function addTimerFlag()
    {
        $this->timerflags = $this->timerflags + 1;
    }

    /**
     * @return int
     */
    public function getTimerFlags(): int
    {
        return $this->timerflags;
    }

    /**
     * @param int $int
     */
    public function setTimerFlags(int $int)
    {
        $this->timerflags = $int;
    }

    public function addReachFlag()
    {
        $this->reachflags = $this->reachflags + 1;
    }

    /**
     * @return int
     */
    public function getReachFlags(): int
    {
        return $this->reachflags;
    }

    /**
     * @param int $int
     */
    public function setReachFlags(int $int)
    {
        $this->reachflags = $int;
    }

    /**
     * @return bool
     */
    public function isFrozen(): bool
    {
        return $this->frozen !== false;
    }

    /**
     * @param bool $value
     */
    public function setFrozen(bool $value)
    {
        $this->frozen = $value;
    }

    /**
     * @return bool
     */
    public function isStaffMode(): bool
    {
        return $this->staffmode !== false;
    }

    /**
     * @param bool $value
     */
    public function setStaffMode(bool $value)
    {
        $this->staffmode = $value;
    }

    /**
     * @return bool
     */
    public function isDisguised(): bool
    {
        return $this->disguised !== false;
    }

    /**
     * @param bool $value
     */
    public function setDisguised(bool $value)
    {
        $this->disguised = $value;
    }

    /**
     * @return bool
     */
    public function isVanished(): bool
    {
        return $this->vanished !== false;
    }

    /**
     * @param bool $value
     */
    public function setVanished(bool $value)
    {
        $this->vanished = $value;
    }

    /**
     * @param bool $value
     */
    public function setCoordins(bool $value)
    {
        $this->coords = $value;
    }

    /**
     * @return bool
     */
    public function isCoordins(): bool
    {
        return $this->coords !== false;
    }

    /**
     * @param bool $value
     */
    public function setAntiCheat(bool $value)
    {
        $this->anticheat = $value;
    }

    /**
     * @return bool
     */
    public function isAntiCheatOn(): bool
    {
        return $this->anticheat !== false;
    }

    /**
     * @return bool
     */
    public function isChatCooldown(): bool
    {
        return $this->chatcooldown !== false;
    }

    /**
     * @param bool $value
     */
    public function setChatCooldown(bool $value)
    {
        $this->chatcooldown = $value;
    }

    /**
     * @return bool
     */
    public function canChat(): bool
    {
        $player = $this->player;

        if ($player->hasPermission("practice.bypass.chatcooldown")) {
            return true;
        }

        if (time() - $this->last_chat_time >= Utils::LENGTH_CHAT_COOLDOWN) {
            $this->last_chat_time = time();
            return true;
        }
        return false;
    }

    # ------------------------------------------------------------------------------------------------------------ #
    #                                                  STATS PART                                                  #
    # ------------------------------------------------------------------------------------------------------------ #

    /**
     * @return array
     */
    #[Pure] public function getData(): array
    {
        return [
            "rank" => $this->getRank(),
            "kills" => $this->getKills(),
            "deaths" => $this->getDeaths(),
            "kdr" => $this->getKdr(),
            "ks" => $this->getKillstreak(),
            "bestks" => $this->getBestKillstreak()
        ];
    }

    /**
     * @return int[]
     */
    #[ArrayShape(["NoDebuff" => "int", "Boxing" => "int", "Gapple" => "int", "Sumo" => "int", "BuildUHC" => "int", "Fist" => "int", "Combo" => "int", "Spleef" => "int"])] #[Pure] public function getEloData(): array
    {
        return [
            "NoDebuff" => $this->getNodebuffElo(),
            "Boxing" => $this->getBoxingElo(),
            "Gapple" => $this->getGappleElo(),
            "Sumo" => $this->getSumoElo(),
            "BuildUHC" => $this->getBuildUHCElo(),
            "Fist" => $this->getFistElo(),
            "Combo" => $this->getComboElo(),
            "Spleef" => $this->getSpleefElo()
        ];
    }

    /**
     * @return string[]
     */
    #[ArrayShape(["scoreboard" => "bool", "cpscounter" => "bool", "autorekit" => "bool", "autorequeue" => "bool", "bloodfx" => "bool"])] #[Pure] public function getSettingsData(): array
    {
        return [
            "scoreboard" => $this->isScoreboard(),
            "cpscounter" => $this->isCpscounter(),
            "autorekit" => $this->isAutorekit(),
            "autorequeue" => $this->isAutorequeue(),
            "bloodfx" => $this->isBloodfx()
        ];
    }

    /**
     * @return String
     */
    public function getRank(): string
    {
        return $this->rank;
    }

    /**
     * @param String $rank
     */
    public function setRank(string $rank): void
    {
        $this->rank = $rank;
    }

    /**
     * @return int
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * @param int $kills
     */
    public function setKills(int $kills): void
    {
        $this->kills = $kills;
    }

    public function addKill(): void
    {
        $this->kills += 1;
        $this->addKillStreak();
    }

    /**
     * @return int
     */
    public function getDeaths(): int
    {
        return $this->deaths;
    }

    /**
     * @param int $deaths
     */
    public function setDeaths(int $deaths): void
    {
        $this->deaths = $deaths;
    }

    public function addDeath(): void
    {
        $this->deaths += 1;
        $this->resetKillStreak();
    }

    /**
     * @return float
     */
    #[Pure] public function getKdr(): float
    {
        return round($this->getKills() / ($this->getDeaths() === 0 ? 1 : $this->getDeaths()));
    }

    /**
     * @param int $kdr
     */
    public function setKdr(int $kdr): void
    {
        $this->kdr = $kdr;
    }

    /**
     * @return int
     */
    public function getKillstreak(): int
    {
        return $this->killstreak;
    }

    /**
     * @param int $killstreak
     */
    public function setKillstreak(int $killstreak): void
    {
        $this->killstreak = $killstreak;
    }

    private function addKillStreak(): void
    {
        $this->killstreak += 1;
    }

    private function resetKillStreak(): void
    {
        $this->killstreak = 0;
    }

    /**
     * @return int
     */
    public function getBestKillstreak(): int
    {
        return $this->bestkillstreak;
    }

    /**
     * @param int $bestkillstreak
     */
    public function setBestKillstreak(int $bestkillstreak): void
    {
        $this->bestkillstreak = $bestkillstreak;
    }

    /**
     * @return int
     */
    public function getNodebuffElo(): int
    {
        return $this->nodebuffElo;
    }

    /**
     * @param int $nodebuffElo
     */
    public function setNodebuffElo(int $nodebuffElo): void
    {
        $this->nodebuffElo = $nodebuffElo;
    }

    /**
     * @return int
     */
    public function getBoxingElo(): int
    {
        return $this->boxingElo;
    }

    /**
     * @param int $boxingElo
     */
    public function setBoxingElo(int $boxingElo): void
    {
        $this->boxingElo = $boxingElo;
    }

    /**
     * @return int
     */
    public function getGappleElo(): int
    {
        return $this->gappleElo;
    }

    /**
     * @param int $gappleElo
     */
    public function setGappleElo(int $gappleElo): void
    {
        $this->gappleElo = $gappleElo;
    }

    /**
     * @return int
     */
    public function getSumoElo(): int
    {
        return $this->sumoElo;
    }

    /**
     * @param int $sumoElo
     */
    public function setSumoElo(int $sumoElo): void
    {
        $this->sumoElo = $sumoElo;
    }

    /**
     * @return int
     */
    public function getBuildUHCElo(): int
    {
        return $this->buildUHCElo;
    }

    /**
     * @param int $buildUHCElo
     */
    public function setBuildUHCElo(int $buildUHCElo): void
    {
        $this->buildUHCElo = $buildUHCElo;
    }

    /**
     * @return int
     */
    public function getFistElo(): int
    {
        return $this->fistElo;
    }

    /**
     * @param int $fistElo
     */
    public function setFistElo(int $fistElo): void
    {
        $this->fistElo = $fistElo;
    }

    /**
     * @return int
     */
    public function getComboElo(): int
    {
        return $this->comboElo;
    }

    /**
     * @param int $comboElo
     */
    public function setComboElo(int $comboElo): void
    {
        $this->comboElo = $comboElo;
    }

    /**
     * @return int
     */
    public function getSpleefElo(): int
    {
        return $this->spleefElo;
    }

    /**
     * @param int $spleefElo
     */
    public function setSpleefElo(int $spleefElo): void
    {
        $this->spleefElo = $spleefElo;
    }

    /**
     * @return bool
     */
    public function isScoreboard(): bool
    {
        return $this->scoreboard;
    }

    /**
     * @param bool $scoreboard
     */
    public function setScoreboard(bool $scoreboard): void
    {
        $this->scoreboard = $scoreboard;
    }

    /**
     * @return bool
     */
    public function isCpscounter(): bool
    {
        return $this->cpscounter;
    }

    /**
     * @param bool $cpscounter
     */
    public function setCpscounter(bool $cpscounter): void
    {
        $this->cpscounter = $cpscounter;
    }

    /**
     * @return bool
     */
    public function isAutorequeue(): bool
    {
        return $this->autorequeue;
    }

    /**
     * @param bool $autorequeue
     */
    public function setAutorequeue(bool $autorequeue): void
    {
        $this->autorequeue = $autorequeue;
    }

    /**
     * @return bool
     */
    public function isAutorekit(): bool
    {
        return $this->autorekit;
    }

    /**
     * @param bool $autorekit
     */
    public function setAutorekit(bool $autorekit): void
    {
        $this->autorekit = $autorekit;
    }

    /**
     * @return bool
     */
    public function isBloodfx(): bool
    {
        return $this->bloodfx;
    }

    /**
     * @param bool $bloodfx
     */
    public function setBloodfx(bool $bloodfx): void
    {
        $this->bloodfx = $bloodfx;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->setRank($data["rank"]);
        $this->setKills($data["kills"]);
        $this->setDeaths($data["deaths"]);
        $this->setKdr($data["kdr"]);
        $this->setKillstreak($data["ks"]);
        $this->setBestKillstreak($data["bestks"]);
    }

    /**
     * @param array $data
     */
    public function setEloData(array $data)
    {
        $this->setNodebuffElo($data["NoDebuff"]);
        $this->setBoxingElo($data["Boxing"]);
        $this->setGappleElo($data["Gapple"]);
        $this->setSumoElo($data["Sumo"]);
        $this->setBuildUHCElo($data["BuildUHC"]);
        $this->setFistElo($data["Fist"]);
        $this->setComboElo($data["Combo"]);
        $this->setSpleefElo($data["Spleef"]);
    }

    /**
     * @param array $data
     */
    public function setSettingsData(array $data)
    {
        $this->setScoreboard($data["scoreboard"]);
        $this->setCpscounter($data["cpscounter"]);
        $this->setAutorekit($data["autorekit"]);
        $this->setAutorequeue($data["autorequeue"]);
        $this->setAutorekit($data["autorekit"]);
        $this->setBloodfx($data["bloodfx"]);
    }

    /**
     * @param string $queue
     * @return int
     */
    #[Pure] public function getElo(string $queue): int
    {
        $var = 0;
        $kit = strtolower($queue);

        if($kit === "nodebuff") $var = $this->getNodebuffElo();
        if($kit === "boxing") $var = $this->getBoxingElo();
        if($kit === "gapple") $var = $this->getGappleElo();
        if($kit === "sumo") $var = $this->getSumoElo();
        if($kit === "builduhc") $var = $this->getBuildUHCElo();
        if($kit === "fist") $var = $this->getFistElo();
        if($kit === "combo") $var = $this->getComboElo();
        if($kit === "spleef") $var = $this->getSpleefElo();

        return $var;
    }

    /**
     * @param string $queue
     * @param int $newElo
     */
    public function setElo(string $queue, int $newElo)
    {
        $kit = strtolower($queue);

        if($kit === "nodebuff") $this->setNodebuffElo($newElo);
        if($kit === "boxing") $this->setBoxingElo($newElo);
        if($kit === "gapple") $this->setGappleElo($newElo);
        if($kit === "sumo") $this->setSumoElo($newElo);
        if($kit === "builduhc") $this->setBuildUHCElo($newElo);
        if($kit === "fist") $this->setFistElo($newElo);
        if($kit === "combo") $this->setComboElo($newElo);
        if($kit === "spleef") $this->setSpleefElo($newElo);

        return;
    }

    # ------------------------------------------------------------------------------------------------------------ #
    #                                        SERVER-INTERNAL PART                                                  #
    # ------------------------------------------------------------------------------------------------------------ #

    /**
     * @param string $rank
     * @return void
     */
    public function initializeJoin(string $rank)
    {
        $player = $this->player;

        $this->teleportPlayer($player, "lobby", true, true);

        $player->setDisplayName(Utils::getPlayerName($player));
        $player->setNameTag(Utils::formatNameTag($player, $rank));

        PermissionInfo::setPermission($player, $rank);
        Utils::sendSpawnMessage($player);
        DefaultKits::sendSpawnKit($player);
        $this->setNormalNameTag();

        PracticeCore::getInstance()->getScoreboardManager()->sendSpawnScoreboard($player);
        PracticeCore::getInstance()->getPlayerClicksInfo()->addToArray($player);
    }

    /**
     * @param Player $player
     * @param string $place
     * @param bool $giveKit
     * @param bool $sendScoreboard
     */
    public function teleportPlayer(Player $player, string $place, bool $giveKit = false, bool $sendScoreboard = false)
    {
        switch ($place) {
            case "lobby":
                $world = PracticeCore::getInstance()->getServer()->getLevelByName(PracticeCore::LOBBY);

                $player->teleport(new Position(PracticeCore::LOBBY_X, PracticeCore::LOBBY_Y, PracticeCore::LOBBY_Z, $world));

                if ($giveKit === true) {
                    DefaultKits::sendSpawnKit($player);
                }

                if ($sendScoreboard === true) {
                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player);
                }
                break;
        }
    }

    public function setnoTarget(): void
    {
        $this->target = null;

        if ($this->getPlayer()->isOnline() && $this->hasTarget()) {
            $target = $this->getTarget();
            $targetSession = PlayerHandler::getSession($target);
            if ($target !== null && $target->isOnline()) $targetSession->setnoTarget();
        }
    }

    /**
     * @return bool
     */
    public function hasTarget(): bool
    {
        if ($this->target === null) return false;
        return true;
    }

    /**
     * @return Player|null
     */
    public function getTarget(): ?Player
    {
        return Server::getInstance()->getPlayerExact($this->target);
    }

    /**
     * @param string $player
     */
    public function setTarget(string $player): void
    {
        $this->target = $player;
    }

    /**
     * @param DuelInfo $winner
     * @param DuelInfo $loser
     * @param bool $draw
     */
    public function addToDuelHistory(DuelInfo $winner, DuelInfo $loser, bool $draw = false): void
    {
        $this->duelHistory[] = ['winner' => $winner, 'loser' => $loser, 'draw' => $draw];
    }

    /**
     * @param int $id
     *
     * @return array|null
     */
    public function getDuelInfo(int $id): ?array
    {
        $result = null;

        if (isset($this->duelHistory[$id])) {
            $result = $this->duelHistory[$id];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDuelHistory(): array
    {
        return $this->duelHistory;
    }

    public function setParty(bool $value)
    {
        $this->party = $value;
    }

    public function isInSpawn(): bool
    {
        return Utils::areLevelsEqual($this->getPlayer()->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY));
    }

    public function removeFromDuelQueue($msg = false)
    {
        if ($this->isInDuelQueue()) {
            PracticeCore::getDuelManager()->removeFromQueue($this->getPlayer(), $msg);
            return;
        }

        return;
    }

    #[Pure] public function isInDuelQueue(): bool
    {
        $duelManager = PracticeCore::getDuelManager();
        return $duelManager->isInQueue($this->getPlayer());
    }

    public function setKit(AbstractKit $kit)
    {
        $this->kit = $kit;
    }

    public function getKit(): AbstractKit
    {
        return $this->kit;
    }

    public function setArena(Arena $arena)
    {
        $this->arena = $arena;
    }

    public function getArena(): Arena
    {
        return $this->arena;
    }

    /**
     * @param string|FFAArena $arena
     * @return bool
     *
     * Teleports the player to the ffa arena.
     */
    public function teleportToFFAArena(FFAArena|string $arena): bool
    {
        if ($this->isFrozen() or $this->isInDuel()) {
            return false;
        }

        $arenaHandler = PracticeCore::getArenaManager();
        $arena = ($arena instanceof FFAArena) ? $arena : $arenaHandler->getArena($arena);

        if ($arena !== null and $arena instanceof FFAArena and $arena->isOpen()) {
            PlayerExtensions::enableFlying($this->getPlayer(), false);

            $this->arena = $arena;

            PlayerExtensions::clearInventory($this->getPlayer());

            $arena->teleportPlayer($this->getPlayer());

            PracticeCore::getScoreboardManager()->sendFFAScoreboard($this->getPlayer());

            return true;
        }

        return false;
    }

    /**
     * Sets the normal name tag.
     */
    public function setNormalNameTag(): void
    {
        $name = TextFormat::WHITE . $this->getDeviceOS(true) . TextFormat::DARK_GRAY . " | " . Utils::getThemeColor() . $this->getInput(true);

        $this->player->setScoreTag($name);
        $this->tagType = self::TAG_NORMAL;
    }

    /**
     * Sets the combat name tag.
     */
    public function setCombatNameTag(): void
    {
        $name = TextFormat::WHITE . "CPS: " . Utils::getThemeColor() .  count($this->cps) . TextFormat::DARK_GRAY . " | " . TextFormat::WHITE . "Ping: ". Utils::getThemeColor() . $this->player->getPing();

        $this->player->setScoreTag($name);
        $this->tagType = self::TAG_COMBAT;
    }

    /**
     * @return bool
     */
    #[Pure] public function isPe(): bool
    {
        $deviceOS = $this->getDeviceOS();

        $invalidDevices = [
            self::PS4 => true,
            self::XBOX => true,
            self::WINDOWS_10 => true,
            self::LINUX => true,
        ];

        if(isset($invalidDevices[$deviceOS]))
        {
            return false;
        }

        if($deviceOS === self::TOUCH)
        {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getClientRandomID(): int
    {
        return intval($this->clientRandomID);
    }

    /**
     * @param bool $strval
     * @return int|string
     */
    #[Pure] public function getDeviceOS(bool $strval = false): int|string
    {
        $osVal = intval(PracticeCore::getPlayerDeviceInfo()->getPlayerOs($this->getPlayer()));
        $result = $strval ? 'Unknown' : $osVal;

        if ($strval === true and isset($this->deviceOSVals[$osVal]))
        {
            $result = $this->deviceOSVals[$osVal];
        }

        return $result;
    }

    /**
     * @param bool $strval
     * @return int|string
     */
    #[Pure] public function getInput(bool $strval = false): int|string
    {
        $input = intval(PracticeCore::getPlayerDeviceInfo()->getPlayerControls($this->getPlayer()));
        $result = ($strval === true) ? 'Unknown' : $input;

        if($strval === true and isset($this->inputVals[$input]))
        {
            $result = $this->inputVals[$input];
        }

        return $result;
    }

    public function onDeath()
    {
        if(!$this->dead)
        {
            $this->dead = true;
            $player = $this->getPlayer();

            $ev = new PlayerDeathEvent($player, $player->getDrops(), null, $player->getXpDropAmount());
            $ev->call();

            $this->setThrowPearl(true);

            $cause = $player->getLastDamageCause();

            $addDeath = false;

            $duel = PracticeCore::getDuelManager()->getDuel($player);

            $skip = false;

            if($cause !== null)
            {
                $causeAction = $cause->getCause();

                if($causeAction === EntityDamageEvent::CAUSE_SUICIDE
                    || $causeAction === EntityDamageEvent::CAUSE_VOID
                    || $causeAction === EntityDamageEvent::CAUSE_LAVA
                    || $causeAction === EntityDamageEvent::CAUSE_DROWNING
                    || $causeAction === EntityDamageEvent::CAUSE_SUFFOCATION
                    || $causeAction === EntityDamageEvent::CAUSE_FIRE
                    || $causeAction === EntityDamageEvent::CAUSE_FIRE_TICK){

                    if($duel !== null)
                    {
                        $duel->setEnded($duel->getOpponent($player));
                        $skip = true;
                    }
                }

                if(!$skip)
                {
                    $duelWinner = null;

                    if($cause instanceof EntityDamageByEntityEvent)
                    {
                        $killer = $cause->getDamager();

                        if($killer !== null)
                        {
                            if($killer instanceof Player)
                            {
                                $killerSession = PlayerHandler::getSession($killer);

                                $killerSession->setThrowPearl(true);

                                if($this->hasTarget())
                                {
                                    Utils::spawnLightning($player);

                                    if($killerSession->isBloodfx())
                                    {
                                        for ($i = 0; $i < 5; $i++)
                                        {
                                            $player->getLevel()->addParticle(new DestroyBlockParticle($player->add(mt_rand(-50, 50) / 100, 1 + mt_rand(-50, 50) / 100, mt_rand(-50, 50) / 100), Block::get(BlockIds::REDSTONE_BLOCK)));
                                        }
                                    }

                                    $this->setCombat(false);
                                    $killerSession->setCombat(false);

                                    $player->sendMessage(TextFormat::GREEN . $player->getDisplayName() . TextFormat::GRAY . ' was killed by ' . TextFormat::RED . $killer->getDisplayName());
                                    $killer->sendMessage(TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . ' was killed by ' . TextFormat::GREEN . $killer->getDisplayName());

                                    $killerSession->addKill();
                                    PlayerExtensions::revivePlayer($killer);

                                    // TODO:
                                    if(Utils::areLevelsEqual($killer->getLevel(), Server::getInstance()->getLevelByName("nodebuff-ffa")))
                                    {
                                        $kit = PracticeCore::getKits()->getKit("nodebuff");
                                        $kit->giveTo($killer, false);
                                    }

                                    if(Utils::areLevelsEqual($killer->getLevel(), Server::getInstance()->getLevelByName("sumo-ffa")))
                                    {
                                        $kit = PracticeCore::getKits()->getKit("sumo");
                                        $kit->giveTo($killer, false);
                                    }

                                    PracticeCore::getScoreboardManager()->sendFFAScoreboard($killer);

                                    $addDeath = true;
                                }elseif($duel !== null && $duel->isPlayer($killer))
                                {
                                    $duelWinner = $killer;
                                }
                            }
                        }
                    }

                    if($duel !== null && $duelWinner !== null)
                    {
                        $duel->setEnded($duelWinner);
                        $winnerSession = PlayerHandler::getSession($duelWinner);

                        $winnerSession->addKill();
                        $addDeath = true;
                    }

                    if($addDeath){
                        $this->addDeath();
                    }
                }
            }

            $this->dead = false;
            PlayerExtensions::setXpAndProgress($player, 0, 0.0);
            $player->doCloseInventory();
        }
    }

    public function respawn()
    {
        $player = $this->getPlayer();

        if($player->isFlying())
        {
            $player->setFlying(false);
        }

        PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player);
        $player->teleport(new Position(PracticeCore::LOBBY_X, PracticeCore::LOBBY_Y, PracticeCore::LOBBY_Z, PracticeCore::getInstance()->getServer()->getLevelByName(PracticeCore::LOBBY)));
        $this->dead = false;
        DefaultKits::sendSpawnKit($player);
    }
}