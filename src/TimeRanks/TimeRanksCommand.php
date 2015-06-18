<?php

namespace TimeRanks;

use pocketmine\command\CommandSender;

class TimeRanksCommand{

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function run(CommandSender $sender, array $args){
        $sub = array_shift($args);
        switch(strtolower($sub)){
            case "check":
                if(!isset($this->plugin->data[strtolower($sender->getName())])){
                    $sender->sendMessage("You have played less than 1 minute on this server");
                    $sender->sendMessage("Rank is: ".$this->plugin->getRank($sender));
                }else{
                    $sender->sendMessage("You have played ".$this->plugin->data[strtolower($sender->getName())]["minutes"]." minutes on this server");
                    $sender->sendMessage("Rank is: ".$this->plugin->getRank($sender));
                }
                return true;
            default:
                return false;
        }
    }

}