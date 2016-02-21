<?php

namespace TimeRanks;

use pocketmine\scheduler\PluginTask;

class TimeTask extends PluginTask{

    private $tr;

    public function __construct(TimeRanks $tr){
        parent::__construct($tr);
        $this->tr = $tr;
    }

    public function onRun($trick){
        foreach($this->tr->getServer()->getOnlinePlayers() as $p){
            $this->tr->getProvider()->setMinutes($p->getName(), $after = ($before = $this->tr->getProvider()->getMinutes($p->getName())) + 1);
            $this->tr->checkRankUp($p->getName(), $before, $after);
        }
    }

}