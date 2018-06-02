<?php

namespace TimeRanks\task;

use pocketmine\scheduler\PluginTask;
use TimeRanks\TimeRanks;

class TimeTask extends PluginTask{

	/** @var TimeRanks */
	private $tr;

	public function __construct(TimeRanks $tr){
		parent::__construct($tr);
		$this->tr = $tr;
	}

	public function onRun(int $tick){
		foreach($this->tr->getServer()->getOnlinePlayers() as $p){
			$this->tr->getProvider()->setMinutes($p->getName(), $after = ($before = $this->tr->getProvider()->getMinutes($p->getName())) + 1);
			$this->tr->checkRankUp($p, $before, $after);
		}
	}

}