<?php

namespace TimeRanks;

use _64FF00\PurePerms\PurePerms;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use TimeRanks\provider\JsonProvider;
use TimeRanks\provider\SQLite3Provider;
use TimeRanks\provider\TimeRanksProvider;
use TimeRanks\provider\YamlProvider;
use TimeRanks\task\TimeTask;

class TimeRanks extends PluginBase{

	/** @var  TimeRanksProvider */
	private $provider;
	/** @var  Rank[] */
	private $ranks = [];
	/** @var  PurePerms */
	private $purePerms;
	private $defaultRank;

	public function onEnable() : void{
		if(($pp = $this->getServer()->getPluginManager()->getPlugin('PurePerms')) === null){
			$this->getLogger()->alert('TimeRanks: Dependency PurePerms not found, disabling plugin');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->purePerms = $pp;
		$this->saveDefaultConfig();
		switch($this->getConfig()->get('data-provider', 'json')){
			case 'sqlite3':
				$this->provider = new SQLite3Provider($this);
				break;
			case 'json':
				$this->provider = new JsonProvider($this);
				break;
			case 'yaml':
				$this->provider = new YamlProvider($this);
				break;
			default:
				$this->getLogger()->alert('Invalid TimeRanks data provider set in config.yml, disabling plugin');
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return;
		}
		if(!$this->loadRanks()){
			return;
		}
		uasort($this->ranks, function($a, $b){ /** @var Rank $a */ /** @var Rank $b */
			return $b->getMinutes() <=> $a->getMinutes();
		});
		$this->getScheduler()->scheduleRepeatingTask(new TimeTask($this), 1200);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	private function loadRanks() : bool{
		$this->saveResource('ranks.yml');
		$ranks = yaml_parse_file($this->getDataFolder().'ranks.yml');
		foreach($ranks as $name => $data){
			$rank = Rank::fromData($this, $name, $data);
			if($rank !== null){
				$this->ranks[$name] = $rank;
			}
		}
		$default = 0;
		foreach($this->ranks as $rank){
			if($rank->isDefault()){
				++$default;
			}
		}
		if($default < 1){
			$this->getLogger()->alert('No default rank set in ranks.yml, disabling plugin');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return false;
		}
		if($default > 1){
			$this->getLogger()->alert('Too many default ranks set in ranks.yml, disabling plugin');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return false;
		}
		return true;
	}

	public function onDisable() : void{
		$this->provider->close();
		$this->provider = null;
		$this->ranks = [];
		$this->purePerms = null;
		$this->defaultRank = null;
	}

	public function getPurePerms() : PurePerms{
		return $this->purePerms;
	}

	public function getProvider() : TimeRanksProvider{
		return $this->provider;
	}

	/**
	 * @return Rank[]
	 */
	public function getRanks() : array{
		return $this->ranks;
	}

	public function getRank(string $name) : ?Rank{
		return $this->ranks[$name] ?? null;
	}

	public function getDefaultRank() : Rank{
		if($this->defaultRank === null){
			foreach($this->ranks as $rank){
				if($rank->isDefault()){
					$this->defaultRank = $rank;
					break;
				}
			}
		}
		return $this->defaultRank;
	}

	public function getPlayerRank(string $name) : Rank{
		return $this->getProvider()->isPlayerRegistered($name) ? $this->getRankOnMinute($this->getProvider()->getMinutes($name)) : $this->getDefaultRank();
	}

	public function checkRankUp(Player $player, int $before, int $after) : bool{
		$new = $this->getRankOnMinute($after);
		if($this->getRankOnMinute($before) !== $new){
			$new->onRankUp($player);
			return true;
		}
		return false;
	}

	public function getRankOnMinute(int $minute) : Rank{
		foreach($this->ranks as $rank){
			if($minute >= $rank->getMinutes()){
				return $rank;
			}
		}
		return $this->getDefaultRank();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0]) && strtolower($args[0]) === 'check'){
			if(isset($args[1])){
				if($sender->hasPermission('timeranks.command') || $sender->hasPermission('timeranks.command.self')){
					$minutes = $this->getProvider()->getMinutes($args[1]);
					$minutes !== -1 ? $sender->sendMessage(str_replace(['{name}', '{minutes}', '{line}', '{rank}'], [$args[1], $minutes, TextFormat::EOL, $this->getPlayerRank($args[1])->getName()], $this->getConfig()->get('message-player-minutes-played'))) : $sender->sendMessage(str_replace('{name}', $args[1], $this->getConfig()->get('message-player-never-played')));
				}
			}else{
				if($sender->hasPermission('timeranks.command') || $sender->hasPermission('timeranks.command.others')){
					$sender->sendMessage(str_replace(['{minutes}', '{line}', '{rank}'], [$this->getProvider()->getMinutes($sender->getName()), TextFormat::EOL, $this->getPlayerRank($sender->getName())->getName()], $this->getConfig()->get('message-minutes-played')));
				}
			}
		}else{
			$sender->sendMessage($this->getConfig()->get('message-usage'));
		}
		return true;
	}

}