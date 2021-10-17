<?php

namespace Wertzui123\CBHeads;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

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
            $item = $event->getPlayer()->getInventory()->getItemInHand();
            $item->pop();
            $event->getPlayer()->getInventory()->setItemInHand($item);
        }
    }

    public function onDamage(PlayerDeathEvent $event)
    {
        if ($event->getPlayer()->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
            /** @var EntityDamageByEntityEvent $lastDamage */
            $lastDamage = $event->getPlayer()->getLastDamageCause();
            if ($lastDamage->getDamager() instanceof Player) {
                if ($lastDamage->getDamager() === $event->getPlayer()) {
                    if (!$this->plugin->getConfig()->get('drop_on_suicide')) return;
                } else {
                    if (!$this->plugin->getConfig()->get('drop_on_killed')) return;
                }
                $drops = $event->getDrops();
                $drops[] = $this->plugin->getHeadItem($event->getPlayer()->getSkin(), $event->getPlayer()->getName());
                $event->setDrops($drops);
            }
        }
    }

}