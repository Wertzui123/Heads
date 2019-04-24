<?php

/*
 * Forked from:
 *
 *  PlayerHead - a Altay and PocketMine-MP plugin to add player head on server
 *  Copyright (C) 2018 Enes Yıldırım
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2019 Wertzui123
 */

declare(strict_types=1);

namespace Enes5519\PlayerHead\entities;

use Enes5519\PlayerHead\PlayerHead;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\UUID;
use pocketmine\utils\TextFormat;

class HeadEntity extends Human
{

    public const HEAD_GEOMETRY = '{"geometry.player_head":{"texturewidth":64,"textureheight":64,"bones":[{"name":"head","pivot":[0,24,0],"cubes":[{"origin":[-4,0,-4],"size":[8,8,8],"uv":[0,0]}]}]}}';

    public $width = 0.5, $height = 0.6;

    protected function initEntity(): void
    {
        $this->setMaxHealth(1);
        parent::initEntity();
        $this->setSkin(new Skin($this->skin->getSkinId(), $this->skin->getSkinData(), "", "geometry.player_head", self::HEAD_GEOMETRY));
    }

    public function hasMovementUpdate(): bool
    {
        return false;
    }

    public function getUniqueId(): UUID
    {
        return $this->uuid;
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent and $source->getDamager() instanceof Player) {
            $player = $source->getDamager();
            $pname = $player->getName();
            $plot = $player->getServer()->getPluginManager()->getPlugin("MyPlot")->getPlotByPosition($this);

            if ($player->hasPermission("cb-heads.kill")) {
                            if(($plot !== null && $plot->owner == $player->getName()) || ($plot !== null && in_array($pname, $plot->helpers)) || ($plot !== null && in_array("*", $plot->helpers)) || $player->hasPermission("myplot.admin.build")){

                    parent::attack($source);

                }
            }
        }
    }

    public function getDrops(): array
    {
        return [PlayerHead::getPlayerHeadItem($this->getSkin())];
    }

}
