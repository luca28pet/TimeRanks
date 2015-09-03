<?php

namespace TimeRanks\event;

use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use TimeRanks\Main;
use TimeRanks\Rank;

class PlayerRankUpEvent extends PluginEvent implements Cancellable{

    public static $handlerList = null;

    private $playerName, $newRank;

    public function __construct(Main $plugin, $playerName, Rank $newRank){
        parent::__construct($plugin);
        $this->playerName = $playerName;
        $this->newRank = $newRank;
    }

    /**
     * @return string|Player the player object if online, otherwise the player name
     */
    public function getPlayer(){
        if(($player = $this->getPlugin()->getServer()->getPlayer($this->playerName)) !== null and $player->isOnline()){
            return $player;
        }
        return $this->playerName;
    }

    /**
     * @return Rank
     */
    public function getNewRank(){
        return $this->newRank;
    }

}