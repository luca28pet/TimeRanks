<?php

namespace TimeRanks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener{

    private $tr;

    public function __construct(TimeRanks $tr){
        $this->tr = $tr;
    }

    public function onJoin(PlayerJoinEvent $event){
        if(!$this->tr->getProvider()->isPlayerRegistered($name = $event->getPlayer()->getName())){
            $this->tr->getProvider()->registerPlayer($name);
            $this->tr->getDefaultRank()->onRankUp($name);
            return;
        }
        //Check pending rank-up
        foreach($this->tr->getRanks() as $rank){
            if($rank->isPending($name)){
                $rank->onRankUp($name, true);
                return;
            }
        }
    }

}