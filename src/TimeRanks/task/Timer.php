<?php

namespace TimeRanks;

use pocketmine\scheduler\PluginTask;

class Timer extends PluginTask{

    private $timeRanks;

    public function __construct(TimeRanks $timeRanks){
        parent::__construct($timeRanks);
        $this->timeRanks = $timeRanks;
    }

    public function onRun($tick){
        foreach($this->timeRanks->getServer()->getOnlinePlayers() as $p){
            if(!$p->hasPermission("timeranks.exempt")){
                $this->timeRanks->setMinutes($p->getName(), $this->timeRanks->getMinutes($p->getName()) + 1);
            }
        }
    }

}