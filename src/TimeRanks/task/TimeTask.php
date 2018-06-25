<?php

namespace TimeRanks\task;

use pocketmine\scheduler\Task;
use TimeRanks\TimeRanks;

class TimeTask extends Task {

	/** @var TimeRanks */
	private $tr;

	public function __construct(TimeRanks $tr){
		$this->tr = $tr;
	}

	public function onRun(int $tick){
		foreach($this->tr->getServer()->getOnlinePlayers() as $p){
			$this->tr->getProvider()->setMinutes($p->getName(), $after = ($before = $this->tr->getProvider()->getMinutes($p->getName())) + 1);
			$this->tr->checkRankUp($p, $before, $after);
		}
	}

}