<?php

namespace DidntPot\player\item;

use DidntPot\arenas\FFAArena;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class ItemHandler
{
    /* @var PracticeItem[] */
    private array $itemList;

    /* @var int */
    private int $hubItemsCount;

    /* @var int */
    private int $duelItemsCount;

    /* @var int */
    private int $ffaItemsCount;

    /* @var int */
    private int $leaderboardItemsCount;

    /* @var int */
    private int $partyItemsCount;

    /* @var ItemTextures */
    private ItemTextures $textures;

    private array $potions;

    private array $buckets;

    public function __construct(PracticeCore $core)
    {
        $this->itemList = [];
        $this->textures = new ItemTextures($core);

        $this->potions = [
            'Water Bottle', 'Water Bottle', 'Water Bottle', 'Water Bottle', 'Water Bottle',
            'Potion of Night Vision', 'Potion of Night Vision', 'Potion of Invisibility',
            'Potion of Invisibility', 'Potion of Leaping', 'Potion of Leaping', 'Potion of Leaping',
            'Potion of Fire Resistance', 'Potion of Fire Resistance', 'Potion of Swiftness', 'Potion of Swiftness',
            'Potion of Swiftness', 'Potion of Slowness', 'Potion of Slowness', 'Potion of Water Breathing',
            'Potion of Water Breathing', 'Potion of Healing', 'Potion of Healing', 'Potion of Harming',
            'Potion of Harming', 'Potion of Poison', 'Potion of Poison', 'Potion of Regeneration', 'Potion of Regeneration',
            'Potion of Regeneration', 'Potion of Strength', 'Potion of Strength', 'Potion of Strength', 'Potion of Weakness',
            'Potion of Weakness', 'Potion of Decay'
        ];

        $this->buckets = [
            8 => 'Water Bucket',
            9 => 'Water Bucket',
            10 => 'Lava Bucket',
            11 => 'Lava Bucket'
        ];

        $this->init();
    }

    private function init(): void
    {
        $this->initHubItems();
        $this->initDuelItems();
        $this->initFFAItems();
        $this->initLeaderboardItems();
        $this->initPartyItems();
        $this->initMiscItems();
    }

    private function initHubItems(): void
    {
        $unranked = new PracticeItem('hub.unranked-duels', 0, Item::get(ItemIds::IRON_SWORD)->setCustomName('unranked-duels'), 'Iron Sword');
        $ranked = new PracticeItem('hub.ranked-duels', 1, Item::get(ItemIds::DIAMOND_SWORD)->setCustomName('ranked-duels'), 'Diamond Sword');
        $ffa = new PracticeItem('hub.ffa', 2, Item::get(ItemIds::IRON_AXE)->setCustomName('play-ffa'), 'Iron Axe');
        $leaderboard = new PracticeItem('hub.leaderboard', 4, Item::get(ItemIds::SKULL, 3, 1)->setCustomName(TextFormat::BLUE . '» ' . TextFormat::GREEN . 'Leaderboards ' . TextFormat::BLUE . '«'), 'Steve Head');
        $settings = new PracticeItem('hub.settings', 7, Item::get(ItemIds::CLOCK)->setCustomName(TextFormat::BLUE . '» ' . TextFormat::GOLD . 'Your Settings ' . TextFormat::BLUE . '«'), 'Clock');
        $inv = new PracticeItem('hub.duel-inv', 8, Item::get(BlockIds::CHEST)->setCustomName('duel-inventory'), 'Chest');

        $this->itemList = [$unranked, $ranked, $ffa, $leaderboard, $settings, $inv];

        $this->hubItemsCount = 6;
    }

    private function initDuelItems(): void
    {
        $duelKits = PracticeCore::getKitManager()->getDuelKits();

        $items = [];

        foreach ($duelKits as $kit) {
            $name = $kit->getName();
            if ($kit->hasRepItem()) $items['duels.' . $name] = $kit->getRepItem();
        }

        $count = 0;

        $keys = array_keys($items);

        foreach ($keys as $localName) {
            $i = $items[$localName];

            if ($i instanceof Item)
                $this->itemList[] = new PracticeItem(strval($localName), $count, $i, $this->getTextureOf($i));

            $count++;
        }

        $this->duelItemsCount = $count;
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getTextureOf(Item $item): string
    {
        $i = clone $item;

        $name = $i->getVanillaName();

        if ($i->getId() === ItemIds::POTION) {
            $meta = $i->getDamage();
            $name = $this->potions[$meta];
        } elseif ($i->getId() === ItemIds::SPLASH_POTION) {
            $meta = $i->getDamage();
            $name = 'Splash ' . $this->potions[$meta];
        } elseif ($i->getId() === ItemIds::BUCKET) {
            $meta = $i->getDamage();
            if (isset($this->buckets[$meta]))
                $name = $this->buckets[$meta];
        }

        return $this->textures->getTexture($name);
    }

    private function initFFAItems(): void
    {
        $arenas = PracticeCore::getKitManager()->getFFAArenasWKits();

        $result = [];

        foreach ($arenas as $arena) {
            if ($arena instanceof FFAArena) {
                $kit = $arena->getFirstKit();

                if ($kit->hasRepItem()) {
                    $arenaName = $arena->getName();

                    $name = '%kit-name% FFA';

                    if (Utils::str_contains(' FFA', $arenaName) and Utils::str_contains(' FFA', $name))
                        $name = Utils::str_replace($name, [' FFA' => '']);

                    $name = Utils::str_replace($name, ['%kit-name%' => $arenaName]);

                    $item = clone $kit->getRepItem();

                    $result['ffa.' . $arena->getLocalizedName()] = $item->setCustomName($name);
                }
            }
        }

        $count = 0;

        $keys = array_keys($result);

        foreach ($keys as $key) {
            $item = $result[$key];

            if ($item instanceof Item)
                $this->itemList[] = new PracticeItem(strval($key), $count, $item, $this->getTextureOf($item));

            $count++;
        }

        $this->ffaItemsCount = $count;
    }

    private function initLeaderboardItems(): void
    {
        $duelKits = PracticeCore::getKitManager()->getDuelKits();

        $items = [];

        foreach ($duelKits as $kit) {
            $name = $kit->getName();
            if ($kit->hasRepItem()) $items['leaderboard.' . $name] = $kit->getRepItem();
        }

        $count = 0;

        $keys = array_keys($items);

        foreach ($keys as $localName) {
            $i = $items[$localName];

            if ($i instanceof Item)
                $this->itemList[] = new PracticeItem(strval($localName), $count, $i, $this->getTextureOf($i));

            $count++;
        }

        $globalItem = Item::get(ItemIds::COMPASS)->setCustomName(TextFormat::RED . 'Global');

        $var = 'leaderboard.global';

        $global = new PracticeItem($var, $count, $globalItem, $this->getTextureOf($globalItem));

        $this->itemList[] = $global;

        $this->leaderboardItemsCount = $count + 2;
    }

    private function initPartyItems(): void
    {
        $settings = new PracticeItem('parties.leader.settings', 0, Item::get(ItemIds::COMPASS)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . '» ' . TextFormat::GREEN . 'Party ' . TextFormat::GRAY . 'Settings ' . TextFormat::BLUE . '«'), $this->getTextureOf(Item::get(ItemIds::GOLD_SWORD)));
        $match = new PracticeItem('parties.leader.match', 1, Item::get(ItemIds::IRON_SWORD)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . '» ' . TextFormat::AQUA . 'Start a Match' . TextFormat::BLUE . ' «'), $this->getTextureOf(Item::get(ItemIds::IRON_SWORD)));
        $queue = new PracticeItem('parties.leader.queue', 2, Item::get(ItemIds::GOLD_SWORD)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . '» ' . TextFormat::GOLD . 'Duel Other Parties ' . TextFormat::BLUE . '«'), $this->getTextureOf(Item::get(ItemIds::GOLD_SWORD)));

        $leaveParty = new PracticeItem('parties.general.leave', 8, Item::get(ItemIds::REDSTONE_DUST, 0, 1)->setCustomName(TextFormat::GRAY . '» ' . TextFormat::RED . 'Leave Party ' . TextFormat::GRAY . '«'), $this->getTextureOf(Item::get(ItemIds::REDSTONE_DUST)));

        $this->itemList = array_merge($this->itemList, [$settings, $queue, $match, $leaveParty]);

        $this->partyItemsCount = 4;
    }

    private function initMiscItems(): void
    {
        $exit_queue = new PracticeItem('exit.queue', 8, Item::get(ItemIds::REDSTONE)->setCustomName('leave-queue'), $this->getTextureOf(Item::get(ItemIds::REDSTONE_DUST)));
        $exit_spec = new PracticeItem('exit.spectator', 8, Item::get(ItemIds::DYE, 1)->setCustomName('spec-hub'), $this->getTextureOf(Item::get(ItemIds::DYE)), false);
        $exit_inv = new PracticeItem('exit.inventory', 8, Item::get(ItemIds::DYE, 1)->setCustomName(TextFormat::RED . 'Exit'), $this->getTextureOf(Item::get(ItemIds::DYE)));

        array_push($this->itemList, $exit_queue, $exit_spec, $exit_inv);
    }

    public function reload(): void
    {
        $this->itemList = [];
        $this->init();
    }

    /**
     * @param Item $item
     * @return PracticeItem|null
     */
    public function getPracticeItem(Item $item): ?PracticeItem
    {
        $result = null;

        if ($this->isPracticeItem($item)) {
            $practiceItem = $this->itemList[$this->indexOf($item)];

            if ($practiceItem instanceof PracticeItem) {
                $result = $practiceItem;
            }
        }

        return $result;
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function isPracticeItem(Item $item): bool
    {
        return $this->indexOf($item) !== -1;
    }

    /**
     * @param Item $item
     * @return int
     */
    private function indexOf(Item $item): int
    {
        $result = -1;
        $count = 0;

        foreach ($this->itemList as $i) {
            $practiceItem = $i->getItem();

            if ($this->itemsEqual($practiceItem, $item)) {
                $result = $count;
                break;
            }

            $count++;
        }
        return $result;
    }

    /**
     * @param Item $item
     * @param Item $item1
     * @return bool
     */
    private function itemsEqual(Item $item, Item $item1): bool
    {
        return $item->equals($item1, true, false) and $item->getName() === $item1->getName();
    }

    /**
     * @param string $name
     * @return PracticeItem|null
     */
    #[Pure] public function getFromLocalName(string $name): ?PracticeItem
    {
        foreach ($this->itemList as $item) {
            if ($item instanceof PracticeItem) {
                $localName = $item->getLocalizedName();
                if ($localName === $name) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @return PracticeItem[]
     */
    #[Pure] public function getDuelItems(): array
    {
        $result = [];

        $start = $this->hubItemsCount;

        $size = $start + $this->duelItemsCount;

        for ($i = $start; $i < $size; $i++) {
            if (isset($this->itemList[$i])) {
                $item = $this->itemList[$i];
                $localName = $item->getLocalizedName();

                if (Utils::str_contains('duels.', $localName))
                    $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    #[Pure] public function getFFAItems(): array
    {
        $result = [];

        $start = $this->hubItemsCount + $this->duelItemsCount;

        $size = $start + $this->hubItemsCount;

        for ($i = $start; $i < $size; $i++) {
            if (isset($this->itemList[$i])) {
                $item = $this->itemList[$i];

                $localName = $item->getLocalizedName();

                if (Utils::str_contains('ffa.', $localName))
                    $result[] = $item;
            }
        }
        return $result;
    }
}