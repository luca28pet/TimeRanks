<?php

namespace TimeRanks;
//todo
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

public $times;
public $values;
public $config;

	public function OnEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->times = new Config($this->getDataFolder()."times.yml", Config::YAML);
		$this->values = new Config($this->getDataFolder()."values.yml", Config::YAML,
			"firstgroup" => array(
				"name" => "Newly Spawned",
				"minute" => 0,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"secondgroup" => array(
				"name" => "Tree Puncher",
				"minute" => 30,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"thirdgroup" => array(
				"name" => "Coal User",
				"minute" => 60,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),//TODO: add default block list
			"fourthgroup" => array(
				"name" => "Iron Miner",
				"minute" => 180,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"fifthgroup" => array(
				"name" => "Gold Player",
				"minute" => 300,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"sixthgroup" => array(
				"name" => "Diamond User",
				"minute" => 600,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			),
			"seventhgroup" => array(
				"name" => "Server Pro",
				"minute" => 1440,
				"blocks" => array(),
				"levels" => array(
					world, survival
				),
				"chat" => true
			)
		);
		$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML,
		"options" => array(
				"disable-blocks-breaking" => false,
				"disable-blocks-placing" => false,
				"disable-joining-levels" => false,
				"chat-fromat" => false
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
		if($minutes >= $this->values->get('firstgroup'['minute']) and $minutes < $this->values->get('secondgroup'['minute'])){
			$rankname = $this->values->get('firstgroup'['name']);
		}elseif($minutes >= $this->values->get('secondgroup'['minute']) and $minutes < $this->values->get('thirdgroup'['minute'])){
			$rankname = $this->values->get('secondgroup'['name']);
		}elseif($minutes >= $this->values->get('thirdgroup'['minute']) and $minutes < $this->values->get('fourthgroup'['minute'])){
			$rankname = $this->values->get('thirdgroup'['name']);
		}elseif($minutes >= $this->values->get('fourthgroup'['minute']) and $minutes < $this->values->get('fifthgroup'['minute'])){
			$rankname = $this->values->get('fourthgroup'['name']);
		}elseif($minutes >= $this->values->get('fifthgroup'['minute']) and $minutes < $this->values->get('sixthgroup'['minute'])){
			$rankname = $this->values->get('fifthgroup'['name']);
		}elseif($minutes >= $this->values->get('sixthgroup'['minute']) and $minutes < $this->values->get('seventhgroup'['minute'])){
			$rankname = $this->values->get('sixthgroup'['name']);
		}elseif($minutes >= $this->values->get('seventhgroup'['minute'])){
			$rankname = $this->values->get('seventhgroup'['name']);
		}else{
			$rankname = "Undefinied rank name";
		}
		return $rankname;
	}
	
	public function getRank($playername){
		$minutes = $this->times->get($playername[0]);
		if($minutes >= $this->values->get('firstgroup'['minute']) and $minutes < $this->values->get('secondgroup'['minute'])){
			$rank = 'firstgroup';
		}elseif($minutes >= $this->values->get('secondgroup'['minute']) and $minutes < $this->values->get('thirdgroup'['minute'])){
			$rank = 'secondgroup';
		}elseif($minutes >= $this->values->get('thirdgroup'['minute']) and $minutes < $this->values->get('fourthgroup'['minute'])){
			$rank = 'thirdgroup';
		}elseif($minutes >= $this->values->get('fourthgroup'['minute']) and $minutes < $this->values->get('fifthgroup'['minute'])){
			$rank = 'fourthgroup';
		}elseif($minutes >= $this->values->get('fifthgroup'['minute']) and $minutes < $this->values->get('sixthgroup'['minute'])){
			$rank = 'fifthgroup';
		}elseif($minutes >= $this->values->get('sixthgroup'['minute']) and $minutes < $this->values->get('seventhgroup'['minute'])){
			$rank = 'sixthgroup';
		}elseif($minutes >= $this->values->get('seventhgroup'['minute'])){
			$rank = 'seventhgroup';
		}else{
			$rank = "Undefinied rank";
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
	
	public function getMinutesLeft($player){
			$rank = $this->getRank($player);
			if($rank == "firstrank"){
				$minutes = $this->values->get('secondrank'['minute']) - $this->times($player[0])
			}elseif($rank == "secondrank"){
				$minutes = $this->values->get('thirdrank'['minute']) - $this->times($player[0])
			}elseif($rank == "thirdrank"){
				$minutes = $this->values->get('foruthrank'['minute']) - $this->times($player[0])
			}elseif($rank == "fourthrank"){
				$minutes = $this->values->get('fifthrank'['minute']) - $this->times($player[0])
			}elseif($rank == "fifthrank"){
				$minutes = $this->values->get('sixthrank'['minute']) - $this->times($player[0])
			}elseif($rank == "sixthrank"){
				$minutes = $this->values->get('seventhrank'['minute']) - $this->times($player[0])
			}
		return $minutes;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() == "timeranks"){
			$params = array_shift($args[0]);
			switch($params){
			case "get":
				if(!(isset($args[1]))){
					$group = $this->getRankName($sender->getName());
					$sender->sendMessage("[TimeRanks] You currently have the rank: ".$group);
					if($this->getRank($sender->getName) != "seventhrank"){
						$sender->sendMessage("[TimeRanks] You have ".$this->getMinutesLeft($sender->getName)." minutes left untill you change the rank.");
					}else{
						$sender->sendMessage("[TimeRanks] You have the highest rank!");
					}
					return true;
				}else{
					$user = $args[1];
					$group = $this->getRankName($user);
					$sender->sendMessage("[TimeRanks] ".$user." has currently the rank: ".$group);
					if($this->getRank($user) != "seventhrank"){
						$sender->sendMessage("[TimeRanks] ".$user." has ".$this->getMinutesLeft($user)." minutes left untill he changes the rank.");
					}else{
						$sender->sendMessage("[TimeRanks] ".$user." has the highest rank!");
					}
					return true;
				}
			break;
			case "set":
				if($sender->isOP()){
					if(isset($args[1]) and isset($args[2])){
						$this->setRank($args[1], $args[2]);
						$sender->sendMessage("[TimeRanks] Ranks Updated!");
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
				$player->sendMessage("[TimeRanks] Your rank is too low to use this block.");
				$player->sendMessage("[TimeRanks] You need rank: ".$this->getBlockRank($ID));
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
				$player->sendMessage("[TimeRanks] Your rank is too low to use this block.");
				$player->sendMessage("[TimeRanks] You need rank: ".$this->getBlockRank($ID));
			}
		}
	}
	
	public function onLevelChange(EntityLevelChangeEvent $event){
		if($event->getEntity instanceof Player){
			if($this->config->get('options'['disable-joining-levels']) == true){
				$player = $event->getEntity();
				$playername = $event->getEntity->getName();
				$playerrank = $this->getRank($player);
				$target = $event->getTartget()->getName();
				if(in_array($target, $this->values->get($playerrank['levels']))){
					$event->setCancelled(false);
				}else{
					$event->setCancelled();
					$player->sendMessage("[TimeRanks] Your rank is too low to join that world");
					$player->sendMessage("[TimeRanks] You need rank: ".$this->getLevelRank($target));
				}
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
				$player->sendMessage("[TimeRanks] You rank is too low to chat.");
			}
		}
	}
}
