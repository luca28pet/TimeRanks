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
                if(isset($args[0])){
                    if(!$this->plugin->getServer()->getOfflinePlayer($args[0])->hasPlayedBefore()){
                        $sender->sendMessage("Player ".$args[0]." never played on this server");
                    }else{
                        if(!isset($this->plugin->data[strtolower($args[0])])){
                            $sender->sendMessage($args[0]." has played less than 1 minute on this server");
                            $sender->sendMessage("Rank is: ".$this->plugin->getRank($args[0]));
                        }else{
                            $sender->sendMessage($args[0]." has played ".$this->plugin->data[strtolower($sender->getName())]["minutes"]." minutes on this server");
                            $sender->sendMessage("Rank is: ".$this->plugin->getRank($args[0]));
                        }
                    }
                    return true;
                }
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