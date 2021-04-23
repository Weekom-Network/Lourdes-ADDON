<?php

declare(strict_types=1);

namespace addon\player;

use addon\AddonLoader;
use addon\entity\FishingHook;
use addon\scoreboard\Scoreboard;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

/**
 * Class AddonPlayer
 * @package addon\player
 */
class AddonPlayer extends Player
{

    /** @var int */
    public const ANDROID = 1;
    /** @var int */
    public const IOS = 2;
    /** @var int */
    public const OSX = 3;
    /** @var int */
    public const FIREOS = 4;
    /** @var int */
    public const VRGEAR = 5;
    /** @var int */
    public const VRHOLOLENS = 6;
    /** @var int */
    public const WINDOWS_10 = 7;
    /** @var int */
    public const WINDOWS_32 = 8;
    /** @var int */
    public const DEDICATED = 9;
    /** @var int */
    public const TVOS = 10;
    /** @var int */
    public const PS4 = 11;
    /** @var int */
    public const SWITCH = 12;
    /** @var int */
    public const XBOX = 13;
    /** @var int */
    public const LINUX = 20; // For linux people.

    /** @var int */
    public const KEYBOARD = 1;
    /** @var int */
    public const TOUCH = 2;
    /** @var int */
    public const CONTROLLER = 3;
    /** @var int */
    public const MOTION_CONTROLLER = 4;

    /** @var string[] */
    private $deviceOSVals = [
        self::ANDROID => 'Android',
        self::IOS => 'iOS',
        self::OSX => 'OSX',
        self::FIREOS => 'FireOS',
        self::VRGEAR => 'VRGear',
        self::VRHOLOLENS => 'VRHololens',
        self::WINDOWS_10 => 'Win10',
        self::WINDOWS_32 => 'Win32',
        self::DEDICATED => 'Dedicated',
        self::TVOS => 'TVOS',
        self::PS4 => 'PS4',
        self::SWITCH => 'Nintendo Switch',
        self::XBOX => 'Xbox',
        self::LINUX => 'Linux'
    ];

    /** @var string[] */
    private $inputVals = [
        self::KEYBOARD => 'Keyboard',
        self::TOUCH => 'Touch',
        self::CONTROLLER => 'Controller',
        self::MOTION_CONTROLLER => 'Motion-Controller'
    ];

    /** @var array */
    private $playerData = [];
    /** @var Scoreboard|null */
    private $scoreboard = null;
    /** @var null|FishingHook */
    private $fishing = null;

    /**
     * @param bool $fakeName
     * @return string
     */
    public function getName(bool $fakeName = false): string
    {
        $c = new Config(AddonLoader::getInstance()->getDataFolder() . 'nicks.yml', Config::YAML);

        if ($fakeName && $c->exists($this->username))
            return (string) $c->get($this->username);
        return parent::getName();
    }

    /**
     * @return Scoreboard|null
     */
    public function getScoreboard(): ?Scoreboard
    {
        return $this->scoreboard;
    }

    /**
     * @param AdventureSettingsPacket $packet
     * @return bool
     */
    public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool
    {
        if ($packet->entityUniqueId !== $this->getId()) {
            return false; //TODO
        }
        $handled = false;

        $isFlying = $packet->getFlag(AdventureSettingsPacket::FLYING);
        if ($isFlying !== $this->isFlying()) {
            $ev = new PlayerToggleFlightEvent($this, $isFlying);
            $ev->call();

            if ($ev->isCancelled()) {
                $this->sendSettings();
            } else { //don't use setFlying() here, to avoid feedback loops
                $this->flying = $ev->isFlying();
                $this->resetFallDistance();
            }

            $handled = true;
        }

        if ($packet->getFlag(AdventureSettingsPacket::NO_CLIP) and !$this->allowMovementCheats and !$this->isSpectator()) {
            $this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.noclip"]));
            return true;
        }
        return $handled;
    }

