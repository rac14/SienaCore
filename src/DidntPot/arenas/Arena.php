<?php

namespace DidntPot\arenas;

use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

abstract class Arena
{
    const TYPE_FFA = 0;
    const TYPE_EVENT = 1;
    const TYPE_DUELS = 2;

    /**
     * @param string $arenaName
     * @param array $array
     * @return Arena|EventArena|FFAArena|null
     */
    public static function parseArena(string $arenaName, array $array): Arena|EventArena|FFAArena|null
    {
        $result = null;

        $type = self::TYPE_FFA;

        if (isset($array['type'])) {
            $type = (int)$array['type'];
        }

        if ($type === self::TYPE_FFA) {
            if (isset($array['kit'], $array['center'], $array['level'])) {
                $kit = strval($array['kit']);
                $centerData = $array['center'];
                $center = null;
                $level = strval($array['level']);

                $spawn = null;

                $spawnData = $centerData;

                if (isset($array['spawn'])) {
                    $spawnData = $array['spawn'];
                }

                if (isset($centerData['x'], $centerData['y'], $centerData['z'])) {
                    $x = intval($centerData['x']);
                    $y = intval($centerData['y']);
                    $z = intval($centerData['z']);
                    $center = new Vector3($x, $y, $z);
                }

                if (isset($spawnData['x'], $spawnData['y'], $spawnData['z'])) {
                    $x = intval($spawnData['x']);
                    $y = intval($spawnData['y']);
                    $z = intval($spawnData['z']);
                    $spawn = new Vector3($x, $y, $z);
                }

                if ($center !== null) {
                    $result = new FFAArena($arenaName, $center, $spawn, Server::getInstance()->getLevelByName($level), $kit);
                }
            }
        } elseif ($type === self::TYPE_EVENT) {
            if (isset($array['kit'], $array['center'], $array['spawn'], $array['p1'], $array['p2'], $array['level'])) {
                $kit = strval($array['kit']);
                $level = strval($array['level']);

                $vec3Data = ['center', 'spawn', 'p1', 'p2'];
                $vec3Result = [];
                foreach ($vec3Data as $key) {
                    $data = $array[$key];
                    if (isset($data['x'], $data['y'], $data['z'])) {
                        $x = intval($data['x']);
                        $y = intval($data['y']);
                        $z = intval($data['z']);
                        $vec3 = new Vector3($x, $y, $z);
                        $vec3Result[$key] = $vec3;
                    }
                }

                if (array_keys($vec3Result) === $vec3Data) {
                    $result = new EventArena($arenaName, $vec3Result['center'], $level, $kit, $vec3Result['p1'], $vec3Result['p2'], $vec3Result['spawn']);
                }
            }
        }

        return $result;
    }

    abstract public function getName(): string;

    /**
     * @return array
     */
    abstract public function getData(): array;

    /**
     * @param Player $player
     * @param bool $value
     */
    abstract public function teleportPlayer(Player $player, bool $value = true): void;
}