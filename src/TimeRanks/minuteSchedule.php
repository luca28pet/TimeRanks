<?php

namespace TimeRanks;

use pocketmine\Server;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;

class minuteSchedule extends PluginTask{
  
	public function __construct($plugin){
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}

	public function onRun($currentTick){
		foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
			$pn = $p->getName();
	  		if(!($this->plugin->times->exists($pn))){
				$this->plugin->times->set($pn, array(1));
			}else{
				$currentminute = $this->plugin->times->get($pn[0]) + 1;
				$this->plugin->times->set($pn, array($currentminute));
			}
		}
	}
}
