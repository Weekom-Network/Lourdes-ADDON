<?php

declare(strict_types=1);

namespace addon\items;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\GoldenApple;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\tile\Skull;
use pocketmine\utils\TextFormat;

/**
 * Class Golden
 * @package addon\items
 */
class Golden extends GoldenApple
{

    /**
     * GoldenHead constructor.
     * @param int $meta
     */
    public function __construct(int $meta = 0)
    {
        parent::__construct($meta);
    }

    /**
     * @return EffectInstance[]
     */
    public function getAdditionalEffects(): array
    {
        if ($this->getDamage() == Skull::TYPE_HUMAN) {
            return [
                new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 9, 1),
                new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 2400)
            ];
        }
        return parent::getAdditionalEffects();
    }

    /**
     * @return string
     */
    public function getVanillaName(): string
    {
        return $this->meta == Skull::TYPE_HUMAN ? 'Golden Head' : parent::getVanillaName();
    }

    /**
     * @return Item
     */
    public static function create(): Item
    {
        return (ItemFactory::get(Item::GOLDEN_APPLE, Skull::TYPE_HUMAN))->setCustomName(TextFormat::GOLD . 'Golden Head');
    }
}