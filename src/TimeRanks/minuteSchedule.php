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
		foreach($this->getServer()->getOnlinePlayers() as $p){
	  	if(!($this->plugin->times->exists($p))){
				$this->plugin->times->set($p, array(1));
			}else{
				$currentminute = $this->plugin->times->get($p[0]) + 1;
				$this->plugin->times->set($p, array($currentminute));
			}
		}
	}
}
