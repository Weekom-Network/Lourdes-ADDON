<?php

declare(strict_types=1);

namespace addon;

use addon\inventory\AnvilInventory;
use addon\inventory\EnchantInventory;
use addon\inventory\FakeInventory;
use addon\player\AddonPlayer;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\FilterTextPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

/**
 * Class AddonListener
 * @package addon
 */
class AddonListener implements Listener
{

    /** @var int[] */
    private const
        HELMET = [
        Item::LEATHER_HELMET,
        Item::CHAIN_HELMET,
        Item::IRON_HELMET,
        Item::GOLD_HELMET,
        Item::DIAMOND_HELMET,
    ],
        CHESTPLATE = [
        Item::LEATHER_CHESTPLATE,
        Item::CHAIN_CHESTPLATE,
        Item::IRON_CHESTPLATE,
        Item::GOLD_CHESTPLATE,
        Item::DIAMOND_CHESTPLATE,
        Item::ELYTRA,
    ],
        LEGGINGS = [
        Item::LEATHER_LEGGINGS,
        Item::CHAIN_LEGGINGS,
        Item::IRON_LEGGINGS,
        Item::GOLD_LEGGINGS,
        Item::DIAMOND_LEGGINGS,
    ],
        BOOTS = [
        Item::LEATHER_BOOTS,
        Item::CHAIN_BOOTS,
        Item::IRON_BOOTS,
        Item::GOLD_BOOTS,
        Item::DIAMOND_BOOTS,
    ];

    /**
     * @param Item $armor
     * @param AddonPlayer $player
     */
    private function setArmorByType(Item $armor, AddonPlayer $player): void
    {
        $id = $armor->getId();
        /** @var null|Item $copy */
        $copy = null;
        if (in_array($id, self::HELMET, true)) {
            $copy = $player->getArmorInventory()->getHelmet();
            $set = $player->getArmorInventory()->setHelmet($armor);
        } elseif (in_array($id, self::CHESTPLATE, true)) {
            $copy = $player->getArmorInventory()->getChestplate();
            $set = $player->getArmorInventory()->setChestplate($armor);
        } elseif (in_array($id, self::LEGGINGS, true)) {
            $copy = $player->getArmorInventory()->getLeggings();
            $set = $player->getArmorInventory()->setLeggings($armor);
        } elseif (in_array($id, self::BOOTS, true)) {
            $copy = $player->getArmorInventory()->getBoots();
            $set = $player->getArmorInventory()->setBoots($armor);
        }
        if (isset($set) and $set) {
            $player->getInventory()->setItemInHand($copy);
        }
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function handleDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$entity instanceof AddonPlayer)
            return;

        if ($event->isCancelled())
            return;

        if (!$event->isApplicable($event::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN))
            return;
        $event->setCancelled(true);
    }

    /**
     * @param PlayerCreationEvent $event
     */
    public function handleCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(AddonPlayer::class);
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function handleInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $action = $event->getAction();
        $item = $player->getInventory()->getItemInHand();

        if ($player instanceof AddonPlayer) {
            if (($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR) and ($item instanceof Armor or $item->getId() === Item::ELYTRA) and $event->getBlock()->getId() !== Block::ITEM_FRAME_BLOCK) {
                $this->setArmorByType($item, $player);
                $event->setCancelled(true);
            }

            if (!$event->isCancelled() && $item->getId() == ItemIds::FISHING_ROD) {
                if ($action == PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action == PlayerInteractEvent::RIGHT_CLICK_AIR) {
                    if ($player->getInput() == AddonPlayer::KEYBOARD)
                        $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
                    else
                        $use = true;

                    if ($use)
                        $player->useRod();
                    else
                        $event->setCancelled();
                }
            }
        }
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
    public function handlePacketReceive(DataPacketReceiveEvent $event): void
    {
        $player = $event->getPlayer();
        $packet = $event->getPacket();

        if ($player instanceof AddonPlayer) {
            switch (true) {
                case $packet instanceof ActorEventPacket:
                    FakeInventory::dealXp($player, $packet);
                    break;

                case $packet instanceof FilterTextPacket:
                    AnvilInventory::writeText($player, $packet);
                    break;

                case $packet instanceof InventoryTransactionPacket:
                    $tmp = AddonManager::getTemporarilyInventory($player);

                    if ($tmp instanceof FakeInventory) {
                        $tmp->listen($player, $packet);
                    }
                    break;

                case $packet instanceof PlayerActionPacket:
                    EnchantInventory::callEvent($player, $packet);
                    break;
            }
        }
    }
}