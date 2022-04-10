<?php

namespace DidntPot\game\hologram;

use DidntPot\game\hologram\types\EloHologram;
use DidntPot\game\hologram\types\StatsHologram;
use DidntPot\kits\AbstractKit;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class HologramHandler
{
    /* @var array */
    private $eloLeaderboards;

    /** @var array */
    private $statsLeaderboards;

    /* @var Server */
    private $server;

    /* @var string */
    private $dataFolder;

    /* @var EloHologram|null */
    private $eloLeaderboardHologram;

    /** @var StatsHologram|null */
    private $statsLeaderboardHologram;

    /* @var Config */
    private $leaderboardConfig;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $this->eloLeaderboards = [];
        $this->statsLeaderboards = [];
        $this->server = $core->getServer();
        $this->dataFolder = $core->getDataFolder();
        $this->eloLeaderboardHologram = null;
        $this->statsLeaderboardHologram = null;
        $this->initConfig();
    }

    private function initConfig(): void
    {
        $keys = ['elo', 'stats'];

        $arr = [];

        foreach($keys as $key)
        {
            $arr[$key] = [
                'x' => NULL,
                'y' => NULL,
                'z' => NULL,
                'level' => NULL
            ];
        }

        $this->leaderboardConfig = new Config($this->dataFolder . '/leaderboard-hologram.yml', Config::YAML);

        if(!$this->leaderboardConfig->exists('data'))
        {
            $this->leaderboardConfig->set('data', $arr);
            $this->leaderboardConfig->save();
        } else {
            $data = $this->leaderboardConfig->get('data');

            $loaded = $this->loadData($data);

            if($loaded !== null)
            {
                /** @var Level $level */
                $level = $loaded['level'];

                $this->eloLeaderboardHologram = new EloHologram(
                    new Vector3($loaded['x'], $loaded['y'], $loaded['z']),
                    $level,
                    false,
                    $this
                );

            } else {
                if(isset($data['stats']))
                {
                    $statsLoaded = $this->loadData($data['stats']);

                    if($statsLoaded !== null)
                    {
                        $this->statsLeaderboardHologram = new StatsHologram(
                            new Vector3($statsLoaded['x'], $statsLoaded['y'], $statsLoaded['z']),
                            $statsLoaded['level'],
                            false,
                            $this
                        );
                    }
                }

                if(isset($data['elo']))
                {
                    $eloLoaded = $this->loadData($data['elo']);

                    if($eloLoaded !== null)
                    {
                        $this->eloLeaderboardHologram = new EloHologram(
                            new Vector3($eloLoaded['x'], $eloLoaded['y'], $eloLoaded['z']),
                            $eloLoaded['level'],
                            false,
                            $this
                        );
                    }

                }
            }
        }
    }

    /**
     * @param $data
     * @return array|null
     */
    private function loadData($data): ?array
    {
        $result = null;

        if(isset($data['x'], $data['y'], $data['z'], $data['level']))
        {
            $x = $data['x'];
            $y = $data['y'];
            $z = $data['z'];
            $levelName = $data['level'];

            if(is_int($x) and is_int($y) and is_int($z) and is_string($levelName) and ($theLevel = $this->server->getLevelByName($levelName)) !== null and $theLevel instanceof Level)
            {
                $result = [
                    'x' => $x,
                    'y' => $y,
                    'z' => $z,
                    'level' => $theLevel
                ];
            }
        }

        return $result;
    }

    public function reloadEloLeaderboards(): void
    {
        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeSelectRaw("SELECT * FROM PlayerElo", [], function(array $rows)
        {
            $data = [];

            foreach ($rows as $row)
            {
                $name = $row['name'];
                $data["NoDebuff"][$name] = $row['NoDebuff'];
                $data["Boxing"][$name] = $row['Boxing'];
                $data["Gapple"][$name] = $row['Gapple'];
                $data["Sumo"][$name] = $row['Sumo'];
                $data["BuildUHC"][$name] = $row['BuildUHC'];
                $data["Fist"][$name] = $row['Fist'];
                $data["Combo"][$name] = $row['Combo'];
                $data["Spleef"][$name] = $row['Spleef'];
            }

            foreach ($data as $key => $elo)
            {
                arsort($elo);
                $data[$key] = $elo;
            }

            $this->setEloLeaderboards($data);
        });
    }

    public function reloadStatsLeaderboards(): void
    {
        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeSelectRaw("SELECT * FROM PlayerStats", [], function(array $rows)
        {
            $data = [];

            foreach ($rows as $row)
            {
                $name = $row['name'];
                $data["kills"][$name] = $kills = $row['kills'];
                $data["deaths"][$name] = $deaths = $row['deaths'];
                $data["kdr"][$name] = round($kills / ($deaths === 0 ? 1 : $deaths));
            }

            foreach ($data as $key => $stat)
            {
                arsort($stat);
                $data[$key] = $stat;
            }

            $this->setStatsLeaderboards($data);
        });
    }

    /**
     * @param array $eloLeaderboards
     */
    public function setEloLeaderboards(array $eloLeaderboards): void
    {
        $this->eloLeaderboards = $eloLeaderboards;

        if($this->eloLeaderboardHologram instanceof EloHologram)
        {
            $this->eloLeaderboardHologram->updateHologram();
        }
    }

    /**
     * @param array $statsLeaderboards
     */
    public function setStatsLeaderboards(array $statsLeaderboards): void
    {
        $this->statsLeaderboards = $statsLeaderboards;

        if($this->statsLeaderboardHologram instanceof StatsHologram)
        {
            $this->statsLeaderboardHologram->updateHologram();
        }
    }

    /**
     * @param Player $player
     * @param bool $elo
     */
    public function setLeaderboardHologram(Player $player, bool $elo = true): void
    {
        $vec3 = $player->asVector3();
        $level = $player->getLevel();

        if($elo)
        {
            $key = 'elo';
            if($this->eloLeaderboardHologram !== null)
            {
                $this->eloLeaderboardHologram->moveHologram($vec3, $level);
            } else
            {
                $this->eloLeaderboardHologram = new EloHologram($vec3, $level, true, $this);
            }
        } else
        {
            $key = 'stats';
            if($this->statsLeaderboardHologram !== null)
            {
                $this->statsLeaderboardHologram->moveHologram($vec3, $level);
            } else
            {
                $this->statsLeaderboardHologram = new StatsHologram($vec3, $level, true, $this);
            }
        }

        $data = $this->leaderboardConfig->get('data');

        if(isset($data['x'], $data['y'], $data['z'], $data['level']))
        {
            unset($data['x'], $data['y'], $data['z'], $data['level']);
        }

        $data[$key] = [
            'x' => (int)$vec3->x,
            'y' => (int)$vec3->y,
            'z' => (int)$vec3->z,
            'level' => $level->getName()
        ];

        $this->leaderboardConfig->setAll(['data' => $data]);
        $this->leaderboardConfig->save();
    }

    /**
     * @param string|AbstractKit $queue
     * @return array
     */
    #[Pure] public function getEloLeaderboardOf(AbstractKit|string $queue = 'global'): array
    {
        $result = [];
        $queue = $queue instanceof AbstractKit ? $queue->getLocalizedName() : $queue;

        if(isset($this->eloLeaderboards[$queue]))
        {
            $result = $this->eloLeaderboards[$queue];
        }

        return $result;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getStatsLeaderboardOf(string $key): array
    {
        $result = [];

        if(isset($this->statsLeaderboards[$key]))
        {
            $result = $this->statsLeaderboards[$key];
        }

        return $result;
    }

    /**
     *
     * @param bool $elo
     *
     * @return array
     */
    #[Pure] public function getLeaderboardKeys(bool $elo = true): array
    {
        $result = ['kills', 'deaths', 'kdr'];

        if($elo)
        {
            $result = PracticeCore::getKits()->getKitsLocal();
            $result[] = 'global';
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getEloLeaderboards(): array
    {
        return $this->eloLeaderboards;
    }

    /**
     * @return array
     */
    public function getStatsLeaderboards(): array
    {
        return $this->statsLeaderboards;
    }

    /**
     * @param string $player
     * @param string $key
     * @param bool $elo
     * @return null|int
     */
    public function getRankingOf(string $player, string $key, bool $elo = true): ?int
    {
        $list = $this->eloLeaderboards;

        if(!$elo)
        {
            $list = $this->statsLeaderboards;
        }

        if(isset($list[$key][$player]))
        {
            $leaderboardSet = $list[$key];
            $searched = array_keys($leaderboardSet);
            $result = array_search($player, $searched);

            if(is_int($result))
            {
                return $result + 1;
            }
        }

        return null;
    }
}