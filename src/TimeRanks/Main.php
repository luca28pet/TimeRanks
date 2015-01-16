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
	
public $times;
public $values;
public $prefs;

	public function OnEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->times = new Config($this->getDataFolder()."times.yml", Config::YAML);
		$this->values = new Config($this->getDataFolder()."values.yml", Config::YAML, 
			array(
				"NewlySpawned" => array(
					"minute" => 0,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				),
				"TreePuncher" => array(
					"minute" => 30,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				),
				"CoalUser" => array(
					"minute" => 60,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				),
				"IronMiner" => array(
					"minute" => 180,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				),
				"GoldPlayer" => array(
					"minute" => 300,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				),
				"DiamondUser" => array(
					"minute" => 600,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				),
				"ServerPro" => array(
					"minute" => 1440,
					"blocks" => array(),
					"levels" => array(
						world, survival
					),
					"chat" => true
				)
			)
		);
		$this->prefs = new Config($this->getDataFolder()."preferences.yml", Config::YAML,
			array(
				"disable-blocks-breaking" => false,
				"disable-blocks-placing" => false,
				"disable-joining-levels" => false,
				"chat-fromat" => false
			)
		);
		$this->getServer->getScheduler()->scheduleRepeatingTask(new minuteSchedule($this), 1200);
	}
	
	public function OnDisable(){
		$this->prefs->save();
		$this->values->save();
		$this->times->save();
	}
	
	public function getRank($playername){
		
		$lowerranks = array();
		$minutes = $this->times->get($playername[0]);
		$ranks = $this->values->get('ranks');
		
		foreach($ranks as $r){
			$rankminute = $r['minute'];
			if($rankminute == $minutes){
				$rank = $r;
			}elseif($rankminute < $minutes){
				array_push($lowerranks, $r);
			}
		}
		
		rsort($lowerranks);
		$ranked = array_shift($lowerranks);
		
		foreach($ranks as $r){
			if($r['minute'] == $ranked){
				$rank = $r;
			}
		}
		
		unset($lowerranks);
		
		return $rank;
		
	}
	
	public function getBlockRank($blockID){
		
		$ranked = array();
		$rankminutes = array();
		$ranks = $this->values->get('ranks');
		
		foreach($ranks as $r){
			if(in_array($blockID, $r['blocks'])){
				array_push($ranked, $r);
			}
		}
		
		foreach($ranks as $r){
			if(in_array($r, $ranked)){
				array_push($rankminutes, $r['minute']);
			}
		}
		
		sort($rankminutes);
		$min = array_shift($rankminutes);
		
		foreach($ranks as $r){
			if($r['minute'] == $min){
				$blockrank = $r;
			}
		}
		
		return $blockrank;
		
	}
	
	public function getLevelRank($levelname){
		
		$ranked = array();
		$rankminutes = array();
		$ranks = $this->values->get('ranks');
		
		foreach($ranks as $r){
			if(in_array($levelname, $r['levels'])){
				array_push($ranked, $r);
			}
		}
		
		foreach($ranks as $r){
			if(in_array($r, $ranked)){
				array_push($rankminutes, $r['minute']);
			}
		}
		
		sort($rankminutes);
		$min = array_shift($rankminutes);
		
		foreach($ranks as $r){
			if($r['minute'] == $min){
				$levelrank = $r;
			}
		}
		
		return $levelrank;
		
	}
	
	public function setRank($playername, $rank){
		
		$ranks = $this->values->get($rank);
		
		if(isset($rank) and isset($this->times->get($playername))){
			$min = $ranks['minute'];
			$this->times->set($playername, array($min));
		}
		return true;
	}
	
	public function getMinutesLeft($playername){
		$rank = $this->getRank($playername);
		$higherranks = array();
		$ranks = $this->values->get('ranks');
		$min = "minute";
		foreach($ranks as $r){
			$rankminute = $r['minute'];
			if($rankminute > $this->values->getNested($ranks.$rank.$min)){ //$ranks[$rank]['minute']
				array_push($higherranks, $rankminute);
			}
		}
		sort($higherranks);
		$nextrankminute = array_shift($higherranks);
		$minutes = $nextrankminute - $this->values->getNested($ranks.$rank.$min);
		
		return $minutes;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() == "timeranks"){
			switch($args[0]){
			case "get":
				if(!(isset($args[1]))){
					$group = $this->getRank($sender->getName());
					$sender->sendMessage("[TimeRanks] You currently have the rank: ".$group);
					if(!$this->isLastRank($this->getRank($sender->getName))){ //TODO
						$sender->sendMessage("[TimeRanks] You have ".$this->getMinutesLeft($sender->getName)." minutes left untill you change the rank.");
					}else{
						$sender->sendMessage("[TimeRanks] You have the highest rank!");
					}
					return true;
				}else{
					$user = $args[1];
					$group = $this->getRank($user);
					$sender->sendMessage("[TimeRanks] ".$user." has currently the rank: ".$group);
					if(!$this->isLastRank($this->getRank($user))){ //TODO
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
		$playerrank = $this->getRank($playername);
		$ID = $event->getBlock()->getID();
		if($this->prefs->get("disable-blocks-placing") == true){
			if(!in_array($ID, $this-values->get($playerrank['blocks']))){
				$event->setCancelled();
				$player->sendMessage("[TimeRanks] Your rank is too low to use this block.");
				$player->sendMessage("[TimeRanks] You need rank: ".$this->getBlockRank($ID));
			}
		}
	}
	
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$playername = $event->getPlayer()->getName();
		$playerrank = $this->getRank($playername);
		$ID = $event->getBlock()->getID();
		if($this->prefs->get("disable-blocks-breaking") == true){
			if(!in_array($ID, $this-values->get($playerrank['blocks']))){
				$event->setCancelled();
				$player->sendMessage("[TimeRanks] Your rank is too low to use this block.");
				$player->sendMessage("[TimeRanks] You need rank: ".$this->getBlockRank($ID));
			}
		}
	}
	
	public function onLevelChange(EntityLevelChangeEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->prefs->get("disable-joining-levels") == true){
				$player = $event->getEntity();
				$playername = $event->getEntity->getName();
				$playerrank = $this->getRank($player);
				$target = $event->getTartget()->getName();
				if(in_array($target, $this->values->get($playerrank['levels']))){
					$event->setCancelled();
					$player->sendMessage("[TimeRanks] Your rank is too low to join that world");
					$player->sendMessage("[TimeRanks] You need rank: ".$this->getLevelRank($target));
					$deflevel = $this->getServer()->getDefaultLevel()->getSafeSpawn();
					$player->teleport($deflevel);
				}
			}
		}
	}
	
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$playername = $event->getPlayer()->getName();
		$playerrank = $this->getRank($playername);
		if($this->prefs->get("chat-format") == true){
			$event->setFormat("[".$playerrank."]<".$playername.">: ".$event->getMessage());
		}
		if($this->prefs->get("disable-chat") == true){
			if($this->values->get($playerrank['chat']) == false){
				$event->setCancelled();
				$player->sendMessage("[TimeRanks] You rank is too low to chat.");
			}
		}
	}
}
