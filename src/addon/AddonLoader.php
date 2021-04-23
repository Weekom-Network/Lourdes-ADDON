<?php

declare(strict_types=1);

namespace addon;

use addon\block\Anvil;
use addon\block\EnchantingTable;
use addon\entity\FishingHook;
use addon\items\EnchantedBook;
use addon\items\Golden;
use apibossbar\API;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Skull;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use rcon\RCONClient;

/**
 * Class AddonLoader
 * @package addon
 */
class AddonLoader extends PluginBase
{

    /** @var self */
    private static $instance;

    public function onLoad()
    {
        self::$instance = $this;
    }

    public function onEnable()
    {
        # Set title network
        $this->getServer()->getNetwork()->setName(TextFormat::LIGHT_PURPLE . 'Weekom Server' . TextFormat::GRAY);

        # Create file players
        $this->saveDefaultConfig();
        new Config($this->getDataFolder() . 'players.yml', Config::YAML);
        new Config($this->getDataFolder() . 'stats.yml', Config::YAML);
        new Config($this->getDataFolder() . 'bans.yml', Config::YAML);
        new Config($this->getDataFolder() . 'nicks.yml', Config::YAML);

        # Register listener
        $this->getServer()->getPluginManager()->registerEvents(new AddonListener(), $this);

        # Register entity
        Entity::registerEntity(FishingHook::class, false, ['FishingHook', 'minecraft:fishing_hook']);

        # Register blocks
        BlockFactory::registerBlock(new Anvil(), true);
        BlockFactory::registerBlock(new EnchantingTable(), true);

        # Register items
        ItemFactory::registerItem(new EnchantedBook, true);
        ItemFactory::registerItem(new Golden, true);
        Item::initCreativeItems();

        # Register crafting
        $this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(['ggg', 'ghg', 'ggg'], ['g' => ItemFactory::get(ItemIds::GOLD_INGOT, 0, 1), 'h' => ItemFactory::get(ItemIds::SKULL, Skull::TYPE_HUMAN, 1)], [ItemFactory::get(ItemIds::GOLDEN_APPLE, Skull::TYPE_HUMAN)->setCustomName(TextFormat::GOLD . 'Golden Head')]));
        $this->getServer()->getCraftingManager()->buildCraftingDataCache();

        # BossBar API
        API::load($this);

        # InvMenu API
        InvMenuHandler::register($this);

        # RCONClient
        $data = $this->getConfig()->getAll()['rcon-server'];
        $rcon = new RCONClient($data['address'], $data['password'], $this->getServer()->getLogger(), $data['port'], $data['timeout']);
        $rcon->sendCommand('status uhc on');
    }

    public function onDisable()
    {
        # RCONClient
        $data = $this->getConfig()->getAll()['rcon-server'];
        $rcon = new RCONClient($data['address'], $data['password'], $this->getServer()->getLogger(), $data['port'], $data['timeout']);
        $rcon->sendCommand('status uhc off');

        # Config
        $nicks = new Config($this->getDataFolder() . 'nicks.yml', Config::YAML);
        $nicks->setAll([]);
        $nicks->save(); // TODO: Clear nicks
    }

    /**
     * @return AddonLoader
     */
    public static function getInstance(): AddonLoader
    {
        return self::$instance;
    }
}