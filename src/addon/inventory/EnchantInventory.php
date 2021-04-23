<?php

declare(strict_types=1);

namespace addon\inventory;

use addon\AddonManager;
use addon\event\PlayerEnchantItemEvent;
use addon\player\AddonPlayer;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

/**
 * Class EnchantInventory
 * @package addon\inventory
 */
class EnchantInventory extends FakeInventory
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'EnchantInventory';
    }

    /**
     * @return int
     */
    public function getDefaultSize(): int
    {
        return 2;
    }

    /**
     * @return int
     */
    public function getNetworkType(): int
    {
        return WindowTypes::ENCHANTMENT;
    }

    /**
     * @return int
     */
    public function getFirstVirtualSlot(): int
    {
        return 14;
    }

    /**
     * @return int[]
     */
    public function getVirtualSlots(): array
    {
        return [14, 15];
    }

    /**
     * @param AddonPlayer $player
     * @param PlayerActionPacket $packet
     */
    public static function callEvent(AddonPlayer $player, PlayerActionPacket $packet): void
    {
        if ($packet->action === PlayerActionPacket::ACTION_SET_ENCHANTMENT_SEED) {
            $inventory = AddonManager::getTemporarilyInventory($player);

            if ($inventory instanceof EnchantInventory) {
                $ev = new PlayerEnchantItemEvent($player, $inventory->getItem(0));
                $ev->call();

                if ($ev->isCancelled())
                    $ev->getItem()->removeEnchantments();
                $inventory->setItem(0, $ev->getItem());
            }
        }
    }
}