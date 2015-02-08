<?php
namespace TimeRanks;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

/**@var Config*/
public $times;
/**@var Config*/
public $values;
/**@var Config*/
public $prefs;

public $timerankseconomy;
private $economys;
private $pocketmoney;

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
						"world", "survival"
					),
					"chat" => true,
					"cost" => 100
				),
				"TreePuncher" => array(
					"minute" => 30,
					"blocks" => array(),
					"levels" => array(
						"world", "survival"
					),
					"chat" => true,
					"cost" => 200
				),
				"CoalUser" => array(
					"minute" => 60,
					"blocks" => array(),
					"levels" => array(
						"world", "survival"
					),
					"chat" => true,
					"cost" => 300
				),
				"IronMiner" => array(
					"minute" => 180,
					"blocks" => array(),
					"levels" => array(
						"world", "survival"
					),
					"chat" => true,
					"cost" => 400
				),
				"GoldPlayer" => array(
					"minute" => 300,
					"blocks" => array(),
					"levels" => array(
						"world", "survival"
					),
					"chat" => true,
					"cost" => 500
				),
				"DiamondUser" => array(
					"minute" => 600,
					"blocks" => array(),
					"levels" => array(
						"world", "survival"
					),
					"chat" => true,
					"cost" => 600
				),
				"ServerPro" => array(
					"minute" => 1440,
					"blocks" => array(),
					"levels" => array(
						"world", "survival"
					),
					"chat" => true,
					"cost" => 700
				)
			)
		);
		$this->prefs = new Config($this->getDataFolder()."preferences.yml", Config::YAML,
			array(
				"disable-blocks-breaking" => false,
				"disable-blocks-placing" => false,
				"disable-joining-levels" => false,
				"chat-fromat" => false,
				"enable-economy" => false,
				"preferred-economy" => "economys"
			)
		);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new minuteSchedule($this), 1200);
		
		if($this->prefs->get("enable-economy") == true){
			if($this->prefs->get("preferred-economy") == "economys"){
				if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") instanceof Plugin){
            				$this->economys = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            				$this->getLogger()->info("TimeRanks loaded with EconomyS by onebone");
            				$this->timerankseconomy = "EconomyS";
				}else{
					$this->economyError();
					$this->timerankseconomy = false;
				}
			}elseif($this->prefs->get("preferred-economy") == "pocketmoney"){
				if($this->getServer()->getPluginManager()->getPlugin("PocketMoney") instanceof Plugin){
					$this->pocketmoney = $this->getServer()->getPluginManager()->getPlugin("PocketMoney");
					$this->getLogger()->info("TimeRanks loaded with PocketMoney by MinecrafterJPN");
					$this->timerankseconomy = "PocketMoney";
				}else{
					$this->economyError();
					$this->timerankseconomy = false;
				}
			}else{
				$this->economyError();
				$this->timerankseconomy = false;
			}
		}else{
			$this->timerankseconomy = false;
		}
	}
	
	public function OnDisable(){
		$this->prefs->save();
		$this->values->save();
		$this->times->save();
	}

	private function economyError(){
		$this->getLogger()->info(TextFormat::RED."You need to specify a valid economy plugin in preferences.yml or put to false enable-economy");
		$this->getLogger()->info(TextFormat::RED."TimeRanks DID NOT load any economy plugin.");
		$this->getLogger()->info(TextFormat::RED."Valid economy plugins: economys, pocketmoney.");
	}

	/**
	 * @param $playername
	 * @return mixed
	 */

	public function getRank($playername){
		
		$lowerranks = array();
		$minutes = $this->times->get($playername)[0];
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

	/**
	 * @param $blockID
	 * @return mixed
	 */

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

	/**
	 * @param $levelname
	 * @return mixed
	 */

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

	/**
	 * @param $playername
	 * @param $rank
	 * @return bool
	 */

	public function setRank($playername, $rank){
		
		$ranks = $this->values->get($rank);
		
		if(isset($rank) and $this->times->exists($playername)){
			$min = $ranks['minute'];
			$this->times->set($playername, array($min));
		}
		return true;
	}

	/**
	 * @param $playername
	 * @return mixed
	 */

	public function getMinutesLeft($playername){

		$higherranks = array();
		$ranks = $this->values->get('ranks');
		$min = $this->times->get($playername)[0];
		
		foreach($ranks as $r){
			$rankminute = $r['minute'];
			if($rankminute > $min){
				array_push($higherranks, $rankminute);
			}
		}
		
		sort($higherranks);
		$nextrankminute = array_shift($higherranks);
		$minutes = $nextrankminute - $min;
		
		return $minutes;
	}

	/**
	 * @param $playername
	 * @return mixed
	 */

	public function getNextRank($playername){

		$higherranks = array();
		$ranks = $this->values->get('ranks');
		$min = $this->times->get($playername)[0];
		
		foreach($ranks as $r){
			$rankminute = $r['minute'];
			if($rankminute > $min){
				array_push($higherranks, $rankminute);
			}
		}
		
		sort($higherranks);
		$nextrankminute = array_shift($higherranks);
		
		foreach($ranks as $r){
			if($r['minute'] == $nextrankminute){
				$nextrank = $r;
			}
		}
		
		return $nextrank;
	}

	/**
	 * @param $rank
	 * @return bool
	 */

	public function isLastRank($rank){
		$higherranks = array();
		$ranks = $this->values->get('ranks');
		$min = $this->values->get($rank['minute']);
		
		foreach($ranks as $r){
			$rankminute = $r['minute'];
			if($rankminute > $min){
				array_push($higherranks, $rankminute);
			}
		}
		
		if(count($higherranks) === 0){
			return true;
		}else{
			return false;
		}

	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() == "timeranks"){
			switch($args[0]){
			case "get":
				if(!(isset($args[1]))){
					$group = $this->getRank($sender->getName());
					$sender->sendMessage("[TimeRanks] You currently have the rank: ".$group);
					if(!$this->isLastRank($this->getRank($sender->getName()))){
						$sender->sendMessage("[TimeRanks] You have ".$this->getMinutesLeft($sender->getName())." minutes left untill you change the rank.");
					}else{
						$sender->sendMessage("[TimeRanks] You have the highest rank!");
					}
					return true;
				}else{
					$user = $args[1];
					$group = $this->getRank($user);
					$sender->sendMessage("[TimeRanks] ".$user." has currently the rank: ".$group);
					if(!$this->isLastRank($this->getRank($user))){
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
			case "buy":
				if($this->timerankseconomy !== false){
					if($this->timerankseconomy == "EconomyS"){
						$rank = $this->getRank($sender->getName());
						$nextrank = $this->getNextRank($sender->getName());
						$cost = $this->values->get($nextrank['cost']);
						$money = $this->economys->myMoney($sender);
						if($cost > $money){
							$sender->sendMessage("[TimeRanks] You don't have enough money");
						}else{
							$this->economys->reduceMoney($sender, $cost);
							$this->setRank($sender->getName(), $nextrank);
							$sender->sendMessage("[TimeRanks] You have bought rank: ".$nextrank);
						}
					}elseif($this->timerankseconomy == "PocketMoney"){
						$rank = $this->getRank($sender->getName());
						$nextrank = $this->getNextRank($sender->getName());
						$cost = $this->values->get($nextrank['cost']);
						$money = $this->pocketmoney->getMoney($sender->getName());
						if($cost > $money){
							$sender->sendMessage("[TimeRanks] You don't have enough money");
						}else{
							$m = $money - $cost;
							$this->pocketmoney->setMoney($sender->getName(), $m);
							$this->setRank($sender->getName(), $nextrank);
							$sender->sendMessage("[TimeRanks] You have bought rank: ".$nextrank);
						}						
					}else{
						$sender->sendMessage("TimeRanks did not loaded with any economy plugin.");
					}
				}else{
					$sender->sendMessage("TimeRanks did not loaded with any economy plugin.");
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
			if(!in_array($ID, $this->values->get($playerrank['blocks']))){
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
			if(!in_array($ID, $this->values->get($playerrank['blocks']))){
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
				$playerrank = $this->getRank($player);
				$target = $event->getTarget()->getName();
				if(!in_array($target, $this->values->get($playerrank['levels']))){
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
