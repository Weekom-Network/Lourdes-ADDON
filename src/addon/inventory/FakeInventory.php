<?php

declare(strict_types=1);

namespace addon\inventory;

use addon\AddonManager;
use addon\player\AddonPlayer;
use pocketmine\entity\Attribute;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\Player;

/**
 * Class FakeInventory
 */
abstract class FakeInventory extends ContainerInventory
{

    /**
     * @return int
     */
    abstract public function getFirstVirtualSlot(): int;

    /**
     * @return int[]
     */
    abstract public function getVirtualSlots(): array;

    /**
     * @param Player|AddonPlayer $who
     * @return bool
     */
    public function open(Player $who): bool
    {
        AddonManager::setTemporarilyInventory($who, $this);
        return parent::open($who);
    }

    public function close(Player $who): void
    {
        AddonManager::resetTemporarilyData($who);
        parent::close($who);
    }

    /**
     * @param AddonPlayer $who
     * @param InventoryTransactionPacket $packet
     */
    public function listen(AddonPlayer $who, InventoryTransactionPacket $packet): void
    {
        $tmp = AddonManager::getTemporarilyInventory($who);

        if ($tmp instanceof $this) {
            foreach ($packet->trData->getActions() as $action) {
                switch ($action->sourceType) {
                    case NetworkInventoryAction::SOURCE_WORLD:
                        if ($action->windowId === null) {
                            $ev = new PlayerDropItemEvent($who, $action->newItem->getItemStack());
                            $ev->call();

                            if ($ev->isCancelled())
                                $tmp->setItem($action->inventorySlot, $action->newItem->getItemStack());
                            else
                                $who->dropItem($action->newItem->getItemStack());
                        }
                        break;

                    case NetworkInventoryAction::SOURCE_CONTAINER:
                        $adjustedSlot = $action->inventorySlot - $this->getFirstVirtualSlot();
                        $ev = new InventoryTransactionEvent(new InventoryTransaction($who, [new SlotChangeAction($who->getWindow($action->windowId), $action->inventorySlot, $action->oldItem->getItemStack(), $action->newItem->getItemStack()), new SlotChangeAction($tmp, $adjustedSlot, $action->oldItem->getItemStack(), $action->newItem->getItemStack())]));
                        $ev->call();

                        if ($action->windowId === ContainerIds::UI && in_array($action->inventorySlot, $this->getVirtualSlots(), true))
                            $tmp->setItem($adjustedSlot, $ev->isCancelled() ? $action->oldItem->getItemStack() : $action->newItem->getItemStack());
                        else
                            $who->getWindow($action->windowId)->setItem($action->inventorySlot, $ev->isCancelled() ? $action->oldItem->getItemStack() : $action->newItem->getItemStack());
                        break;
                }
            }
        }
    }

    /**
     * @param AddonPlayer $player
     * @param int $level
     */
    public static function setXpProgress(AddonPlayer $player, int $level): void
    {
        $ev = new PlayerExperienceChangeEvent($player, $player->getXpLevel(), $player->getXpProgress(), $level, null);
        $ev->call();

        if ($ev->isCancelled())
            return;
        $level = $ev->getNewLevel();
        $player->getAttributeMap()->getAttribute(Attribute::EXPERIENCE_LEVEL)->setValue($level, true);
    }

    /**
     * @param AddonPlayer $player
     * @param ActorEventPacket $packet
     */
    public static function dealXp(AddonPlayer $player, ActorEventPacket $packet): void
    {
        if ($packet->event === ActorEventPacket::PLAYER_ADD_XP_LEVELS && AddonManager::equalsTemporarilyInventory($player, static::class)) {
            $extractXp = abs($packet->data);
            $playerXp = $player->getXpLevel();
            self::setXpProgress($player, $playerXp - $extractXp);
        }
    }
}