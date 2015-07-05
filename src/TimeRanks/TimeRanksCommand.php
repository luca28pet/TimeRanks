<?php

namespace TimeRanks;

use pocketmine\command\CommandSender;
use pocketmine\Player;

class TimeRanksCommand{

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function run(CommandSender $sender, array $args){
        if(!isset($args[0])){
            $sender->sendMessage("TimeRanks plugin by luca28pet");
            $sender->sendMessage("Use /tr check ".($sender instanceof Player ? "[player]" : "<player>"));
            return true;
        }
        $sub = array_shift($args);
        switch(strtolower($sub)){
            case "check":
                if(!isset($this->plugin->data[strtolower($sender->getName())])){
                    if(!($sender instanceof Player)){
                        $sender->sendMessage("Please use /tr check <playername>");
                        return true;
                    }
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