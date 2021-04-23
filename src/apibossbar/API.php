<?php

declare(strict_types=1);

namespace apibossbar;

use pocketmine\plugin\Plugin;

/**
 * Class API
 * @package apibossbar
 */
class API
{

    /**
     * @param Plugin $plugin
     */
    public static function load(Plugin $plugin): void
    {
        PacketListener::register($plugin);
    }
}
