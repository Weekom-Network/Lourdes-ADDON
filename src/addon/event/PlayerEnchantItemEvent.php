<?php

declare(strict_types=1);

namespace addon\event;

use addon\player\AddonPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\item\Item;

/**
 * Class PlayerEnchantItemEvent
 * @package addon\event
 */
class PlayerEnchantItemEvent extends Event implements Cancellable
{
    /** @var AddonPlayer */
    private $player;
    /** @var Item */
    private $item;

    /**
     * @param AddonPlayer $player
     * @param Item $item
     */
    public function __construct(AddonPlayer $player, Item $item)
    {
        $this->player = $player;
        $this->item = $item;
    }

    /**
     * @return AddonPlayer
     */
    public function getPlayer(): AddonPlayer
    {
        return $this->player;
    }

    /**
     * Return an enchanted item
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }
}