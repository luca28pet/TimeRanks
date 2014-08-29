<?php

namespace TimeRanks;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\utils\config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener{
	public function OnEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->times = new Config($this->getDataFolder()."times.yml", Config::YAML);
		$this->values = new Config($this->getDataFolder()."values.yml", Config::YAML,
			"firstgroup" => array(
				"name" => "Newly Spawned",
				"minute" => 0,
				"blocks" => array(
					0,1,2,3,4,5,6,12,13,16,17,35,50,54,60,61,64
                        		
				),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"secondgroup" => array(
				"name" => "Tree Puncher",
				"minute" => 30,
				"blocks" => array(
					0,1,2,3,4,5,6,12,13,16,17,35,50,60,61,64,245,43,44,53,54,67,134,135,136,139,157,158,163,164,173
                        		
				)
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"thirdgroup" => array(
				"name" => "Coal User",
				"minute" => 60,
				"blocks" => array(
					0,1,2,3,4,5,6,12,13,16,17,35,50,60,61,64,245,43,44,53,54,67,134,135,136,139,157,158,163,164,173
                        		
				)
				"levels" => array(
					world, survival
				),
				"chat" => true
			),//TODO: add default block list
			"fourthgroup" => array(
				"name" => "Iron Miner",
				"minute" => 180,
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"fifthgroup" => array(
				"name" => "Gold Player",
				"minute" => 300,
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"sixthgroup" => array(
				"name" => "Diamond User",
				"minute" => 600,
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"seventhgroup" => array(
				"name" => "Server Pro",
				"minute" => 1440,
				"levels" => array(
					world, survival
				),
				"chat" => true
			)
		);
		$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML,
		"options" => array(
				"disable-blocks-breaking" => false,
				"disable-blocks-placing" => true,
				"disable-joining-levels" => true,
				"chat-fromat" => true
			)
		);
		$this->times->save();
		$this->values->save();
		$this->getServer->getScheduler->scheduleRepeatingTask(new minuteSchedule($this), 72.000);
	}
	
	public function OnDisable(){
		$this->times->save();
		$this->values->save();
	}
	
	public function getRankName($playername){
		$minutes = $this->times->get($playername[0]);
		if($minutes => $this->values->get('firstgroup'['minute']) and $minutes < $this->values->get('secondgroup'['minute'])){
			$rankname = $this->values->get('firstgroup'['name']);
		}elseif($minutes => $this->values->get('secondgroup'['minute']) and $minutes < $this->values->get('thirdgroup'['minute'])){
			$rankname = $this->values->get('secondgroup'['name']);
		}elseif($minutes => $this->values->get('thirdgroup'['minute']) and $minutes < $this->values->get('fourthgroup'['minute'])){
			$rankname = $this->values->get('thirdgroup'['name']);
		}elseif($minutes => $this->values->get('fourthgroup'['minute']) and $minutes < $this->values->get('fifthgroup'['minute'])){
			$rankname = $this->values->get('fourthgroup'['name']);
		}elseif($minutes => $this->values->get('fifthgroup'['minute']) and $minutes < $this->values->get('sixthgroup'['minute'])){
			$rankname = $this->values->get('fifthgroup'['name']);
		}elseif($minutes => $this->values->get('sixthgroup'['minute']) and $minutes < $this->values->get('seventhgroup'['minute'])){
			$rankname = $this->values->get('sixthgroup'['name']);
		}elseif($minutes => $this->values->get('seventhgroup'['minute'])){
			$rankname = $this->values->get('seventhgroup'['name']);
		}
		return $rankname;
	}
	
	public function getRank($playername){
		$minutes = $this->times->get($playername[0]);
		if($minutes => $this->values->get('firstgroup'['minute']) and $minutes < $this->values->get('secondgroup'['minute'])){
			$rank = 'firstgroup';
		}elseif($minutes => $this->values->get('secondgroup'['minute']) and $minutes < $this->values->get('thirdgroup'['minute'])){
			$rank = 'secondgroup';
		}elseif($minutes => $this->values->get('thirdgroup'['minute']) and $minutes < $this->values->get('fourthgroup'['minute'])){
			$rank = 'thirdgroup';
		}elseif($minutes => $this->values->get('fourthgroup'['minute']) and $minutes < $this->values->get('fifthgroup'['minute'])){
			$rank = 'fourthgroup';
		}elseif($minutes => $this->values->get('fifthgroup'['minute']) and $minutes < $this->values->get('sixthgroup'['minute'])){
			$rank = 'fifthgroup';
		}elseif($minutes => $this->values->get('sixthgroup'['minute']) and $minutes < $this->values->get('seventhgroup'['minute'])){
			$rank = 'sixthgroup';
		}elseif($minutes => $this->values->get('seventhgroup'['minute'])){
			$rank = 'seventhgroup';
		}
		return $rank;
	}
	
	public function getBlockRank($blockID){
		if(in_array($blockID, $this-values->get('firstgroup'['blocks']))){
			$rank = $this->values->get('firstgroup'['name']);
		}elseif(in_array($blockID, $this-values->get('secondgroup'['blocks']))){
			$rank = $this->values->get('secondgroup'['name']);
		}elseif(in_array($blockID, $this-values->get('thirdgroup'['blocks']))){
			$rank = $this->values->get('thirdgroup'['name']);
		}elseif(in_array($blockID, $this-values->get('fourthgroup'['blocks']))){
			$rank = $this->values->get('fourthgroup'['name']);
		}elseif(in_array($blockID, $this-values->get('fifthgroup'['blocks']))){
			$rank = $this->values->get('fifthgroup'['name']);
		}elseif(in_array($blockID, $this-values->get('sixthgroup'['blocks']))){
			$rank = $this->values->get('sixthgroup'['name']);
		}elseif(in_array($blockID, $this-values->get('seventhgroup'['blocks']))){
			$rank = $this->values->get('seventhgroup'['name']);
		}else{
			$rank = "Undefinied block rank";
		}
		return $rank;
	}
	
	public function getLevelRank($level){
		if(in_array($level, $this-values->get('firstgroup'['levels']))){
			$rank = $this->values->get('firstgroup'['name']);
		}elseif(in_array($level, $this-values->get('secondgroup'['levels']))){
			$rank = $this->values->get('secondgroup'['name']);
		}elseif(in_array($level, $this-values->get('thirdgroup'['levels']))){
			$rank = $this->values->get('thirdgroup'['name']);
		}elseif(in_array($level, $this-values->get('fourthgroup'['levels']))){
			$rank = $this->values->get('fourthgroup'['name']);
		}elseif(in_array($level, $this-values->get('fifthgroup'['levels']))){
			$rank = $this->values->get('fifthgroup'['name']);
		}elseif(in_array($level, $this-values->get('sixthgroup'['levels']))){
			$rank = $this->values->get('sixthgroup'['name']);
		}elseif(in_array($level, $this-values->get('seventhgroup'['levels']))){
			$rank = $this->values->get('seventhgroup'['name']);
		}else{
			$rank = "Undefinied level rank";
		}
		return $rank;
	}
	
	public function setRank($player, $rank){
		if($rank == "firstrank" or $rank == $this->values->get('firstrank'['name'])){
			$minute = $this->values->get('firstrank'['minutes']);
			$this->times->set($player, array($minute));
		}elseif($rank == "secondrank" or $rank == $this->values->get('secondrank'['name'])){
			$minute = $this->values->get('secondrank'['minutes']);
			$this->times->set($player, array($minute));
		}elseif($rank == "thirdrank" or $rank == $this->values->get('thirdrank'['name'])){
			$minute = $this->values->get('thirdrank'['minutes']);
			$this->times->set($player, array($minute));
		}elseif($rank == "fourthrank" or $rank == $this->values->get('fourthrank'['name'])){
			$minute = $this->values->get('fourthrank'['minutes']);
			$this->times->set($player, array($minute));
		}elseif($rank == "fifthrank" or $rank == $this->values->get('fifthrank'['name'])){
			$minute = $this->values->get('fifthrank'['minutes']);
			$this->times->set($player, array($minute));
		}elseif($rank == "sixthrank" or $rank == $this->values->get('sixthrank'['name'])){
			$minute = $this->values->get('sixthrank'['minutes']);
			$this->times->set($player, array($minute));
		}elseif($rank == "seventhrank" or $rank == $this->values->get('seventhrank'['name'])){
			$minute = $this->values->get('seventhrank'['minutes']);
			$this->times->set($player, array($minute));
		}else{
			return "This rank doesn't exist.";
		}
		return true;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() == "timeranks"){
			$params = array_shift($args[0]);
			switch($params){
			case "get":
				if(!(isset($args[1]))){
					$group = $this->getRankName($sender->getName());
					$sender->sendMessage("You currently have the rank: ".$group);
					return true;
				}else{
					$user = $args[1];
					$group = $this->getRankName($user);
					$sender->sendMessage($user." has currently the rank: ".$group);
					return true;
				}
			break;
			case "set":
				if($sender->isOP()){
					if(isset($args[1]) and isset($args[2])){
						$this->setRank($args[1], $args[2]);
						$sender->sendMessage("Ranks Updated!");
						return true;
					}else{
						return false;
					}
				}else{
					$sender->sendMessage("You have not the permission to run this command");
					return true;
				}
			break;
			}
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$playername = $event->getPlayer()->getName();
		$playerrank = $this->getRank($player);
		$ID = $event->getBlock()->getID();
		if($this->config->get('options'['disable-blocks-placing']) == true){
			if(in_array($ID, $this-values->get($playerrank['blocks']))){
				$event->setCancelled(false);
			}else{
				$event->setCancelled();
				$player->sendMessage("Your rank is too low to use this block.");
				$player->sendMessage("You need rank: ".$this->getBlockRank($ID));
			}
		}
	}
	
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$playername = $event->getPlayer()->getName();
		$playerrank = $this->getRank($player);
		$ID = $event->getBlock()->getID();
		if($this->config->get('options'['disable-blocks-breaking']) == true){
			if(in_array($ID, $this-values->get($playerrank['blocks']))){
				$event->setCancelled(false);
			}else{
				$event->setCancelled();
				$player->sendMessage("Your rank is too low to use this block.");
				$player->sendMessage("You need rank: ".$this->getBlockRank($ID));
			}
		}
	}
	
	public function onLevelChange(EntityLevelChangeEvent $event){
		if($this->config->get('options'['disable-joining-levels']) == true){
			$player = $event->getPlayer();
			$playername = $event->getEntity->getName();
			$playerrank = $this->getRank($player);
			$target = $event->getTartget();
			if(in_array($target, $this->values->get($playerrank['levels']))){
				$event->setCancelled(false);
			}else{
				$event->setCancelled();
				$player->sendMessage("Your rank is too low to join that world");
				$player->sendMessage("You need rank: ".$this->getLevelRank($target));
			}
		}
	}
	
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$playername = $event->getPlayer()->getName();
		$playerrank = $this->getRank($playername);
		$playerrankname = $this->getRankName($playername);
		if($this->config->get('options'['chat-format']) == true){
			$event->setFormat("[".$playerrankname."]<".$playername.">: ".$event->getMessage);
		}
		if($this->config->get('options'['disable-chat']) == true){
			if($this->values->get($playerrank['chat']) == false){
				$event->setCancelled();
				$player->sendMessage("You rank is too low to chat.");
			}
		}
	}
}
 
class minuteSchedule extends PluginTask{
	public function __construct(Main $plugin){
		$this->api = $server;
    }
	public function onRun($currentTick){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if(!($this->times->exists($p))){
				$this->times->set($p, array(1));
			}else{
				$currentminute = $this->times->get($p[0]) + 1;
				$this->times->set($p, array($currentminute));
			}
		}
	}
}
