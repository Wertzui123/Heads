<?php

declare(strict_types=1);

namespace Wertzui123\CBHeads;

use pocketmine\Player;
use pocketmine\utils\Config;
use Wertzui123\CBHeads\commands\headCommand;
use Wertzui123\CBHeads\entities\Head;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{

    const CONFIG_VERSION = 3.0;

    /** @var Main */
    public static $instance;
    /** @var Config */
    private $playersFile;
    /** @var Config */
    private $stringsFile;

    public function onEnable(): void
    {
        self::$instance = $this;
        $this->saveResource('head.json');
        $this->saveResource('strings.yml');
        $this->configUpdater();
        $this->playersFile = new Config($this->getDataFolder() . 'players.json', Config::JSON);
        $this->stringsFile = new Config($this->getDataFolder() . 'strings.yml', Config::YAML);
        Entity::registerEntity(Head::class, true);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register('CB-Heads', new headCommand($this));
    }

    /**
     * Returns a message by the given key
     * @param string $message
     * @param array $replace
     * @return string
     */
    public function getMessage($message, $replace = [])
    {
        return str_replace(array_keys($replace), $replace, $this->stringsFile->getNested($message));
    }

    /**
     * Returns the timestamp of when the given player claimed a head the last time or -1 they never have
     * @param Player $player
     * @return int
     */
    public function getLastClaim(Player $player)
    {
        return $this->playersFile->get(strtolower($player->getName()), -1);
    }

    /**
     * Sets the timestamp of when the given player claimed a head the last time
     * @param Player $player
     * @param int $timestamp
     */
    public function setLastClaim(Player $player, $timestamp)
    {
        $this->playersFile->set(strtolower($player->getName()), $timestamp);
    }

    /**
     * Returns the amount of seconds a player at least has to wait between claiming heads
     * @param Player $player
     * @return int
     */
    public function getWaitTime(Player $player)
    {
        foreach (array_reverse($this->getConfig()->get('wait_time')) as $group => $time) {
            if ($player->hasPermission('cb-heads.waiting.' . $group)) {
                return $time;
            }
        }
        return $this->getConfig()->get('wait_time')['default'];
    }

    /**
     * Spawns a head entity
     * @param CompoundTag|Skin $skin
     * @param Position $pos
     * @param string $name
     * @param float|null $yaw
     * @param float|null $pitch
     * @return Head
     */
    public static function spawnHead($skin, $name, Position $pos, $yaw = null, $pitch = null): Head
    {
        $skinTag = $skin instanceof Skin ? self::skinToTag($skin) : $skin;
        $nbt = Head::createBaseNBT($pos->add(0.5, 0, 0.5), null, $yaw ?? 0.0, $pitch ?? 0.0);
        $nbt->setTag($skinTag);
        $nbt->setString('Player', $name);
        $head = new Head($pos->level, $nbt);
        $head->spawnToAll();
        return $head;
    }

    /**
     * Returns a yaw relative to the given vectors
     * @param Vector3 $pos
     * @param Vector3 $target
     * @return float
     */
    public static function getYaw(Vector3 $pos, Vector3 $target): float
    {
        $yaw = atan2($target->z - $pos->z, $target->x - $pos->x) / M_PI * 180 - 90;
        if ($yaw < 0) {
            $yaw += 360.0;
        }
        foreach ([45, 90, 135, 180, 225, 270, 315, 360] as $direction) {
            if ($yaw <= $direction) {
                return $direction;
            }
        }
        return $yaw;
    }

    /**
     * Returns a head item
     * @param CompoundTag|Skin $skin
     * @param string $name [optional]
     * @return Item
     */
    public function getHeadItem($skin, $name = null): Item
    {
        $skin = $skin instanceof Skin ? self::skinToTag($skin) : $skin;
        $item = ItemFactory::get(Item::MOB_HEAD, 3);
        $tag = $item->getCustomBlockData() ?? new CompoundTag();
        $tag->setTag($skin);
        $tag->setString('Player', $name);
        $item->setCustomBlockData($tag);
        $item->setCustomName(str_replace('{name}', $name ?? $skin->getString('Name', 'Player'), $this->getConfig()->get('head_format')));
        return $item;
    }

    /**
     * Converts a skin to nbt
     * @param Skin $skin
     * @return CompoundTag
     */
    public static function skinToTag(Skin $skin): CompoundTag
    {
        return new CompoundTag('Skin', [
            new StringTag('Name', $skin->getSkinId()),
            new ByteArrayTag('Data', $skin->getSkinData())
        ]);
    }

    /**
     * Converts a nbt to skin
     * @param CompoundTag $tag
     * @return Skin
     */
    public static function tagToSkin(CompoundTag $tag): Skin
    {
        return new Skin(
            $tag->getString('Name'),
            $tag->getByteArray('Data')
        );
    }

    /**
     * Checks whether the config version is the latest and updates the config files if it isn't
     */
    public function configUpdater()
    {
        if (!file_exists($this->getDataFolder() . 'config.yml')) {
            $this->saveResource('config.yml');
            return;
        }
        if ($this->getConfig()->get('config-version') !== self::CONFIG_VERSION) {
            $this->getLogger()->info("§cYour Config isn't the latest. §6CB-Heads renamed your old config to §bconfig-" . $this->getConfig()->get("config-version") . ".yml §6and created a new config.yml. §aHave fun!");
            rename($this->getDataFolder() . 'config.yml', 'config-' . $this->getConfig()->get('config-version') . '.yml');
            rename($this->getDataFolder() . 'strings.yml', 'strings-' . $this->getConfig()->get('config-version') . '.yml');
            $this->saveResource('config.yml');
            $this->saveResource('strings.yml');
        }
    }

    /**
     * Converts seconds to hours, minutes and seconds
     * @param int $seconds
     * @param string $message
     * @return string
     */
    public function convertSeconds($seconds, $message)
    {
        $days = floor($seconds / 86400);
        $hours = floor($seconds / 3600) % 24;
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
        return str_replace(['{days}', '{hours}', '{minutes}', '{seconds}'], [$days, $hours, $minutes, $seconds], $message);
    }

    public function onDisable()
    {
        $this->playersFile->save();
    }

}