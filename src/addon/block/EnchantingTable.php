<?php

declare(strict_types=1);

namespace addon\block;

use addon\inventory\EnchantInventory;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Class EnchantingTable
 * @package addon\block
 */
class EnchantingTable extends \pocketmine\block\EnchantingTable
{

    /**
     * @param Item $item
     * @param Player|null $player
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null): bool
    {
        $player->addWindow(new EnchantInventory($this));
        return true;
    }
}