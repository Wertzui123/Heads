<?php

namespace Wertzui123\CBHeads\entities;

use pocketmine\level\Position;
use Wertzui123\CBHeads\Main;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;

class Head extends Human
{

    const HEAD_GEOMETRY = '{"format_version": "1.12.0", "minecraft:geometry": [{"description": {"identifier": "geometry.player_head", "texture_width": 64, "texture_height": 64, "visible_bounds_width": 2, "visible_bounds_height": 4, "visible_bounds_offset": [0, 0, 0]}, "bones": [{"name": "Head", "pivot": [0, 24, 0], "cubes": [{"origin": [-4, 0, -4], "size": [8, 8, 8], "uv": [0, 0]}, {"origin": [-4, 0, -4], "size": [8, 8, 8], "inflate": 0.5, "uv": [32, 0]}]}]}]}';
    public $width = 0.3, $height = 0.3;

    protected function initEntity(): void
    {
        $this->setMaxHealth(1);
        $this->setSkin(new Skin($this->skin->getSkinId(), $this->skin->getSkinData(), '', 'geometry.player_head', self::HEAD_GEOMETRY));
        $this->setImmobile();
        parent::initEntity();
    }

    public function hasMovementUpdate(): bool
    {
        return false;
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void
    {
        if (!$source instanceof EntityDamageByEntityEvent) return;
        if (!$source->getDamager() instanceof Player) return;
        if ($source->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK) return;
        /** @var Player $player */
        $player = $source->getDamager();
        $pos = Position::fromObject($this->floor(), $this->getLevel());
        $block = BlockFactory::get(BlockIds::SKULL_BLOCK, 3, $pos);
        $event = new BlockBreakEvent($player, $block, $player->getInventory()->getItemInHand(), false, $this->getDrops());
        $event->call();
        if ($event->isCancelled()) {
            $source->setCancelled();
            return;
        }
        parent::despawnFromAll();
        parent::kill();
    }

    public function getDrops(): array
    {
        return [Main::$instance->getHeadItem($this->getSkin(), $this->namedtag->getString('Player', 'Player'))];
    }

}