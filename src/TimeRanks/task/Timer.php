<?php

namespace TimeRanks\task;

use pocketmine\scheduler\PluginTask;
use TimeRanks\TimeRanks;

class Timer extends PluginTask{

    private $timeRanks;

    public function __construct(TimeRanks $timeRanks){
        parent::__construct($timeRanks);
        $this->timeRanks = $timeRanks;
    }

    public function onRun($tick){
		$this->timeRanks->dataprovider->start_transaction();
        foreach($this->timeRanks->getServer()->getOnlinePlayers() as $p){
            if(!$p->hasPermission("timeranks.exempt")){
                $this->timeRanks->setMinutes($p->getName(), $this->timeRanks->getMinutes($p->getName()) + 1, true);
            } elseif($this->timeRanks->trackAllPlayersTimes) {
				$this->timeRanks->setMinutes($p->getName(), $this->timeRanks->getMinutes($p->getName()) + 1, false);
			}
        }
		$this->timeRanks->dataprovider->commit_transaction();
    }

}