<?php

namespace DidntPot\kits;

use DidntPot\kits\types\Boxing;
use DidntPot\kits\types\BuildUHC;
use DidntPot\kits\types\Combo;
use DidntPot\kits\types\Fist;
use DidntPot\kits\types\Gapple;
use DidntPot\kits\types\NoDebuff;
use DidntPot\kits\types\Resistance;
use DidntPot\kits\types\Spleef;
use DidntPot\kits\types\Sumo;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\utils\Config;

class Kits
{
    public const GAPPLE = 'gapple';
    public const SUMO = 'sumo';
    public const FIST = 'fist';
    public const NODEBUFF = 'nodebuff';
    public const COMBO = 'combo';
    public const BUILDUHC = 'builduhc';
    public const SPLEEF = 'spleef';
    public const BOXING = 'boxing';
    public const RESISTANCE = 'resistance';

    /* @var string */
    private $path;

    /* @var Config */
    private Config $config;

    /* @var AbstractKit[]|array */
    private $kits = [];

    public function __construct(PracticeCore $core)
    {
        $this->path = $core->getDataFolder() . 'kit-knockback.yml';

        $this->initConfig();
    }

    /**
     * Initializes the config file.
     */
    private function initConfig(): void
    {
        $this->config = new Config($this->path, Config::YAML, []);

        $this->kits = [
            self::NODEBUFF => new NoDebuff(),
            self::BOXING => new Boxing(),
            self::GAPPLE => new Gapple(),
            self::SUMO => new Sumo(),
            //self::BUILDUHC => new BuildUHC(),
            self::FIST => new Fist(),
            self::COMBO => new Combo(),
            self::SPLEEF => new Spleef(),
            self::RESISTANCE => new Resistance()
        ];

        foreach ($this->kits as $key => $kit) {
            assert($kit instanceof AbstractKit);
            if (!$this->config->exists($key)) {
                $this->config->set($key, $kit->export());
                $this->config->save();
            } else {
                $kitData = $this->config->get($key);

                if (isset($kitData['xkb'], $kitData['ykb'], $kitData['speed'])) {
                    $xKb = floatval($kitData['xkb']);
                    $yKb = floatval($kitData['ykb']);
                    $speed = intval($kitData['speed']);
                    $kit = $kit->setYKB($yKb)->setXKB($xKb)->setSpeed($speed);
                    $this->kits[$key] = $kit;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    #[Pure] public function isKit(string $name): bool
    {
        $kit = $this->getKit($name);
        return $kit !== null;
    }

    /**
     * @param string $name
     * @return AbstractKit|null
     */
    #[Pure] public function getKit(string $name): ?AbstractKit
    {
        if (isset($this->kits[$name])) {
            return $this->kits[$name];
        } else {
            foreach ($this->kits as $kit) {
                if ($name === $kit->getName()) {
                    return $kit;
                }
            }
        }

        return null;
    }

    /**
     * @param bool $asString
     * @return array
     */
    #[Pure] public function getKits(bool $asString = false): array
    {
        $result = [];

        foreach ($this->kits as $kit) {
            $name = $kit->getName();

            if ($asString === true)
                $result[] = $name;
            else $result[] = $kit;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getKitsLocal(): array
    {
        return array_keys($this->kits);
    }
}