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

namespace Enes5519\PlayerHead;

use Enes5519\PlayerHead\commands\PHCommand;
use Enes5519\PlayerHead\entities\HeadEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class PlayerHead extends PluginBase implements Listener
{
    public $configversion;
    private static $config;
    public function onEnable(): void
    {
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        self::$config = $config;
        $this->configversion = "2.4";
        $this->ConfigUpdater();
        $this->saveResource("blacklist.yml");
        $this->saveResource("players.yml");

        Entity::registerEntity(HeadEntity::class, true, ["PlayerHead"]);
        $this->getServer()->getCommandMap()->register("head", new PHCommand($this));
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $position = $player->getPosition();

        if ($player->hasPermission("cb-heads.spawn") and ($item = $player->getInventory()->getItemInHand())->getId() == Item::MOB_HEAD) {
            if (!$event->isCancelled()) {
                $blockData = $item->getCustomBlockData() ?? new CompoundTag();
                $skin = $blockData->getCompoundTag("Skin");
                if ($skin !== null) {
                    $this->spawnPlayerHead($skin, $event->getBlock(), self::getYaw($event->getBlock(), $player));
                    if (!$player->isCreative()) {
                        $item->pop();
                        $player->getInventory()->setItemInHand($item);
                    }
                    $event->setCancelled(true);
                }
            }
        }
    }

    /**
     * @param CompoundTag|Skin $skin
     * @param Position $pos
     * @param float|null $yaw
     * @param float|null $pitch
     * @return HeadEntity
     */
    public static function spawnPlayerHead($skin, Position $pos, float $yaw = null, float $pitch = null): HeadEntity
    {
        $skinTag = $skin instanceof Skin ? self::skinToTag($skin) : $skin;
        $nbt = HeadEntity::createBaseNBT($pos->add(0.5, 0, 0.5), null, $yaw ?? 0.0, $pitch ?? 0.0);
        $nbt->setTag($skinTag);
        $head = new HeadEntity($pos->level, $nbt);
        $head->spawnToAll();

        return $head;
    }

    public static function getYaw(Vector3 $pos, Vector3 $target): float
    {
        $xDist = $target->x - $pos->x;
        $zDist = $target->z - $pos->z;
        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if ($yaw < 0) {
            $yaw += 360.0;
        }

        foreach ([45, 90, 135, 180, 225, 270, 315, 360] as $direction) {
            $min = min($yaw, $direction);
            if ($min == $yaw) {
                return $direction;
            }
        }

        return $yaw;
    }

    /**
     * @param CompoundTag|Skin $skin
     * @return Item
     */
    public static function getPlayerHeadItem($skin): Item
    {

        if ($skin instanceof Skin) {
            $skinTag = self::skinToTag($skin);
        } else {
            $skinTag = $skin;
        }
        $name = $skinTag->getString("Name", "Player");

        $config = self::$config;
        $headformat = $config->get("head_format") ?? "§r§6" . $name. "'s Head";

        $item = ItemFactory::get(Item::MOB_HEAD, 3);
        $tag = $item->getCustomBlockData() ?? new CompoundTag();
        $tag->setTag($skinTag);
        $item->setCustomBlockData($tag);
        $headformat = str_replace("{name}", $name, $headformat);
        $item->setCustomName(/*"§r§6" . $name. "'s Head"*/ $headformat);
        return $item;
    }

    public static function skinToTag(Skin $skin): CompoundTag
    {
        return new CompoundTag("Skin", [
            new StringTag("Name", $skin->getSkinId()),
            new ByteArrayTag("Data", $skin->getSkinData())
        ]);
    }

    public static function tagToSkin(CompoundTag $tag): Skin
    {
        return new Skin(
            $tag->getString("Name"),
            $tag->getByteArray("Data")
        );
    }

    public function ConfigArray()
    {
        $c = new Config($this->getDataFolder() . "config.yml");
        $c = $c->getAll();
        return $c;
    }

    public function Config()
    {
        $c = new Config($this->getDataFolder() . "config.yml");
        return $c;
    }

    public function ConfigUpdater()
    {
        if (file_exists($this->getDataFolder() . "config.yml")) {
                    $c = $this->ConfigArray();
                    $cv = $c["config_version"] ?? 0;
            if ($cv != $this->configversion) {
                $this->getLogger()->info("§cYour Config isn't the latest. §6We renamed your old config to §bconfig-" . $cv . ".yml §6and created a new config.yml. §aHave fun!");
                rename($this->getDataFolder() . "config.yml", "config-" . $cv . ".yml");
                $this->saveResource("config.yml");
            }
        } else {
            $this->saveResource("config.yml");

        }
    }
}
