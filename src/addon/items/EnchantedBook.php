<?php

declare(strict_types=1);

namespace addon\items;

use pocketmine\item\Item;

/**
 * Class EnchantedBook
 * @package addon\items
 */
class EnchantedBook extends Item
{

    /**
     * EnchantedBook constructor.
     * @param int $meta
     */
    public function __construct(int $meta = 0)
    {
        parent::__construct(self::ENCHANTED_BOOK, $meta, 'Enchanted Book');
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int
    {
        return 1;
    }
}