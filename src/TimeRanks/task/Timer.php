<?php

namespace TimeRanks\task;

use pocketmine\scheduler\PluginTask;
use TimeRanks\TimeRanks;
use SimpleAuth\SimpleAuth;

class Timer extends PluginTask{
    /**
     * @var TimeRanks $timeRanks TimeRanks plugins
     */
    private $timeRanks;
    
    /**
     *
     * @var SimpleAuth
     */
    private $simpleAuth;

    public function __construct(TimeRanks $timeRanks){
        parent::__construct($timeRanks);
        $this->timeRanks = $timeRanks;
        $this->simpleAuth = $timeRanks->getServer()->getPluginManager()->getPlugin("SimpleAuth");
    }

    public function onRun($tick){
        $this->timeRanks->dataprovider->start_transaction();
        foreach($this->timeRanks->getServer()->getOnlinePlayers() as $p){
            if( ! $this->simpleAuth->isPlayerAuthenticated($p) ) {
                $msg = "Blocked " . $p->getName() . " from timerank minute up ";
                $msg .= "as they are not authenticated";
                $this->timeRanks->getServer()->getLogger()->debug($msg);
                continue;
            } elseif(!$p->hasPermission("timeranks.exempt")){
                $this->timeRanks->setMinutes($p->getName(), $this->timeRanks->getMinutes($p->getName()) + 1, true);
            } elseif($this->timeRanks->trackAllPlayersTimes) {
                $this->timeRanks->setMinutes($p->getName(), $this->timeRanks->getMinutes($p->getName()) + 1, false);
            }
        }
	$this->timeRanks->dataprovider->commit_transaction();
    }

}