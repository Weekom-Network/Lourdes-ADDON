<?php

declare(strict_types=1);

namespace addon\inventory;

use addon\AddonManager;
use addon\player\AddonPlayer;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\FilterTextPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

/**
 * Class AnvilInventory
 * @package addon\inventory
 */
class AnvilInventory extends FakeInventory
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'AnvilInventory';
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
        return WindowTypes::ANVIL;
    }

    /**
     * @return int
     */
    public function getFirstVirtualSlot(): int
    {
        return 1;
    }

    /**
     * @return int[]
     */
    public function getVirtualSlots(): array
    {
        return [1, 2];
    }

    /**
     * @param AddonPlayer $who
     * @param InventoryTransactionPacket $packet
     */
    public function listen(AddonPlayer $who, InventoryTransactionPacket $packet): void
    {
        if ($this->isOutputItem($packet)) {
            foreach ($packet->trData->getActions() as $action) {
                if ($action->sourceType === NetworkInventoryAction::SOURCE_CONTAINER && $action->windowId === ContainerIds::INVENTORY) {
                    $newName = AddonManager::getTemporarilyText($who);
                    $who->getLevel()->addSound(new AnvilUseSound($who), [$who] + $who->getViewers());

                    if ($newName !== null) {
                        $action->newItem->getItemStack()->setCustomName($newName);
                        AddonManager::resetTemporarilyText($who);
                    }
                }
            }
        }
        parent::listen($who, $packet);
    }

    /**
     * @param InventoryTransactionPacket $packet
     * @return bool
     */
    private function isOutputItem(InventoryTransactionPacket $packet): bool
    {
        foreach ($packet->trData->getActions() as $action)
            if ($action->sourceType === NetworkInventoryAction::SOURCE_TODO && $action->windowId === -12 && $action->inventorySlot === 2)
                if ($action->oldItem->getItemStack()->getNamedTag()->hasTag('RepairCost', IntTag::class) && $action->newItem->getItemStack()->isNull())
                    return true;
        return false;
    }

    /**
     * @param AddonPlayer $player
     * @param FilterTextPacket $packet
     */
    public static function writeText(AddonPlayer $player, FilterTextPacket $packet): void
    {
        if (AddonManager::equalsTemporarilyInventory($player, self::class)) {
            AddonManager::setTemporarilyText($player, $packet);
            $player->dataPacket(FilterTextPacket::create($packet->getText(), true));
        }
    }
}