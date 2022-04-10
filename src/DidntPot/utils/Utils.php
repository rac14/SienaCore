<?php

namespace DidntPot\utils;

use DidntPot\data\Database;
use DidntPot\forms\types\CustomForm;
use DidntPot\game\level\AsyncDeleteLevel;
use DidntPot\game\level\PracticeChunkLoader;
use DidntPot\player\Human;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\scoreboard\ScoreboardUtils;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\SplashPotion;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ZipArchive;

/**
 *
 */
class Utils
{
    const CLASSIC_DUEL_GEN = "duel_classic";
    const CLASSIC_SUMO_GEN = "sumo_classic";
    const CLASSIC_SPLEEF_GEN = "spleef_classic";

    const RIVER_DUEL_GEN = "duel_river";
    const BURNT_DUEL_GEN = "duel_burnt";

    const N = "N";
    const NE = "NE";
    const SE = "SE";
    const S = "S";
    const E = "E";
    const W = "W";
    const NW = "NW";
    const SW = "SW";

    public const WINDOWS_10 = 7;
    public const IOS = 2;
    public const ANDROID = 1;
    public const WINDOWS_32 = 8;
    public const UNKNOWN = -1;
    public const MAC_EDU = 3;
    public const FIRE_EDU = 4;
    public const GEAR_VR = 5;
    public const HOLOLENS_VR = 6;
    public const DEDICATED = 9;
    public const ORBIS = 10;
    public const NX = 11;

    public const CONTROLS_UNKNOWN = 0;
    public const CONTROLS_MOUSE = 1;
    public const CONTROLS_TOUCH = 2;
    public const CONTROLS_CONTROLLER = 3;

    const LENGTH_CHAT_COOLDOWN = 2;

    const SWISH_SOUNDS = [LevelSoundEventPacket::SOUND_ATTACK => true, LevelSoundEventPacket::SOUND_ATTACK_STRONG => true];

    /**
     * @return string
     */
    public static function getIP(): string
    {
        return "sienamc.net";
    }

    /**
     * @return string
     */
    public static function getIP_Port(): string
    {
        return "sienamc.net:19132";
    }

    /**
     * @return string
     */
    #[Pure] public static function getPrefix(): string
    {
        return self::getThemeColor() . "Siena §7§l» §r";
    }

    /**
     * @param bool $old
     * @return string
     */
    public static function formatTitle(bool $old = false): string
    {
        if ($old === false) return "§r" . self::getThemeColor() . "§l" . PracticeCore::getRegionInfo() . " PRACTICE";
        else return "§r§l" . self::getThemeColor() . "Siena§r §8| §f" . PracticeCore::getRegionInfo() . " Practice";
    }

    /**
     * @param Player $player
     *
     * Sends the spawn message to a specific player on-join.
     */
    public static function sendSpawnMessage(Player $player): void
    {
        $player->sendMessage("§8----------------------------------------");
        $player->sendMessage("§l" . self::getThemeColor() . PracticeCore::getRegionInfo() . " Practice\n\n");

        $player->sendMessage("§e• §7Season: " . self::getThemeColor() . "3 §7(Started " . Utils::getSeasonStartDate() . ")§r\n\n");

        $player->sendMessage("§e• §7Discord: " . self::getDiscord());
        $player->sendMessage("§e• §7Store: " . self::getStore() . "\n\n");

        $player->sendMessage("§e• §7To join a ffa-arena, right click the §agolden sword§7.\n");
        $player->sendMessage("§e• §7To join a bot-queue, right click the §astone sword§7.\n");
        $player->sendMessage("§e• §7To join a queue, right click one of the §aduel swords§7.\n");

        $player->sendMessage("§8----------------------------------------");
    }

    /**
     * @return string
     */
    public static function getSeasonStartDate(): string
    {
        return "January 1st";
    }

    /**
     * @return string
     */
    public static function getDiscord(): string
    {
        return "discord.sienamc.net";
    }

    /**
     * @return string
     */
    public static function getStore(): string
    {
        return "store.sienamc.net";
    }

    /**
     * @return string
     */
    public static function getThemeColor(): string
    {
        return TextFormat::LIGHT_PURPLE;
    }

    /**
     * @return float
     */
    public static function currentTimeMillis(): float
    {
        $time = microtime(true);
        return $time * 1000;
    }

    /**
     * @param int $seconds
     * @return int
     */
    public static function secondsToTicks(int $seconds): int
    {
        return $seconds * 20;
    }

    /**
     * @param int $minutes
     * @return int
     */
    public static function minutesToTicks(int $minutes): int
    {
        return $minutes * 1200;
    }

    /**
     * @param int $hours
     * @return int
     */
    public static function hoursToTicks(int $hours): int
    {
        return $hours * 72000;
    }

    /**
     * @param int $tick
     * @return int
     */
    public static function ticksToSeconds(int $tick): int
    {
        return intval($tick / 20);
    }

    /**
     * @param int $tick
     * @return int
     */
    public static function ticksToMinutes(int $tick): int
    {
        return intval($tick / 1200);
    }

    /**
     * @param int $tick
     * @return int
     */
    public static function ticksToHours(int $tick): int
    {
        return intval($tick / 72000);
    }

    /**
     * @param Player $player
     */
    public static function loadPlayer(Player $player)
    {
        PlayerHandler::createSession($player);

        $session = PlayerHandler::getSession($player);

        $playerData = [
            "rank" => "Player",
            "kills" => 0,
            "deaths" => 0,
            "kdr" => 0,
            "ks" => 0,
            "bestks" => 0
        ];

        $eloData = [
            "NoDebuff" => 1000,
            "Boxing" => 1000,
            "Gapple" => 1000,
            "Sumo" => 1000,
            "BuildUHC" => 1000,
            "Fist" => 1000,
            "Combo" => 1000,
            "Spleef" => 1000
        ];

        $settingsData = [
            "scoreboard" => true,
            "cpscounter" => true,
            "autorekit" => false,
            "autorequeue" => false,
            "bloodfx" => false
        ];

        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeSelect(Database::LOAD_PLAYER_STATS_DATA, ["name" => $player->getName()], function (array $rows) use ($player, $playerData, $session) {
            if (count($rows) > 0 && isset($rows[0])) {
                $loadedData = $rows[0];
                foreach ($loadedData as $key => $data) {
                    if ($data !== null) {
                        $playerData[$key] = $data;
                    }
                }
            }

            $session->setData($playerData);
            if (PlayerHandler::hasSession($player)) $session->initializeJoin($playerData["rank"]);
        });

        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeSelect(Database::LOAD_PLAYER_ELO_DATA, ["name" => $player->getName()], function (array $rows) use ($player, $eloData, $session) {
            if (count($rows) > 0 && isset($rows[0])) {
                $loadedData = $rows[0];
                foreach ($loadedData as $key => $data) {
                    if ($data !== null) {
                        $eloData[$key] = $data;
                    }
                }
            }

            $session->setEloData($eloData);
        });

        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeSelect(Database::LOAD_PLAYER_SETTINGS_DATA, ["name" => $player->getName()], function (array $rows) use ($player, $settingsData, $session) {
            if (count($rows) > 0 && isset($rows[0])) {
                $loadedData = $rows[0];
                foreach ($loadedData as $key => $data) {
                    if ($data !== null) {
                        $settingsData[$key] = $data;
                    }
                }
            }

            $session->setSettingsData($settingsData);
        });

        Utils::sendRulesForm($player);

        foreach(Server::getInstance()->getOnlinePlayers() as $players)
        {
            if(ScoreboardUtils::isPlayerSetSpawnScoreboard($players))
            {
                PracticeCore::getScoreboardManager()->sendSpawnScoreboard($players);
            }
        }
    }

