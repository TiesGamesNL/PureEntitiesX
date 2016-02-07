<?php

namespace milk\entitymanager\entity\monster\walking;

use milk\entitymanager\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\entity\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;

class Skeleton extends WalkingMonster implements ProjectileSource{
    const NETWORK_ID = 34;

    public $width = 0.65;
    public $height = 1.8;

    public function getName() : string{
        return "Skeleton";
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE) && !$this->isOnFire()){
            $this->setOnFire(5);
        }

        Timings::$timerEntityBaseTick->startTiming();
        return $hasUpdate;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;
        
            $f = 1.2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
                    new DoubleTag("", $this->y + 1.62),
                    new DoubleTag("", $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", -sin($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ]),
            ]);

            /** @var Projectile $arrow */
            $arrow = Entity::createEntity("Arrow", $this->chunk, $nbt, $this);

            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, $f);
            $this->server->getPluginManager()->callEvent($ev);

            $projectile = $ev->getProjectile();
            if($ev->isCancelled()){
                $projectile->kill();
            }elseif($projectile instanceof Projectile){
                $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($projectile));
                if($launch->isCancelled()){
                    $projectile->kill();
                }else{
                    $projectile->spawnToAll();
                    $this->level->addSound(new LaunchSound($this), $this->getViewers());
                }
            }
        }
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [
                Item::get(Item::BONE, 0, mt_rand(0, 2)),
                Item::get(Item::ARROW, 0, mt_rand(0, 3)),
            ];
        }
        return [];
    }

}