    /**
     * @param LoginPacket $packet
     * @return bool
     */
    public function handleLogin(LoginPacket $packet): bool
    {
        $status = parent::handleLogin($packet);

        if ($status) {
            # Check is exist ban
            $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'bans.yml', Config::YAML);

            if ($config->exists($this->getName(false))) {
                $data = $config->get($this->getName(false));

                if ($data['duration'] == 'permanent') {
                    $this->kick(TextFormat::RED . 'You are banned for ' . $data['reason'] . PHP_EOL . TextFormat::WHITE . 'Expires in: ' . TextFormat::RED . 'NEVER', false, null);
                } else {
                    if ((int)$data['duration'] > time()) {
                        $expire = (int)$data['duration'] - time();
                        $days = floor($expire / 86400);
                        $hourSeconds = $expire % 86400;
                        $hours = floor($hourSeconds / 3600);
                        $minutesSeconds = $hourSeconds % 3600;
                        $minutes = floor($minutesSeconds / 60);
                        $this->kick(TextFormat::RED . 'You are banned for ' . $data['reason'] . PHP_EOL . TextFormat::WHITE . 'Expires in: ' . TextFormat::RED . $days . ' day(s), ' . $hours . ' hour(s) and ' . $minutes . ' minute(s)', false, null);
                    } else {
                        $config->remove($this->getName(false));
                        $config->save();
                    }
                }
            }

            # Save data device
            $clientData = $packet->clientData;
            $deviceModel = (string)$clientData['DeviceModel'];
            $deviceOS = (int)$clientData['DeviceOS'];

            if (trim($deviceModel) == '') {
                switch ($deviceOS) {
                    case self::ANDROID:
                        $deviceOS = self::LINUX;
                        $deviceModel = 'Linux';
                        break;
                    case self::XBOX:
                        $deviceModel = 'Xbox One';
                        break;
                }
            }
            $clientData['DeviceModel'] = $deviceModel;
            $clientData['DeviceOS'] = $deviceOS;
            $this->playerData = $clientData;
        }
        return $status;
    }

    /**
     * @param LevelSoundEventPacket $packet
     * @return bool
     */
    public function handleLevelSoundEvent(LevelSoundEventPacket $packet): bool
    {
        $sound = $packet->sound;

        if (in_array($sound, [41, 42, 43])) {
            return false;
        }
        return parent::handleLevelSoundEvent($packet);
    }

    /**
     * @param bool $strval
     * @return int|string
     */
    public function getDeviceOS(bool $strval = false)
    {
        $osVal = intval($this->playerData['DeviceOS']);
        $result = $strval ? 'Unknown' : $osVal;

        if ($strval == true and isset($this->deviceOSVals[$osVal])) {
            $result = strval($this->deviceOSVals[$osVal]);
        }
        return $result;
    }

    /**
     * @param bool $strval
     * @return int|string
     */
    public function getInput(bool $strval = false)
    {
        $input = intval($this->playerData['CurrentInputMode']);
        $result = ($strval == true) ? 'Unknown' : $input;

        if ($strval == true and isset($this->inputVals[$input])) {
            $result = strval($this->inputVals[$input]);
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isFishing(): bool
    {
        return $this->fishing !== null;
    }

    /**
     * Starts fishing.
     */
    private function startFishing(): void
    {
        $player = $this->getPlayer();

        if ($player !== null && !$this->isFishing()) {
            $tag = Entity::createBaseNBT($player->add(0.0, $player->getEyeHeight(), 0.0), $player->getDirectionVector(), floatval($player->yaw), floatval($player->pitch));
            $rod = Entity::createEntity('FishingHook', $player->getLevel(), $tag, $player);

            if ($rod !== null) {
                $x = -sin(deg2rad($player->yaw)) * cos(deg2rad($player->pitch));
                $y = -sin(deg2rad($player->pitch));
                $z = cos(deg2rad($player->yaw)) * cos(deg2rad($player->pitch));
                $rod->setMotion(new Vector3($x, $y, $z));
            }

            if ($rod != null && $rod instanceof FishingHook) {
                $ev = new ProjectileLaunchEvent($rod);
                $ev->call();

                if ($ev->isCancelled()) {
                    $rod->flagForDespawn();
                } else {
                    $rod->spawnToAll();
                    $this->fishing = $rod;
                    $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
                }
            }
        }
    }

    /**
     * @param bool $click
     * @param bool $killEntity
     */
    public function stopFishing(bool $click = true, bool $killEntity = true): void
    {
        if ($this->isFishing() and $this->fishing instanceof FishingHook) {
            $rod = $this->fishing;

            if ($click === true) {
                $rod->reelLine();
            } elseif ($rod !== null) {
                if (!$rod->isClosed() && $killEntity === true) {
                    $rod->kill();
                    $rod->close();
                }
            }
        }
        $this->fishing = null;
    }

    /**
     * @param bool $animate
     * @return bool
     *
     * Player uses the fishing rod.
     */
    public function useRod(bool $animate = false): bool
    {
        $exec = !$this->isSpectator() and !$this->isImmobile();

        if ($exec) {
            $players = $this->getLevel()->getPlayers();

            if ($this->isFishing()) {
                $this->stopFishing();
            } else {
                $this->startFishing();
            }

            if ($animate) {
                $pkt = new AnimatePacket();
                $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                $pkt->entityRuntimeId = $this->getId();
                $this->getServer()->broadcastPacket($players, $pkt);
            }
        }
        return $exec;
    }

    public function join(): void
    {
        # Create scoreboard
        if ($this->scoreboard == null)
            $this->scoreboard = new Scoreboard($this);

        # Save data player in config
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);

        if (!$config->exists($this->getName(false))) {
            $config->set($this->getName(false), [
                'address' => $this->getAddress(),
                'uuid' => $this->getUniqueId()->toString(),
                'xuid' => $this->getXuid(),
                'cid' => $this->getClientId()
            ]);
            $config->save();
        } else {
            $data = $config->get($this->getName(false));
            $data['address'] = $this->getAddress();
            $data['uuid'] = $this->getUniqueId()->toString();
            $data['xuid'] = $this->getXuid();
            $data['cid'] = $this->getClientId();
            $config->set($this->getName(false), $data);
            $config->save();
        }
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
    }

    /**
     * @return string|null
     */
    public function getRank(): ?string
    {
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
        $data = $config->getAll();
        
        if (!isset($data[$this->getName(true)]))
            return null;
        $data = $data[$this->getName(true)];
        return isset($data['rank']) ? $data['rank'] : null;
    }

    /**
     * @param string $rank
     */
    public function setRank(string $rank): void
    {
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
        $data = (array) $config->get($this->getName(false));
        $data['rank'] = $rank;
        $config->set($this->getName(false), $data);
        $config->save();
    }

    public function removeRank(): void
    {
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
        $data = $config->get($this->getName(false));
        unset($data['rank']);
        $config->set($this->getName(false), $data);
        $config->save();
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
        $data = $config->getAll();

        if (!isset($data[$this->getName(true)]))
            return null;
        $data = $data[$this->getName(true)];
        return isset($data['prefix']) ? $data['prefix'] : null;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
        $data = (array) $config->get($this->getName(false));
        $data['prefix'] = $prefix;
        $config->set($this->getName(false), $data);
        $config->save();
    }

    public function removePrefix(): void
    {
        $config = new Config(AddonLoader::getInstance()->getDataFolder() . 'players.yml', Config::YAML);
        $data = $config->get($this->getName(false));
        unset($data['prefix']);
        $config->set($this->getName(false), $data);
        $config->save();
    }
}