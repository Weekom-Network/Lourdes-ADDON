<?php

declare(strict_types=1);

namespace addon;

use addon\inventory\FakeInventory;
use addon\player\AddonPlayer;
use pocketmine\network\mcpe\protocol\FilterTextPacket;
use pocketmine\Player;

/**
 * Class AddonManager
 * @package addon
 */
class AddonManager
{

    /** @var FakeInventory[] */
    private static $inventories = [];
    /** @var string[] */
    private static $texts = [];

    /**
     * @param AddonPlayer $player
     *
     * @return FakeInventory|null
     */
    public static function getTemporarilyInventory(AddonPlayer $player): ?FakeInventory
    {
        return self::$inventories[$player->getRawUniqueId()] ?? null;
    }

    /**
     * @param AddonPlayer $player
     * @param FakeInventory|null $inventory
     */
    public static function setTemporarilyInventory(AddonPlayer $player, ?FakeInventory $inventory): void
    {
        self::$inventories[$player->getRawUniqueId()] = $inventory;
    }

    /**
     * @param AddonPlayer $player
     * @param string $expectedInventory
     *
     * @return bool
     */
    public static function equalsTemporarilyInventory(AddonPlayer $player, string $expectedInventory): bool
    {
        return self::getTemporarilyInventory($player) instanceof $expectedInventory;
    }

    /**
     * @param AddonPlayer $player
     * @param FilterTextPacket $packet
     */
    public static function setTemporarilyText(AddonPlayer $player, FilterTextPacket $packet): void
    {
        self::$texts[$player->getRawUniqueId()] = $packet->getText();
    }

    /**
     * @param AddonPlayer $player
     *
     * @return string|null
     */
    public static function getTemporarilyText(AddonPlayer $player): ?string
    {
        return self::$texts[$player->getRawUniqueId()] ?? null;
    }

    /**
     * @param AddonPlayer $player
     */
    public static function resetTemporarilyText(AddonPlayer $player): void
    {
        self::$texts[$player->getRawUniqueId()] = null;
    }

    /**
     * @param Player|AddonPlayer $player
     */
    public static function resetTemporarilyData($player): void
    {
        self::$inventories[$player->getRawUniqueId()] = null;
        self::resetTemporarilyText($player);
    }
}