    /**
     * @param Player $player
     *
     * Sends the rules form to a specific player on-join.
     */
    public static function sendRulesForm(Player $player): void
    {
        $form = new CustomForm(function ($player, $data = null): void {
            switch ($data) {
                default:
                    $player->setImmobile(false);
                    $player->sendTitle("§r§6§k|§r " . self::getThemeColor() . "Siena §9S3 §r§6§k|§r", "§6§l»§r §fWelcome " . $player->getName() . " §6§l«§r", 20, 40, 20);
                    Utils::teleportSound($player);
                    Utils::sendDragonEffect([$player], $player->getX(), $player->getY(), $player->getZ(), $player->getLevel());
                break;
            }
        });

        $form->setTitle("§eServer Rules§r");

        $form->addLabel(
            "§fServer rules scale, meaning these punishments will always increase upon repeated offenses. Breaking gameplay rules will result in a punishment in-game.\n\n" .
            "§e1. Unfair Advantage: §fUse of a program or cheat(s) to gain an unfair advantage.\n" .
            "§e2. Ban Evasion: §fUsing an alternate account to bypass an active ban.\n" .
            "§e3. Exploiting Bugs: §fAbusing/or using a server bug on purpose.\n" .
            "§e4. Lag Switching: §fPurposely lagging connection to gain an advantage.\n" .
            "§e5. Elo Boosting: §fBoosting another player’s statistics.\n" .
            "§e6. Camping: §fBuilding up/digging down and remaining there for a long period of time.\n" .
            "§e7. Running: §fExcessively Running during matches to stall matches.\n" .
            "§e8. Mouse Abuse: §fAbusing your mouse to gain CPS.\n" .
            "§e9. VPN Usage: §fHaving a VPN enabled while on the server.\n" .
            "§e10. Debounce Time: §fHaving a debounce time less than 10." .

            "\n\n§o§6For questions or concerns refer to " . self::getDiscord() . "."
        );

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public static function savePlayer(Player $player)
    {
        $name = $player->getName();

        $session = PlayerHandler::getSession($player);
        $duelHandler = PracticeCore::getDuelManager();

        if (PlayerHandler::hasSession($player)) {
            $playerData = $session->getData();
            $eloData = $session->getEloData();
            $settingsData = $session->getSettingsData();
        } else {
            return;
        }

        if ($session->hasParty()) {
            $partyManager = PracticeCore::getPartyManager();
            $party = $partyManager->getPartyFromPlayer($session->getPlayer());
            $party->removePlayer($session->getPlayer());
        }

        if ($session->isInDuelQueue()) $session->removeFromDuelQueue(false);

        if($session->isCombat())
        {
            if($session->hasTarget())
            {
                $cause = $session->getTarget();
                $targetSession = PlayerHandler::getSession($cause);

                if($cause instanceof Player && $cause->isOnline())
                {
                    $targetSession->addKill();
                    $targetSession->setCombat(false);
                    $targetSession->setThrowPearl(true);

                    Utils::spawnLightning($player);

                    if($targetSession->isBloodfx())
                    {
                        for ($i = 0; $i < 5; $i++)
                        {
                            $player->getLevel()->addParticle(new DestroyBlockParticle($player->add(mt_rand(-50, 50) / 100, 1 + mt_rand(-50, 50) / 100, mt_rand(-50, 50) / 100), Block::get(BlockIds::REDSTONE_BLOCK)));
                        }
                    }

                    $cause->sendMessage(TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . ' was killed by ' . TextFormat::GREEN . $cause->getDisplayName());

                    if(Utils::areLevelsEqual($cause->getLevel(), Server::getInstance()->getLevelByName("nodebuff-ffa")))
                    {
                        $kit = PracticeCore::getKits()->getKit("nodebuff");
                        $kit->giveTo($cause, false);
                    }

                    if(Utils::areLevelsEqual($cause->getLevel(), Server::getInstance()->getLevelByName("sumo-ffa")))
                    {
                        $kit = PracticeCore::getKits()->getKit("sumo");
                        $kit->giveTo($cause, false);
                    }

                    PracticeCore::getScoreboardManager()->sendFFAScoreboard($cause);
                }
            }

            $session->addDeath();
            $session->setCombat(false);
        }

        if ($session->isInDuel()) {
            $duel = $duelHandler->getDuel($player);
            $opponent = $duel->getOpponent($player);

            if ($duel->isRunning() && $opponent !== null)
            {
                $duel->setEnded($opponent);
            } elseif ($duel->isCountingDown())
            {
                $duel->setEnded(null, false);
            }
        }

        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeGeneric(Database::SAVE_PLAYER_STATS_DATA,
            [
                "name" => $name,
                "rank" => $playerData["rank"],
                "kills" => $playerData["kills"],
                "deaths" => $playerData["deaths"],
                "kdr" => $playerData["kdr"],
                "ks" => $playerData["ks"],
                "bestks" => $playerData["bestks"]
            ]
        );

        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeGeneric(Database::SAVE_PLAYER_ELO_DATA,
            [
                "name" => $name,
                "NoDebuff" => $eloData["NoDebuff"],
                "Boxing" => $eloData["Boxing"],
                "Gapple" => $eloData["Gapple"],
                "Sumo" => $eloData["Sumo"],
                "BuildUHC" => $eloData["BuildUHC"],
                "Fist" => $eloData["Fist"],
                "Combo" => $eloData["Combo"],
                "Spleef" => $eloData["Spleef"]
            ]
        );

        PracticeCore::getInstance()->getDatabase()->getDatabase()->executeGeneric(Database::SAVE_PLAYER_SETTINGS_DATA,
            [
                "name" => $name,
                "scoreboard" => $settingsData["scoreboard"],
                "cpscounter" => $settingsData["cpscounter"],
                "autorekit" => $settingsData["autorekit"],
                "autorequeue" => $settingsData["autorequeue"],
                "bloodfx" => $settingsData["bloodfx"],
            ]
        );

        $duelHandler->getRequestHandler()->removeAllRequestsWith($player);

        ScoreboardUtils::removeScoreboard($player);
        PracticeCore::getInstance()->getPlayerClicksInfo()->removeFromArray($player);
        PlayerHandler::removeSession($player);
    }

    /**
     * @param $player
     * @return bool
     */
    public static function isPlayer($player): bool
    {
        return !is_null(self::getPlayer($player));
    }

    /**
     * @param $info
     * @return Player|null
     */
    public static function getPlayer($info): ?Player
    {
        $result = null;
        $player = self::getPlayerName($info);

        if ($player === null) {
            return $result;
        }

        $player = Server::getInstance()->getPlayer($player);

        if ($player instanceof Player) {
            $result = $player;
        }

        return $result;
    }

    /**
     * @param $player
     * @return string|null
     */
    #[Pure] public static function getPlayerName($player): ?string
    {
        $result = null;

        if (isset($player) and !is_null($player)) {
            if ($player instanceof Player) {
                $result = $player->getName();
            } elseif (is_string($player)) {
                $result = $player;
            }
        }

        return $result;
    }

    /**
     * @param $player
     * @return string|null
     */
    public static function getPlayerDisplayName($player): ?string
    {
        $result = null;

        if (isset($player) and !is_null($player)) {
            if ($player instanceof Player) {
                $result = $player->getDisplayName();
            } elseif (is_string($player)) {
                $p = self::getPlayer($player);
                if (!is_null($p)) {
                    $result = self::getPlayerDisplayName($p);
                }
            }
        }

        return $result;
    }

    /**
     * @param array|string[] $excludedColors
     *
     * @return string
     */
    public static function randomColor(array $excludedColors = []): string
    {
        $array = [
            TextFormat::DARK_PURPLE => true,
            TextFormat::GOLD => true,
            TextFormat::RED => true,
            TextFormat::GREEN => true,
            TextFormat::LIGHT_PURPLE => true,
            TextFormat::AQUA => true,
            TextFormat::DARK_RED => true,
            TextFormat::DARK_AQUA => true,
            TextFormat::BLUE => true,
            TextFormat::GRAY => true,
            TextFormat::DARK_GREEN => true,
            TextFormat::BLACK => true,
            TextFormat::DARK_BLUE => true,
            TextFormat::DARK_GRAY => true,
            TextFormat::YELLOW => true,
            TextFormat::WHITE => true
        ];

        $array2 = $array;
        foreach ($excludedColors as $c) {
            if (isset($array[$c]))
                unset($array[$c]);
        }

        if (count($array) === 0) $array = $array2;

        $size = count($array) - 1;
        $keys = array_keys($array);

        return (string)$keys[mt_rand(0, $size)];
    }

    /**
     * @param $player
     * @return string|null
     */
    public static function getFacingDirection($player): ?string
    {
        $yaw = $player->getYaw();
        $direction = ($yaw - 180) % 360;
        if ($direction < 0) $direction += 360;
        if (0 <= $direction && $direction < 22.5) return self::N;
        elseif (22.5 <= $direction && $direction < 67.5) return self::NE;
        elseif (67.5 <= $direction && $direction < 112.5) return self::E;
        elseif (112.5 <= $direction && $direction < 157.5) return self::SE;
        elseif (157.5 <= $direction && $direction < 202.5) return self::S;
        elseif (202.5 <= $direction && $direction < 247.5) return self::SW;
        elseif (247.5 <= $direction && $direction < 292.5) return self::W;
        elseif (292.5 <= $direction && $direction < 337.5) return self::NW;
        elseif (337.5 <= $direction && $direction < 360.0) return self::N;
        else return null;
    }

    /**
     * @param int|string $index - Int or string.
     * @return int|string
     *
     * Converts the armor index based on its types.
     */
    public static function convertArmorIndex(int|string $index): int|string
    {
        if (is_string($index)) {
            return match (strtolower($index)) {
                "boots" => 3,
                "leggings" => 2,
                "chestplate", "chest" => 1,
                "helmet" => 0,
                default => 0,
            };

        }

        return match ($index % 4) {
            0 => "helmet",
            1 => "chestplate",
            2 => "leggings",
            3 => "boots",
            default => 0,
        };

    }

    /**
     * @param Item $item
     * @return array
     *
     * Converts an item to an array.
     */
    #[ArrayShape(["id" => "int", "meta" => "int", "count" => "int", "customName" => "string", "enchants" => "array"])] public static function itemToArr(Item $item): array
    {
        $output = [
            "id" => $item->getId(),
            "meta" => $item->getDamage(),
            "count" => $item->getCount()
        ];

        if ($item->hasEnchantments()) {
            $enchantments = $item->getEnchantments();
            $inputEnchantments = [];
            foreach ($enchantments as $enchantment) {
                $inputEnchantments[] = [
                    "id" => $enchantment->getId(),
                    "level" => $enchantment->getLevel()
                ];
            }

            $output["enchants"] = $inputEnchantments;
        }

        if ($item->hasCustomName()) {
            $output["customName"] = $item->getCustomName();
        }

        return $output;
    }

    /**
     * @param array $input
     * @return Item|null
     *
     * Converts an array of data to an item.
     */
    public static function arrToItem(array $input): ?Item
    {
        if (!isset($input["id"], $input["meta"], $input["count"])) {
            return null;
        }

        $item = Item::get($input["id"], $input["meta"], $input["count"]);
        if (isset($input["customName"])) {
            $item->setCustomName($input["customName"]);
        }

        if (isset($input["enchants"])) {
            $enchantments = $input["enchants"];
            foreach ($enchantments as $enchantment) {
                if (!isset($enchantment["id"], $enchantment["level"])) {
                    continue;
                }

                $item->addEnchantment(new EnchantmentInstance(
                    Enchantment::getEnchantment($enchantment["id"]),
                    $enchantment["level"]
                ));
            }
        }

        return $item;
    }

    /**
     * @param Player $player
     * @param bool $keepAir
     * @return array
     */
    public static function inventoryToArray(Player $player, bool $keepAir = false): array
    {
        $result = [];

        $armor = [];
        $items = [];

        $armorInv = $player->getArmorInventory();
        $itemInv = $player->getInventory();

        $armorSize = $armorInv->getSize();

        $armorVals = ['helmet', 'chestplate', 'boots', 'leggings'];

        for ($i = 0; $i < $armorSize; $i++) {
            $item = $armorInv->getItem($i);
            if (isset($armorVals[$i])) {
                $key = $armorVals[$i];
                $armor[$key] = $item;
            }
        }

        $itemSize = $itemInv->getSize();

        for ($i = 0; $i < $itemSize; $i++) {
            $item = $itemInv->getItem($i);
            $exec = !((!$keepAir and $item->getId() === 0));
            if ($exec === true) $items[] = $item;
        }

        $result['armor'] = $armor;
        $result['items'] = $items;

        return $result;
    }

    /**
     * @param string $haystack
     * @param string ...$needles
     * @return bool
     */
    #[Pure] public static function str_contains_vals(string $haystack, string...$needles): bool
    {
        $result = true;

        $size = count($needles);

        if ($size > 0) {
            foreach ($needles as $needle) {
                if (!self::str_contains($needle, $haystack)) {
                    $result = false;
                    break;
                }
            }
        } else $result = false;


        return $result;
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param bool $use_mb
     * @return bool
     */
    public static function str_contains(string $needle, string $haystack, bool $use_mb = false): bool
    {
        $result = false;
        $type = ($use_mb === true) ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);

        if (is_bool($type)) {
            $result = $type;
        } elseif (is_int($type)) {
            $result = $type > -1;
        }
        return $result;
    }

    /**
     * @param string $haystack
     * @param array $values
     * @return string
     */
    public static function str_replace(string $haystack, array $values): string
    {
        $result = $haystack;

        $keys = array_keys($values);

        foreach ($keys as $value) {
            $value = strval($value);
            $replaced = strval($values[$value]);
            if (self::str_contains($value, $haystack)) {
                $result = str_replace($value, $replaced, $result);
            }
        }

        return $result;
    }

    /**
     * @param array $arr
     * @return array
     */
    public static function sort_array(array $arr): array
    {
        if (count($arr) === 1)
            return $arr;

        $middle = intval(count($arr) / 2);

        $left = array_slice($arr, 0, $middle, true);
        $right = array_slice($arr, $middle, null, true);

        $left = self::sort_array($left);
        $right = self::sort_array($right);

        return self::merge($left, $right);
    }

    /**
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    private static function merge(array $arr1, array $arr2): array
    {
        $result = [];

        while (count($arr1) > 0 and count($arr2) > 0) {
            $leftKey = array_keys($arr1)[0];
            $rightKey = array_keys($arr2)[0];
            $leftVal = $arr1[$leftKey];
            $rightVal = $arr2[$rightKey];
            if ($leftVal > $rightVal) {
                $result[$rightKey] = $rightVal;
                $arr2 = array_slice($arr2, 1, null, true);
            } else {
                $result[$leftKey] = $leftVal;
                $arr1 = array_slice($arr1, 1, null, true);
            }
        }

        while (count($arr1) > 0) {
            $leftKey = array_keys($arr1)[0];
            $leftVal = $arr1[$leftKey];
            $result[$leftKey] = $leftVal;
            $arr1 = array_slice($arr1, 1, null, true);
        }

        while (count($arr2) > 0) {
            $rightKey = array_keys($arr2)[0];
            $rightVal = $arr2[$rightKey];
            $result[$rightKey] = $rightVal;
            $arr2 = array_slice($arr2, 1, null, true);
        }

        return $result;
    }

    /**
     * @param EffectInstance $instance
     * @param int|null $duration - The input duration of the effect.
     * @return array
     *
     * Converts an effect instance to an array.
     */
    #[ArrayShape(["id" => "int", "amplifier" => "int", "duration" => "int"])] public static function effectToArr(EffectInstance $instance, ?int $duration = null): array
    {
        return [
            "id" => $instance->getId(),
            "amplifier" => $instance->getAmplifier(),
            "duration" => $duration ?? $instance->getDuration()
        ];
    }

    /**
     * @param $input
     * @return EffectInstance|null
     *
     * Converts an array to an effect instance.
     */
    public static function arrToEffect($input): ?EffectInstance
    {
        if (!is_array($input) || !isset($input["id"], $input["amplifier"], $input["duration"])) {
            return null;
        }

        return new EffectInstance(
            Effect::getEffect($input["id"]),
            $input["duration"],
            $input["amplifier"]
        );
    }

    /**
     * @param $needle
     * @param array $haystack
     * @param bool $strict
     * @return false|int|string
     */
    public static function arr_indexOf($needle, array $haystack, bool $strict = false): bool|int|string
    {
        $index = array_search($needle, $haystack, $strict);

        if (is_bool($index) and $index === false)
            $index = -1;

        return $index;
    }

    /**
     * @param array $arr
     * @param array $values
     * @return array
     */
    #[Pure] public static function arr_replace_values(array $arr, array $values): array
    {
        $valuesKeys = array_keys($values);

        foreach ($valuesKeys as $key) {
            $value = $values[$key];

            if (self::arr_contains_value($key, $arr)) {
                $keys = array_keys($arr);

                foreach ($keys as $editedArrKey) {
                    $origVal = $arr[$editedArrKey];
                    if ($origVal === $key)
                        $arr[$editedArrKey] = $value;

                }
            }
        }

        return $arr;
    }

    /**
     * @param $needle
     * @param array $haystack
     * @param bool $strict
     * @return bool
     */
    public static function arr_contains_value($needle, array $haystack, bool $strict = TRUE): bool
    {
        return in_array($needle, $haystack, $strict);
    }

    /**
     * @param string $input
     * @param string ...$tests
     * @return bool
     */
    public static function equals_string(string $input, string...$tests): bool
    {
        $result = false;

        foreach ($tests as $test) {
            if ($test === $input) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param int $count
     * @return int
     */
    public static function getProperCount(int $count): int
    {
        return ($count <= 0 ? 1 : $count);
    }

    /**
     * @param string $level
     */
    public static function loadLevel(string $level): void
    {
        $server = Server::getInstance();
        if (!$server->isLevelLoaded($level) and !self::str_contains('.', $level))
            $server->loadLevel($level);
    }

    /**
     * @param Position $pos
     * @return array
     */
    public static function getPositionToMap(Position $pos): array
    {
        $result = [
            'x' => intval(round($pos->x)),
            'y' => intval(round($pos->y)),
            'z' => intval(round($pos->z)),
        ];

        if ($pos instanceof Location) {
            $result['yaw'] = intval(round($pos->yaw));
            $result['pitch'] = intval(round($pos->pitch));
        }

        return $result;
    }

    /**
     * @param $posArr
     * @param $level
     * @return Location|Position|null
     */
    public static function getPositionFromMap($posArr, $level): Location|Position|null
    {
        $result = null;

        if (!is_null($posArr) and is_array($posArr) and self::arr_contains_keys($posArr, 'x', 'y', 'z')) {
            $x = floatval(intval($posArr['x']));
            $y = floatval(intval($posArr['y']));
            $z = floatval(intval($posArr['z']));

            if (self::isALevel($level)) {
                $server = Server::getInstance();

                if (self::arr_contains_keys($posArr, 'yaw', 'pitch')) {
                    $yaw = floatval(intval($posArr['yaw']));
                    $pitch = floatval(intval($posArr['pitch']));
                    $result = new Location($x, $y, $z, $yaw, $pitch, $server->getLevelByName($level));
                } else
                    $result = new Position($x, $y, $z, $server->getLevelByName($level));
            }
        }

        return $result;
    }

    /**
     * @param array $haystack
     * @param ...$needles
     * @return bool
     */
    public static function arr_contains_keys(array $haystack, ...$needles): bool
    {
        $result = true;

        foreach ($needles as $key) {
            if (!isset($haystack[$key])) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string|Level $level
     * @param bool $loaded
     * @return bool
     */
    public static function isALevel(Level|string $level, bool $loaded = true): bool
    {
        $server = Server::getInstance();

        $lvl = ($level instanceof Level) ? $level : $server->getLevelByName($level);

        $result = false;

        if (is_string($level) and $loaded === false) {
            $levels = self::getLevelsFromFolder();

            if (in_array($level, $levels))
                $result = true;

        } elseif ($lvl instanceof Level) {
            $name = $lvl->getName();
            if ($loaded === true)
                $result = $server->isLevelLoaded($name);
        }

        return $result;
    }

    /**
     * @param PracticeCore|null $core
     * @return array
     */
    public static function getLevelsFromFolder(PracticeCore $core = null): array
    {
        $core = ($core instanceof PracticeCore) ? $core : PracticeCore::getInstance();

        $index = self::str_indexOf("/plugin_data", $core->getDataFolder());

        $substr = substr($core->getDataFolder(), 0, $index);

        $worlds = $substr . "/worlds";

        if (!is_dir($worlds))
            return [];

        return scandir($worlds);
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param int $len
     * @return int
     */
    public static function str_indexOf(string $needle, string $haystack, int $len = 0): int
    {

        $result = -1;

        $indexes = self::str_indexes($needle, $haystack);

        $length = count($indexes);

        if ($length > 0) {

            $length = $length - 1;

            $indexOfArr = ($len > $length or $len < 0 ? 0 : $len);

            $result = $indexes[$indexOfArr];

        }

        return $result;
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return array
     */
    public static function str_indexes(string $needle, string $haystack): array
    {
        $result = [];

        $end = strlen($needle);

        $len = 0;

        while (($len + $end) <= strlen($haystack)) {
            $substr = substr($haystack, $len, $end);

            if ($needle === $substr)
                $result[] = $len;

            $len++;
        }

        return $result;
    }

    /**
     * @param Level $level1 - The first level.
     * @param Level $level2 - The second level.
     * @return bool - Return true if equivalent, false otherwise.
     *
     * Determines if the levels are equivalent.
     */
    #[Pure] public static function areLevelsEqual(Level $level1, Level $level2): bool
    {
        if (!$level1 instanceof Level && !is_string($level1)) {
            return false;
        }

        if (!$level2 instanceof Level && !is_string($level2)) {
            return false;
        }

        if ($level1 instanceof Level && $level2 instanceof Level) {
            return $level1->getId() === $level2->getId();
        }

        $level2Name = $level2 instanceof Level ? $level2->getName() : $level2;
        $level1Name = $level1 instanceof Level ? $level1->getName() : $level1;

        return $level1Name === $level2Name;
    }

    /**
     * @param Vector3|null $vec3 - The input vector3.
     * @return array|null
     *
     * Converts the vector3 to an array.
     */
    public static function vec3ToArr(?Vector3 $vec3): ?array
    {
        if ($vec3 == null) {
            return null;
        }

        $output = [
            "x" => $vec3->x,
            "y" => $vec3->y,
            "z" => $vec3->z,
        ];

        if ($vec3 instanceof Location) {
            $output["pitch"] = $vec3->pitch;
            $output["yaw"] = $vec3->yaw;
        }

        return $output;
    }

    /**
     * @param $input - The input array.
     * @return Vector3|null
     *
     * Converts an array input to a Vector3.
     */
    public static function arrToVec3($input): ?Vector3
    {
        if (is_array($input) && isset($input["x"], $input["y"], $input["z"])) {
            if (isset($input["pitch"], $input["yaw"])) {
                return new Location($input["x"], $input["y"], $input["z"], $input["yaw"], $input["pitch"]);
            }

            return new Vector3($input["x"], $input["y"], $input["z"]);
        }

        return null;
    }

    /**
     * @param $player
     */
    public static function spawnLightning($player)
    {
       /* if ($player instanceof Player) {
            $player = self::getPlayer($player);
        }

        if (is_null($player)) return;

        $lightning = new AddActorPacket();

        $lightning->type = "minecraft:lightning_bolt";
        $lightning->entityRuntimeId = Entity::$entityCount++;
        $lightning->metadata = [];
        $lightning->motion = null;
        $lightning->yaw = $player->getYaw();
        $lightning->pitch = $player->getPitch();
        $lightning->position = new Vector3($player->getX(), $player->getY(), $player->getZ());
        Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $lightning);

        self::impactSound($player);*/
    }

    /**
     * @param $player
     */
    public static function impactSound($player)
    {
        if ($player instanceof Player) {
            $player = self::getPlayer($player);
        }

        if (is_null($player)) return;

        $sound = new PlaySoundPacket();

        $sound->soundName = "ambient.weather.lightning.impact";
        $sound->x = $player->getX();
        $sound->y = $player->getY();
        $sound->z = $player->getZ();
        $sound->volume = 1;
        $sound->pitch = 1;

        Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $sound);
    }

    /**
     * @param Player $player
     */
    public static function teleportSound(Player $player)
    {
        $player = self::getPlayer($player);

        if (is_null($player)) return;

        $sound = new PlaySoundPacket();

        $sound->soundName = "mob.endermen.portal";
        $sound->x = $player->getX();
        $sound->y = $player->getY();
        $sound->z = $player->getZ();
        $sound->volume = 10;
        $sound->pitch = 1;

        foreach ($player->getLevel()->getPlayers() as $players) {
            $players->dataPacket($sound);
        }
    }

    /**
     * @param Player $player
     */
    public static function createPotion(Player $player)
    {
        $nbt = Entity::createBaseNBT($player->add(0, 0, 0), $player->getDirectionVector());
        $entity = new \DidntPot\game\entities\SplashPotion($player->getLevelNonNull(), $nbt, $player);

        $ev = new ProjectileLaunchEvent($entity);
        $ev->call();

        if ($ev->isCancelled()) {
            $entity->flagForDespawn();
            return;
        }

        $entity->spawnToAll();

        if (!$player->isCreative()) {
            $player->getInventory()->setItemInHand(Item::get(0));
        }
    }

    /**
     * @param Player $player
     */
    public static function createEnderPearl(Player $player)
    {
        $duel = PracticeCore::getDuelManager()->getDuel($player);

        if($duel !== null)
        {
            if($duel->isCountingDown()) return;
        }

        $world = $player->getLevelNonNull();
        $nbt = Entity::createBaseNBT($player->add(0, 0, 0), $player->getDirectionVector());
        $entity = new \DidntPot\game\entities\EnderPearl($world, $nbt, $player);

        $ev = new ProjectileLaunchEvent($entity);
        $ev->call();

        if ($ev->isCancelled()) {
            $entity->flagForDespawn();
            return;
        }

        $entity->spawnToAll();
    }

    /**
     * @param String $s
     * @return Item|null
     */
    public static function getItemFromString(string $s): ?Item
    {
        $itemArr = [];

        $enchantsArr = [];

        if (self::str_contains('-', $s)) {
            $arr = explode('-', $s);
            $arrSize = count($arr);
            $itemArr = explode(':', $arr[0]);

            if ($arrSize > 1) $enchantsArr = explode(',', $arr[1]);

        } else $itemArr = explode(':', $s);

        $baseItem = null;

        $len = count($itemArr);

        if ($len >= 1 and $len < 4) {
            $id = intval($itemArr[0]);
            $count = 1;
            $meta = 0;

            if ($len == 2) $meta = intval($itemArr[1]);
            else if ($len == 3) {
                $count = intval($itemArr[2]);
                $meta = intval($itemArr[1]);
            }

            $isGoldenHead = false;

            if ($id === ItemIds::GOLDEN_APPLE and $meta === 1) {
                $isGoldenHead = true;
                $meta = 0;
            }

            $baseItem = Item::get($id, $meta, $count);

            if ($isGoldenHead === true) $baseItem = $baseItem->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Golden Apple");
        }

        $enchantCount = count($enchantsArr);

        if ($enchantCount > 0 and !is_null($baseItem)) {
            for ($i = 0; $i < $enchantCount; $i++) {
                $enchant = strval($enchantsArr[$i]);
                $enArr = explode(':', $enchant);
                $arrCount = count($enArr);
                if ($arrCount === 2) {
                    $eid = intval($enArr[0]);
                    $elvl = intval($enArr[1]);
                    $e = new EnchantmentInstance(Enchantment::getEnchantment($eid), $elvl);
                    $baseItem->addEnchantment($e);
                }
            }
        }

        return $baseItem;
    }

    /**
     * @param Player $sender
     * @param $player
     * @return bool
     */
    public static function canRequestPlayer(Player $sender, $player): bool
    {
        /*$playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {
            $msg = null;
            $requested = $playerHandler->getPlayer($player);
            $rqName = $requested->getPlayerName();

            if($requested->isInArena())
                $msg = self::str_replace(self::getMessage('duels.misc.arena-msg'), ['%player%' => $rqName]);
            else {
                if(PracticeCore::getDuelHandler()->isWaitingForDuelToStart($rqName) or $requested->isInDuel()) {
                    $msg = self::str_replace(self::getMessage('duels.misc.in-duel'), ['%player%' => $rqName]);
                } else {
                    if($requested->canSendDuelRequest())
                        $result = true;
                    else {
                        $sec = $requested->getCantDuelSpamSecs();
                        $msg = self::str_replace(self::getMessage('duels.misc.anti-spam'), ['%player%' => $rqName, '%time%' => "$sec"]);
                    }
                }
            }

            if(!is_null($msg)) $sender->sendMessage($msg);
        }*/

        return true;
    }

    /**
     * @param Player $sender
     * @param $player
     * @return bool
     */
    public static function canAcceptPlayer(Player $sender, $player): bool
    {
        /*$ivsiHandler = PracticeCore::get1vs1Handler();

        if(self::canRequestPlayer($sender, $player)) {
            if($ivsiHandler->hasPendingRequest($sender, $player)) {
                $request = $ivsiHandler->getRequest($sender, $player);
                $result = $request->canAccept();
            } else 'duels.1vs1.no-pending-rqs';
        }*/

        return true;
    }

    /**
     * @param $s
     * @param bool $isInteger
     * @return bool
     */
    #[Pure] public static function canParse($s, bool $isInteger): bool
    {
        $canParse = true;

        if (is_string($s)) {
            $abc = 'ABCDEFGHIJKLMNOPQRZTUVWXYZ';
            $invalid = $abc . strtoupper($abc) . "!@#$%^&*()_+={}[]|:;\"',<>?/";

            if ($isInteger === true) $invalid = $invalid . '.';

            $strArr = str_split($invalid);

            $canParse = self::str_contains_from_arr($s, $strArr);

        } else $canParse = ($isInteger === true) ? is_int($s) : is_float($s);

        return $canParse;
    }

    /**
     * @param string $haystack
     * @param array $needles
     * @return bool
     */
    #[Pure] public static function str_contains_from_arr(string $haystack, array $needles): bool
    {
        $result = true;

        $size = count($needles);

        if ($size > 0) {
            foreach ($needles as $needle) {
                if (!self::str_contains($needle, $haystack)) {
                    $result = false;
                    break;
                }
            }
        } else $result = false;

        return $result;
    }

    /**
     * @param string $msg
     */
    public static function broadcastMsg(string $msg): void
    {
        $server = Server::getInstance();

        $players = $server->getOnlinePlayers();

        foreach ($players as $player)
            $player->sendMessage($msg);

        $server->getLogger()->info($msg);
    }

    /**
     * @param Player $player
     * @return string
     */
    public static function formatChat(Player $player): string
    {
        $session = PlayerHandler::getSession($player);

        $rank = strtolower($session->getRank());

        if($rank === "player")
        {
            return "§8[%division%§8] §a" . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "knight")
        {
            return "§8[%division%§8] §8[§bKnight§8] §b" . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "duke")
        {
            return "§8[%division%§8] §8[§6Duke§8] §6" . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "siena")
        {
            return "§8[%division%§8] §8[" . TextFormat::DARK_PURPLE . "Siena§8] " . TextFormat::DARK_PURPLE . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "nitro")
        {
            return "§8[%division%§8] §8[§dNitro§8] §d" . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "media")
        {
            return "§8[%division%§8] §8[§cMedia§8] §c" . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "famous")
        {
            return "§8[%division%§8] §8[§dFamous§8] §d" . "%name%" . " §f» §7" . "%msg%";
        }

        if($rank === "helper")
        {
            return "§8[%division%§8] §8[§aHelper§8] §a" . "%name%" . " §f» §a" . "%msg%";
        }

        if($rank === "moderator")
        {
            return "§8[%division%§8] §8[§6Moderator§8] §6" . "%name%" . " §f» §6" . "%msg%";
        }

        if($rank === "admin")
        {
            return "§8[%division%§8] §8[§cAdmin§8] §c" . "%name%" . " §f» §c" . "%msg%";
        }
        
        if($rank === "manager")
        {
            return "§8[%division%§8] §8[§5Manager§8] §5" . "%name%" . " §f» §5" . "%msg%";
        }

        if($rank === "owner")
        {
            return "§8[%division%§8] §8[§4Owner§8] §4" . "%name%" . " §f» §4" . "%msg%";
        }

        return "§8[%division%§8] " . "%name%" . " §f» §7" . "%msg%";
    }

    /**
     * @param Player $player
     * @return string
     */
    public static function formatDivision(Player $player): string
    {
        $session = PlayerHandler::getSession($player);
        $kills = $session->getKills();

        $format = "§8Bronze V§r";

        if($kills >= 2100)
        {
            $format = "§r§l§k§f||§r§4Overlord I§r§l§k§f||§r";
            return $format;
        }

        if($kills >= 2000)
        {
            $format = "§r§l§k§f||§r§4Overlord II§r§l§k§f||§r";
            return $format;
        }

        if($kills >= 1900)
        {
            $format = "§r§l§k§f||§r§4Overlord III§r§r§l§k§f||§r";
            return $format;
        }

        if($kills >= 1800)
        {
            $format = "§r§l§k§f||§r§4Overlord IV§r§r§l§k§f||§r";
            return $format;
        }

        if($kills >= 1700)
        {
            $format = "§r§l§k§f||§r§4Overlord V§r§r§l§k§f||§r";
            return $format;
        }

        if($kills >= 1600)
        {
            $format = "§r§l§f||§r§9Grand-Master I§r";
            return $format;
        }

        if($kills >= 1500)
        {
            $format = "§9Grand-Master II§r";
            return $format;
        }

        if($kills >= 1400)
        {
            $format = "§9Grand-Master III§r";
            return $format;
        }

        if($kills >= 1300)
        {
            $format = "§9Grand-Master IV§r";
            return $format;
        }

        if($kills >= 1200)
        {
            $format = "§9Grand-Master V§r";
            return $format;
        }

        if($kills >= 1150)
        {
            $format = "§aMaster I§r";
            return $format;
        }

        if($kills >= 1100)
        {
            $format = "§aMaster II§r";
            return $format;
        }

        if($kills >= 1050)
        {
            $format = "§aMaster III§r";
            return $format;
        }

        if($kills >= 1000)
        {
            $format = "§aMaster IV§r";
            return $format;
        }

        if($kills >= 980)
        {
            $format = "§aMaster V§r";
            return $format;
        }

        if($kills >= 860)
        {
            $format = "§bDiamond I§r";
            return $format;
        }

        if($kills >= 840)
        {
            $format = "§bDiamond II§r";
            return $format;
        }

        if($kills >= 820)
        {
            $format = "§bDiamond III§r";
            return $format;
        }

        if($kills >= 800)
        {
            $format = "§bDiamond IV§r";
            return $format;
        }

        if($kills >= 780)
        {
            $format = "§bDiamond V§r";
            return $format;
        }

        if($kills >= 660)
        {
            $format = "§3Platinum I§r";
            return $format;
        }

        if($kills >= 640)
        {
            $format = "§3Platinum II§r";
            return $format;
        }

        if($kills >= 620)
        {
            $format = "§3Platinum III§r";
            return $format;
        }

        if($kills >= 600)
        {
            $format = "§3Platinum IV§r";
            return $format;
        }

        if($kills >= 580)
        {
            $format = "§3Platinum V§r";
            return $format;
        }

        if($kills >= 460)
        {
            $format = "§eGold I§r";
            return $format;
        }

        if($kills >= 440)
        {
            $format = "§eGold II§r";
            return $format;
        }

        if($kills >= 420)
        {
            $format = "§eGold III§r";
            return $format;
        }

        if($kills >= 400)
        {
            $format = "§eGold IV§r";
            return $format;
        }

        if($kills >= 380)
        {
            $format = "§eGold V§r";
            return $format;
        }

        if($kills >= 260)
        {
            $format = "§fIron I§r";
            return $format;
        }

        if($kills >= 240)
        {
            $format = "§fIron II§r";
            return $format;
        }

        if($kills >= 220)
        {
            $format = "§fIron III§r";
            return $format;
        }

        if($kills >= 200)
        {
            $format = "§fIron IV§r";
            return $format;
        }

        if($kills >= 180)
        {
            $format = "§fIron V§r";
            return $format;
        }

        if($kills >= 80)
        {
            $format = "§8Bronze I§r";
            return $format;
        }

        if($kills >= 60)
        {
            $format = "§8Bronze II§r";
            return $format;
        }

        if($kills >= 40)
        {
            $format = "§8Bronze III§r";
            return $format;
        }

        if($kills >= 20)
        {
            $format = "§8Bronze IV§r";
            return $format;
        }

        if($kills >= 0)
        {
            $format = "§8Bronze V§r";
            return $format;
        }

        return $format;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function willUpgradeDivision(Player $player): bool
    {
        $session = PlayerHandler::getSession($player);
        $kills = $session->getKills();

        if($kills === 1000)
        {
            return true;
        }

        if($kills === 860)
        {
            return true;
        }

        if($kills === 840)
        {
            return true;
        }

        if($kills === 820)
        {
            return true;
        }

        if($kills === 800)
        {
            return true;
        }

        if($kills === 780)
        {
            return true;
        }

        if($kills === 660)
        {
            return true;
        }

        if($kills === 640)
        {
            return true;
        }

        if($kills === 620)
        {
            return true;
        }

        if($kills === 600)
        {
            return true;
        }

        if($kills === 580)
        {
            return true;
        }

        if($kills === 460)
        {
            return true;
        }

        if($kills === 440)
        {
            return true;
        }

        if($kills === 420)
        {
            return true;
        }

        if($kills === 400)
        {
            return true;
        }

        if($kills === 380)
        {
            return true;
        }

        if($kills === 260)
        {
            return true;
        }

        if($kills === 240)
        {
            return true;
        }

        if($kills === 220)
        {
            return true;
        }

        if($kills === 200)
        {
            return true;
        }

        if($kills === 180)
        {
            return true;
        }

        if($kills === 80)
        {
            return true;
        }

        if($kills === 60)
        {
            return true;
        }

        if($kills === 40)
        {
            return true;
        }

        if($kills === 20)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Player $player
     * @param bool $clearInv
     * @param bool $teleport
     * @param bool $disablePlugin
     */
    public static function resetPlayer(Player $player, bool $clearInv = true, bool $teleport = true, bool $disablePlugin = false): void
    {
        if (!is_null($player) and $player->isOnline()) {
            if ($player->getGamemode() !== 0) $player->setGamemode(0);

            if ($player->hasEffects()) $player->removeAllEffects();

            if ($player->getHealth() !== $player->getMaxHealth()) $player->setHealth($player->getMaxHealth());

            if ($teleport === true) {
                $lobby = PracticeCore::getInstance()->getServer()->getLevelByName(PracticeCore::LOBBY);
                $pos = new Position(PracticeCore::LOBBY_X, PracticeCore::LOBBY_Y, PracticeCore::LOBBY_Z, $lobby);
                if ($disablePlugin === true) {
                    $player->teleport($pos);
                } else {
                    $x = $pos->x;
                    $z = $pos->z;

                    self::onChunkGenerated($pos->level, intval($x) >> 4, intval($z) >> 4, function () use ($player, $pos) {
                        $player->teleport($pos);
                    });
                }
            }

            $session = PlayerHandler::getSession($player);

            if ($player->isOnFire()) $player->extinguish();

            if ($player->isImmobile()) $player->setImmobile(false);

            if (Server::getInstance()->getPlayer($player->getName())->isOnline()) {
                if (!$session->isEnderPearlCooldown()) $session->setEnderPearlCooldown(false);
                if ($session->isCombat()) $session->setCombat(false);

                $session->teleportPlayer($player, "lobby", $clearInv, true);
            }
        }
    }

    /**
     * @param Level $level
     * @param int $x
     * @param int $z
     * @param callable $callable
     */
    public static function onChunkGenerated(Level $level, int $x, int $z, callable $callable): void
    {
        if ($level->isChunkPopulated($x, $z)) {
            ($callable)();
            return;
        }

        $level->registerChunkLoader(new PracticeChunkLoader($level, $x, $z, $callable), $x, $z, true);
    }

    /**
     * @param Level $level
     * @param bool $proj
     * @param bool $all
     */
    public static function clearEntitiesIn(Level $level, bool $proj = false, bool $all = false): void
    {
        $entities = $level->getEntities();

        foreach ($entities as $entity) {
            $exec = true;

            if ($entity instanceof Player) $exec = false;
            elseif ($all === false and $entity instanceof EnderPearl) $exec = false;
            elseif ($all === false and $entity instanceof SplashPotion) $exec = false;
            elseif ($all === false and $entity instanceof Arrow) $exec = $proj;

            if ($exec === true)
                $entity->close();
        }
    }

    /**
     * @param array $str
     * @param bool $visible
     * @return string
     */
    public static function getLineSeparator(array $str, bool $visible = true): string
    {
        $count = count($str);

        $len = 20;

        $keys = array_keys($str);

        if ($count > 0) {
            $greatest = self::getUncoloredString(strval($str[$keys[0]]));

            foreach ($keys as $key) {
                $current = self::getUncoloredString(strval($str[$key]));

                if (strlen($current) > strlen($greatest))
                    $greatest = $current;
            }

            $len = strlen($greatest);
        }

        if ($len > 27) $len = 27;

        $str = '';
        $count = 0;

        while ($count < $len) {
            $character = ($visible === true) ? '-' : ' ';

            $str .= $character;

            $count++;
        }

        return $str;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function getUncoloredString(string $str): string
    {
        return TextFormat::clean($str);
    }

    /**
     * @param int $worldId
     * @param string $arena
     * @param string $type
     *
     * @return bool
     */
    public static function createLevel(int $worldId, string $arena, string $type): bool
    {
        $server = Server::getInstance();

        $arenaPath = PracticeCore::getResourcesFolder() . "worlds/$arena.zip";

        if (!file_exists($arenaPath)) {
            return false;
        } else {
            $newLevelPath = $server->getDataPath() . "/worlds/$type$worldId/";
            $zip = new ZipArchive;

            if ($zip->open($arenaPath) === true) {
                mkdir($newLevelPath);
                $zip->extractTo($newLevelPath);
                $zip->close();
                unset($zip);

                $nbt = new BigEndianNBTStream();
                $leveldat = zlib_decode(file_get_contents($newLevelPath . 'level.dat'));
                $levelData = $nbt->read($leveldat);
                $levelData["Data"]->setTag(new StringTag("LevelName", "$type$worldId"));

                $buffer = $nbt->writeCompressed($levelData);
                file_put_contents($newLevelPath . 'level.dat', $buffer);

                $server->loadLevel("$type$worldId");
                $level = $server->getLevelByName("$type$worldId");
                $level->setTime(0);
                $level->stopTime();

                return true;
            }
        }

        return false;
    }

    /**
     * @param string|Level $level
     */
    public static function deleteLevel(Level|string $level): void
    {
        $server = Server::getInstance();

        if (is_string($level)) {
            $path = $server->getDataPath() . "worlds/" . $level;
            $server->getAsyncPool()->submitTask(new AsyncDeleteLevel($path));
        } elseif ($level instanceof Level) {
            $server->unloadLevel($level);
            $path = Server::getInstance()->getDataPath() . 'worlds/' . $level->getFolderName();
            $server->getAsyncPool()->submitTask(new AsyncDeleteLevel($path));
        }
    }


    /**
     * @param Vector3 $vec3
     * @param Level|null $level
     *
     * @return Position
     */
    public static function toPosition(Vector3 $vec3, Level $level = null): Position
    {
        return new Position($vec3->x, $vec3->y, $vec3->z, $level);
    }

    /**
     * @param float|int $pos
     * @param float|int $max
     * @param float|int $min
     *
     * @return bool
     *
     * Determines whether the player is within a set of bounds.
     */
    public static function isWithinBounds(float|int $pos, float|int $max, float|int $min): bool
    {
        return $pos <= $max && $pos >= $min;
    }

    /**
     * @param Location|Position|Vector3 $pos
     *
     * @return array
     */
    public static function posToArray(Location|Vector3|Position $pos): array
    {
        return [
            'x' => (int)$pos->x,
            'y' => (int)$pos->y,
            'z' => (int)$pos->z
        ];
    }

    /**
     * @param int $id
     * @param int $meta
     * @param int $count
     * @param array|EnchantmentInstance[] $enchants
     * @return Item
     */
    public static function createItem(int $id, int $meta = 0, int $count = 1, array $enchants = []): Item
    {
        $item = Item::get($id, $meta, $count);

        foreach ($enchants as $e)
            $item->addEnchantment($e);

        return $item;
    }

    /**
     * @param bool $fps
     * @return string
     *
     * Provides a random duel arena determining whether it is fps or not.
     */
    public static function randomizeDuelArenas(bool $fps = false): string
    {
        $genManager = PracticeCore::getGeneratorManager();

        $arenas = array_keys($genManager->listGenerators($fps, "type_duel"));

        return $arenas[mt_rand(0, count($arenas) - 1)];
    }


    /**
     * @param bool $fps
     * @return string
     *
     * Provides a random sumo arena determined whether it is fps or not.
     */
    public static function randomizeSumoArenas(bool $fps = false): string
    {
        $generatorManager = PracticeCore::getGeneratorManager();

        $arenas = array_keys($generatorManager->listGenerators($fps, "type_sumo"));

        return $arenas[mt_rand(0, count($arenas) - 1)];
    }

    /**
     * @param array $player
     * @param $x
     * @param $y
     * @param $z
     * @param Level $level
     * @param int $ticks
     */
    public static function sendDragonEffect(array $player, $x, $y, $z, Level $level, int $ticks = 120): void
    {
        $id = Entity::$entityCount++;

        $pk = new AddActorPacket();
        $pk->type = 'minecraft:ender_dragon';
        $pk->entityRuntimeId = $id;
        $pk->metadata = [Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]];
        $pk->motion = null;
        $pk->position = new Position($x, $y, $z, $level);
        Server::getInstance()->batchPackets($player, [$pk], false);

        $pk = new ActorEventPacket();
        $pk->entityRuntimeId = $id;
        $pk->event = ActorEventPacket::ENDER_DRAGON_DEATH;
        Server::getInstance()->batchPackets($player, [$pk], false);

        $pk = new RemoveActorPacket();
        $pk->entityUniqueId = $id;

        PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new class($player, $pk) extends Task
        {
            private $player, $pk;

            public function __construct($player, $pk)
            {
                $this->player = $player;
                $this->pk = $pk;
            }

            public function onRun($currentTick)
            {
                Server::getInstance()->batchPackets($this->player, [$this->pk], false);
            }
        }, $ticks);
    }

    /**
     * @param Human $inPlayer
     * @param DataPacket $packet
     * @param callable|null $callable
     * @param array|null $viewers
     */
    public static function broadcastPacketToViewers(Human $inPlayer, DataPacket $packet, ?callable $callable = null, ?array $viewers = null): void
    {
        $viewers = $viewers ?? $inPlayer->getLevelNonNull()->getViewersForPosition($inPlayer->asVector3());

        foreach($viewers as $viewer)
        {
            if($viewer->isOnline())
            {
                if($callable !== null and !$callable($viewer, $packet))
                {
                    continue;
                }

                $viewer->batchDataPacket($packet);
            }
        }
    }

    /**
     * @param Player $player
     * @param SplashPotion $item
     * @param bool $animate
     */
    public static function throwPotion(Player $player, SplashPotion $item, bool $animate = false)
    {
        $duel = PracticeCore::getDuelManager()->getDuel($player);
        $players = $player->getLevel()->getPlayers();
        $item->onClickAir($player, $player->getDirectionVector());

        if($duel !== null)
        {
            if($duel->isCountingDown()) return;
        }

        if($animate){
            $pkt = new AnimatePacket();
            $pkt->action = AnimatePacket::ACTION_SWING_ARM;
            $pkt->entityRuntimeId = $player->getId();
            $player->getServer()->broadcastPacket($players, $pkt);
        }

        if(!$player->isCreative()){
            $inv = $player->getInventory();
            $inv->setItem($inv->getHeldItemIndex(), Item::get(0));
        }
    }

    /**
     * @param string $winner
     * @param string $loser
     * @param int $winnerElo
     * @param int $loserElo
     * @param string $queue
     * @param bool $set
     * @return int[]
     */
    public static function updateElo(string $winner, string $loser, int $winnerElo, int $loserElo, string $queue, bool $set = true): array
    {
        $result = ['winner' => 1000, 'loser' => 1000, 'winner-change' => 0, 'loser-change' => 0];

        $wPlayer = self::getPlayer($winner);
        $lPlayer = self::getPlayer($loser);

        $kFactor = 32;

        $winnerExpectedScore = 1.0 / (1.0 + pow(10, floatval(($loserElo - $winnerElo) / 400)));
        $loserExpectedScore = abs(1.0 / (1.0 + pow(10, floatval(($winnerElo - $loserElo) / 400))));

        $newWinnerElo = $winnerElo + intval($kFactor * (1 - $winnerExpectedScore));
        $newLoserElo = $loserElo + intval($kFactor * (0 - $loserExpectedScore));

        $winnerEloChange = $newWinnerElo - $winnerElo;
        $loserEloChange = abs($loserElo - $newLoserElo);

        $winnerEloChange = intval($winnerEloChange * 1.1);

        $newWElo = $winnerElo + $winnerEloChange;
        $newLElo = $loserElo - $loserEloChange;

        if($newLElo < 700)
        {
            $newLElo = 700;
            $loserEloChange = $loserElo - 700;
        }

        $result['winner'] = $newWElo;
        $result['loser'] = $newLElo;

        $result['winner-change'] = $winnerEloChange;
        $result['loser-change'] = $loserEloChange;

        if($set)
        {
            if($wPlayer !== null and $wPlayer->isOnline() and $wPlayer instanceof Player)
                PlayerHandler::getSession($wPlayer)->setElo($queue, $newWElo);

            if($lPlayer !== null and $lPlayer->isOnline() and $lPlayer instanceof Player)
                PlayerHandler::getSession($lPlayer)->setElo($queue, $newLElo);
        }

        return $result;
    }

    /**
     * @param string $rank
     * @return string
     */
    #[Pure] public static function formatNameTag(Player $player, string $rank): string
    {
        $rank = strtolower($rank);

        if($rank === "player") return TextFormat::GREEN . Utils::getPlayerName($player);

        if($rank === "knight") return TextFormat::BLUE . Utils::getPlayerName($player);

        if($rank === "duke") return TextFormat::GOLD . Utils::getPlayerName($player);

        if($rank === "siena") return TextFormat::DARK_PURPLE . Utils::getPlayerName($player);

        if($rank === "nitro") return TextFormat::LIGHT_PURPLE . Utils::getPlayerName($player);

        if($rank === "media") return TextFormat::RED . Utils::getPlayerName($player);

        if($rank === "famous") return TextFormat::LIGHT_PURPLE . Utils::getPlayerName($player);

        if($rank === "helper") return TextFormat::GREEN . Utils::getPlayerName($player);

        if($rank === "moderator") return TextFormat::GOLD . Utils::getPlayerName($player);

        if($rank === "admin") return TextFormat::RED . Utils::getPlayerName($player);

        if($rank === "manager") return TextFormat::DARK_PURPLE . Utils::getPlayerName($player);

        if($rank === "owner") return TextFormat::DARK_RED . Utils::getPlayerName($player);

        return "[NO_RANK] " . Utils::getPlayerName($player);
    }
}