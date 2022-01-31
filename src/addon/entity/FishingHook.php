<?php

declare(strict_types=1);

namespace addon\entity;

use addon\player\AddonPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;

/**
 * Class FishingHook
 * @package addon\entity
 */
class FishingHook extends Projectile
{

    /** @var int */
    public const NETWORK_ID = self::FISHING_HOOK;

    /** @var bool */
    public $caught = false;
    /** @var float */
    public $width = 0.2;
    /** @var float */
    public $height = 0.2;
    /** @var float */
    public $gravity = 0.08;
    /** @var float */
    public $drag = 0.05;

    /* @var Entity|null */
    private $attachedEntity = null;

    /**
     * @param int $currentTick
     * @return bool
     */
    public function onUpdate(int $currentTick): bool
    {
        if ($this->isFlaggedForDespawn() or !$this->isAlive()) {
            return false;
        }

        if ($this->getOwningEntity() === null) {
            $this->flagForDespawn();
            return false;
        }
        $this->timings->startTiming();
        $update = parent::onUpdate($currentTick);

        if (!$this->isCollidedVertically) {
            $this->motion->x *= 1.13;
            $this->motion->z *= 1.13;
            $this->motion->y -= $this->gravity * -0.04;
            if ($this->isUnderwater()) {
                $this->motion->z = 0;
                $this->motion->x = 0;
                $difference = floatval($this->getWaterHeight() - $this->y);

                if ($difference > 0.15)
                    $this->motion->y += 0.1;
                else
                    $this->motion->y += 0.01;
            }
            $update = true;
        } elseif ($this->isCollided and $this->keepMovement) {
            $this->motion->x = 0;
            $this->motion->y = 0;
            $this->motion->z = 0;
            $this->keepMovement = false;
            $update = true;
        }

        if ($this->isOnGround())
            $this->motion->y = 0;

        if ($this->attachedEntity !== null) {
            $pos = $this->attachedEntity->asPosition();

            if ($pos !== $this->getPosition()) {
                $this->setPosition($pos->add(0, 1));
            }
            $this->setMotion($this->attachedEntity->getMotion());
        }
        $source = $this->getOwningEntity();

        if ($source != null and $source instanceof Player) {
            $p = $source->getPlayer();
            $inv = $p->getInventory();
            $itemInHand = $inv->getItemInHand();
            $kill = false;

            if ($source->distance($this) > 35)
                $kill = true;
            elseif ($itemInHand->getId() !== Item::FISHING_ROD)
                $kill = true;

            if ($kill === true) {
                $this->kill();
                $this->close();

                if ($p instanceof AddonPlayer and $p->isFishing())
                    $p->stopFishing();
            }
        }
        $this->timings->stopTiming();
        return $update;
    }

    public function getWaterHeight(): int
    {
        $floorY = $this->getFloorY();
        $result = $floorY;

        for ($y = $floorY; $y < 256; $y++) {
            $id = $this->getLevel()->getBlockIdAt($this->getFloorX(), $y, $this->getFloorZ());

            if ($id === 0) {
                $result = $y;
                break;
            }
        }
        return $result;
    }

    public function reelLine(): void
    {
        $e = $this->getOwningEntity();

        if ($e instanceof AddonPlayer and $this->caught === true)
            $this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_TEASE, 0, $this->getLevel()->getPlayers());

        if (!$this->closed) {
            $this->kill();
            $this->close();
        }
    }

    /**
     * @param Entity $entityHit
     * @param RayTraceResult $hitResult
     */
    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $damage = $this->getResultDamage();
        $this->attachedEntity = $entityHit;

        if ($damage >= 0) {
            if ($this->getOwningEntity() === null)
                $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            else
                $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            $entityHit->attack($ev);

            if ($this->isOnFire()) {
                $ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
                $ev->call();

                if (!$ev->isCancelled())
                    $entityHit->setOnFire($ev->getDuration());
            }
        }
    }

    /**
     * @return int
     */
    public function getResultDamage(): int
    {
        return parent::getResultDamage();
    }
}
