<?php

namespace Wertzui123\CBHeads;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\CompoundTag;

class EventListener implements Listener
{

    private $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        if ($event->isCancelled() || ($event->getPlayer()->getInventory()->getItemInHand()->getCustomBlockData() ?? new CompoundTag())->getCompoundTag('Skin') === null) return;
        $event->setCancelled();
        $this->plugin->spawnHead(($event->getPlayer()->getInventory()->getItemInHand()->getCustomBlockData() ?? new CompoundTag())->getCompoundTag('Skin'), ($event->getPlayer()->getInventory()->getItemInHand()->getCustomBlockData() ?? new CompoundTag())->getString('Player'), $event->getBlock(), Main::getYaw($event->getBlock(), $event->getPlayer()));
        if (!$event->getPlayer()->isCreative()) {
            $event->getPlayer()->getInventory()->setItemInHand($event->getPlayer()->getInventory()->getItemInHand()->pop());
        }
    }

}