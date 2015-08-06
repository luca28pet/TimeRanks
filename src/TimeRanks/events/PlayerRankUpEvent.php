<?php

namespace TimeRanks\events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerRankUpEvent extends PlayerEvent implements Cancellable{

    private $newRank;
    private $message;

    public function __construct(Player $player, $newRank, $message){
        $this->player = $player;
        $this->newRank = $newRank;
        $this->message = $message;
    }

    public function getNewRank(){
        return $this->newRank;
    }

    public function getMessage(){
        return $this->message;
    }

    public function setMessage($message){
        $this->message = $message;
    }

}