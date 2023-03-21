<?php

declare(strict_types=1);

namespace Wertzui123\CBHeads\commands;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use Wertzui123\CBHeads\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class HeadCommand extends Command implements PluginOwned
{

    private $plugin;

    public function __construct(Main $plugin)
    {
        parent::__construct($plugin->getConfig()->getNested('command.command'), $plugin->getConfig()->getNested('command.description'), $plugin->getConfig()->getNested('command.usage'), $plugin->getConfig()->getNested('command.aliases'));
        $this->setPermission('cb-heads.command.head');
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getMessage('command.head.runIngame'));
            return;
        }
        if (!$sender->hasPermission($this->getPermission())) {
            $sender->sendMessage($this->plugin->getMessage('command.head.noPermission'));
            return;
        }
        if (empty($args)) {
            $sender->sendMessage($this->plugin->getMessage('command.head.passPlayer'));
            return;
        }
        if ($this->plugin->getServer()->getPlayerByPrefix(implode(' ', $args)) === null) {
            $sender->sendMessage($this->plugin->getMessage('command.head.notOnline'));
            return;
        }
        $player = $sender->getServer()->getPlayerByPrefix(implode(' ', $args));
        if (in_array($player->getName(), $this->plugin->getConfig()->get('blacklist')) && !$sender->hasPermission('cb-heads.bypass.blacklist.' . $player->getName()) && !$sender->hasPermission('cb-heads.bypass.blacklist')) {
            $sender->sendMessage($this->plugin->getMessage('command.head.onBlacklist'));
            return;
        }
        if (!$sender->hasPermission('cb-heads.bypass.waiting')) {
            if (time() <= $this->plugin->getLastClaim($sender) + $this->plugin->getWaitTime($sender)) {
                $sender->sendMessage($this->plugin->convertSeconds($this->plugin->getLastClaim($sender) + $this->plugin->getWaitTime($sender) - time(), $this->plugin->getMessage('command.head.wait')));
                return;
            }
            $this->plugin->setLastClaim($sender, time());
        }
        $sender->getInventory()->addItem($this->plugin->getHeadItem($player->getSkin(), $player->getName()));
        $sender->sendMessage($this->plugin->getMessage('command.head.success', ['{player}' => $player->getName()]));
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }

}