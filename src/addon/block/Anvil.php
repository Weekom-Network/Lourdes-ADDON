<?php

declare(strict_types=1);

namespace addon\block;

use addon\inventory\AnvilInventory;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Class Anvil
 * @package addon\block
 */
class Anvil extends \pocketmine\block\Anvil
{

    /**
     * @param Item $item
     * @param Player|null $player
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null): bool
    {
        $player->addWindow(new AnvilInventory($this));
        return true;
    }
}