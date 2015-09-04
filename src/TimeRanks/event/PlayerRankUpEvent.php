<?php

namespace TimeRanks\event;

use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use TimeRanks\TimeRanks;
use TimeRanks\Rank;

class PlayerRankUpEvent extends PluginEvent implements Cancellable{

    public static $handlerList = null;

    private $playerName, $oldRank, $newRank;

    public function __construct(TimeRanks $plugin, $playerName, Rank $oldRank, Rank $newRank){
        parent::__construct($plugin);
        $this->playerName = $playerName;
        $this->oldRank = $oldRank;
        $this->newRank = $newRank;
    }

    /**
     * @return \pocketmine\OfflinePlayer|Player
     */
    public function getPlayer(){
        if(($player = $this->getPlugin()->getServer()->getPlayer($this->playerName)) !== null and $player->isOnline()){
            return $player;
        }
        return $this->getPlugin()->getServer()->getOfflinePlayer($this->playerName);
    }

    /**
     * @return Rank the previous rank
     */
    public function getOldRank(){
        return $this->oldRank;
    }

    /**
     * @return Rank the new rank that the player will have
     */
    public function getNewRank(){
        return $this->newRank;
    }

    /**
     * @param Rank $newRank
     */
    public function setNewRank(Rank $newRank){
        if($newRank instanceof Rank){
            $this->newRank = $newRank;
        }
    }

}