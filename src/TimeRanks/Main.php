<?php

namespace TimeRanks;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\PlayerChatEvent;
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
				"minute" => 0
				"blocks" => array(
					0,1,2,3,4,5,6,12,13,16,17,35,50,54,60,61,64
                        		
				)
			)
			"secondgroup" => array(
				"name" => "Tree Puncher",
				"minute" => 30
				"blocks" => array(
					0,1,2,3,4,5,6,12,13,16,17,35,50,60,61,64,245,43,44,53,54,67,134,135,136,139,157,158,163,164,173
                        		
				)
			)
			"thirdgroup" => array(
				"name" => "Coal User",
				"minute" => 60
				"blocks" => array(
					0,1,2,3,4,5,6,12,13,16,17,35,50,60,61,64,245,43,44,53,54,67,134,135,136,139,157,158,163,164,173
                        		
				)
			)//TODO: add default block list
			"fourthgroup" => array(
				"name" => "Iron Miner",
				"minute" => 180
			)
			"fifthgroup" => array(
				"name" => "Gold Player",
				"minute" => 300
			)
			"sixthgroup" => array(
				"name" => "Diamond User",
				"minute" => 600
			)
			"seventhgroup" => array(
				"name" => "Server Pro",
				"minute" => 1440
			)
		);
/*		$this->blocks = new Config($this->getDataFolder()."values.yml", Config::YAML,
			"firstgroup" => array(
                		"Content" => array(
                    			array(
                				11,
                        			0,
                        			7
                    			)))*/
		$this->times->save();
		$this->values->save();
		$this->getServer->getScheduler->scheduleRepeatingTask(new minuteSchedule($this), 72.000);
	}
	
	public function OnDisable(){
		$this->times->save();
		$this->values->save();
	}
	
	public function getRank($playername){
		$minutes = $this->times->get($playername[0]);
		if($minutes => $this->values->get('firstgroup'['minute']) and $minutes < $this->values->get('secondgroup'['minute'])){
			$rank = $this->values->get('firstgroup'['name']);
		}elseif($minutes => $this->values->get('secondgroup'['minute']) and $minutes < $this->values->get('thirdgroup'['minute'])){
			$rank = $this->values->get('secondgroup'['name']);
		}elseif($minutes => $this->values->get('thirdgroup'['minute']) and $minutes < $this->values->get('fourthgroup'['minute'])){
			$rank = $this->values->get('thirdgroup'['name']);
		}elseif($minutes => $this->values->get('fourthgroup'['minute']) and $minutes < $this->values->get('fifthgroup'['minute'])){
			$rank = $this->values->get('fourthgroup'['name']);
		}elseif($minutes => $this->values->get('fifthgroup'['minute']) and $minutes < $this->values->get('sixthgroup'['minute'])){
			$rank = $this->values->get('fifthgroup'['name']);
		}elseif($minutes => $this->values->get('sixthgroup'['minute']) and $minutes < $this->values->get('seventhgroup'['minute'])){
			$rank = $this->values->get('sixthgroup'['name']);
		}elseif($minutes => $this->values->get('seventhgroup'['minute'])){
			$rank = $this->values->get('seventhgroup'['name']);
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
		}
		return true;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() == "timeranks"){
			switch(array_shift($args[0]){
			case "get":
				if(!(isset $args[1])){
					$group = $this->getRank($sender->getName());
					$sender->sendMessage("You currently have the rank: ".$group);
					return true;
				}else{
					$user = $args[1];
					$group = $this->getRank($user);
					$sender->sendMessage($user." has currently the rank: ".$group);
					return true;
				}
			break;
			case "set": 
				$this->setRank($args[1], $args[2]);
				$sender->sendMessage("Ranks Updated!");
				return true;
			break
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
