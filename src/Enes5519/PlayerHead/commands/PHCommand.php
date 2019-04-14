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

namespace Enes5519\PlayerHead\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerJoinEvent;
use Enes5519\PlayerHead\PlayerHead;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\entity\Skin;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listeners;

class PHCommand extends Command{

public function __construct(PlayerHead $plugin) {
	    $config = new Config($plugin->getDataFolder() . "config.yml", Config::YAML);
        $desc = $config->get("command_description");
		$usage = $config->get("command_usage");
		$command = $config->get("head_command");
		$alias = $config->get("command_alias");
		parent::__construct("$command", "$desc", "$usage", $alias);
		$this->setPermission("cb-heads.gethead");
		$this->plugin = $plugin;
	}

	public function onEnable() : void{ 
	    $this->saveResource("config.yml");
		$this->saveResource("blacklist.yml");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

		public function onJoin(PlayerJoinEvent $event){
if(!file_exists($this->plugin->getDataFolder()/* . "players/"*/ . $event->getPlayer()->getName() . ".yml")){
$cfg = new Config($this->plugin->getDataFolder()/* . "players/"*/ . $event->getPlayer()->getName() . ".yml", Config::YAML);
$player = $event->getPlayer();
$today = new \DateTime("now");
$now = $today->format("d.m.Y H:i");
$cfg->set("until", $now);
$cfg->save();

return $ph;

}
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		
		$name = $sender->getName();
		$player = $sender->getServer()->getPlayer(implode(" ", $args));
		$cfg = new Config($this->plugin->getDataFolder()/* . "players/"*/ . $name . ".yml", Config::YAML);
		$config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
		$blist = new Config($this->plugin->getDataFolder() . "blacklist.yml", Config::YAML);
		$until = $cfg->get("until");
		$today = new \DateTime("now");
		$nopermission = $config->get("no_permission");
		$onblacklist = $config->get("on_blacklist");
		$blacklist = $blist->get("blacklisted_players");
		//$usage = $config->get("usage");
		$alreadygothead = $config->get("already_got_head");
		$alreadygothead = str_replace("{until}", $until, $alreadygothead);
		$gotheadsucces = $config->get("got_head_succes");
		//$headcommand = $config->get("command_wich_will_be_executed");
		$timeformat = $config->get("time_format");
		$waittime = $config->get("wait_time");
		$today = new \DateTime("now");
		$now = $today->format($timeformat);
		$until2 = date ($timeformat, strtotime ($now ."+" . $waittime));
		$runingame = $config->get("run_ingame");
		$notonline = $config->get("player_is_not_online");
		
		 if(!($sender instanceof Player)){
			 $sender->sendMessage($runingame);
		 }else{

		if(empty($args)){
			throw new InvalidCommandSyntaxException();
		}

		if($player == !null){
		$name = $player->getName();
		$onblacklist = str_replace("{player}", $name, $onblacklist);
		if($player instanceof Player){
			
			if(!in_array($name, $blacklist) or $sender->hasPermission("cb-heads.blacklist.$name") or $sender->hasPermission("cb-heads.blacklist")){
			
			if($sender->hasPermission("cb-heads.gethead")){
			
			if($now >= $until or $sender->hasPermission("cb-heads.gethead.bypass")){
				
			/*$headcommand = str_replace("{player}", $name, $headcommand);
		    $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), $headcommand);*/
		    $cfg->set("until", $until2);
		    $cfg->save();
			$sender->getInventory()->addItem(PlayerHead::getPlayerHeadItem(new Skin($player->getName(), $player->getSkin()->getSkinData())));
		    $gotheadsucces = str_replace("{got}", $player->getName(), $gotheadsucces);
			$sender->sendMessage($gotheadsucces);
			
			}else{
				$sender->sendMessage($alreadygothead);
			}
			}else{
				$sender->sendMessage($nopermission);
			}
			}else{
				$sender->sendMessage($onblacklist);
			}
		}
		 }else{
			 $sender->sendMessage($notonline);
		 }
		 }

		return true;
	}
}
