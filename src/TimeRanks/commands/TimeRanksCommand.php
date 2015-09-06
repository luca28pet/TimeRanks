<?php

namespace TimeRanks\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use TimeRanks\TimeRanks;

class TimeRanksCommand extends Command implements PluginIdentifiableCommand{

    private $timeRanks;

    public function __construct(TimeRanks $timeRanks){
        parent::__construct("timeranks", "TimeRanks", "/tr help", ["tranks", "tr"]);
        $this->timeRanks = $timeRanks;
        $this->setPermission("timeranks.command");
    }

    public function execute(CommandSender $sender, $label, array $args){
        if(!$this->testPermission($sender)){
            $sender->sendMessage("You don't have permission to use this command");
            return true;
        }
        if(!isset($args[0])){
            $sender->sendMessage("TimeRanks version ".$this->timeRanks->getDescription()->getVersion()." by luca28pet");
            return true;
        }
        $sub = array_shift($args);
        switch(strtolower($sub)){
            case "check":
                if(!isset($args[0])){
                    $minutes = $this->timeRanks->getMinutes($sender->getName());
                    $rank = $this->timeRanks->getRankFromMinutes($minutes);
                    if($rank !== null){
                        $sender->sendMessage("Your rank is: ".$rank->getName());
                        $sender->sendMessage("You have played for: ".TimeRanks::minutesToString($minutes));
                    }else{
                        $sender->sendMessage("Your rank was not found due to an internal error");
                    }
                }else{
                    $rank = $this->timeRanks->getRank($args[0]);
                    if($rank !== null){
                        $sender->sendMessage($args[0]."'s rank is: ".$rank->getName());
                    }else{
                        $sender->sendMessage($args[0]."'s rank was not found");
                    }
                }
                return true;
            case "list":
                $string = "";
                foreach($this->timeRanks->ranks as $rank){
                    $string .= $rank->getName().", ";
                }
                strlen($string) ? $sender->sendMessage("Available ranks: ".substr($string, 0, -2)) : $sender->sendMessage("No available ranks");
                return true;
            case "rinfo":
                if(!isset($args[0])){
                    $sender->sendMessage("Usage: /tr rinfo [rankName]");
                    return true;
                }
                $rank = $this->timeRanks->getRankByName($args[0]);
                if($rank === null){
                    $sender->sendMessage("Rank ".$args[0]." not found. Type /tr list for a list of ranks");
                    return true;
                }
                $sender->sendMessage("Rank name: ".$rank->getName());
                $sender->sendMessage("Rank time: ".TimeRanks::minutesToString($rank->getMinutes()));
                return true;
            case "set":
                if(!isset($args[0]) or !isset($args[1])){
                    $sender->sendMessage("Usage: /tr set [player] [minutes]");
                    return true;
                }
                if(!is_numeric($args[1])){
                    $sender->sendMessage("Please specify a valid number");
                    return true;
                }
                if($this->timeRanks->setMinutes($args[0], $args[1])){
                    $sender->sendMessage($args[0]." play time set to ".$args[1]);
                }else{
                    $sender->sendMessage($args[0]."'s data doesn't exist");
                }
                return true;
        }
        return true;
    }

    public function getPlugin(){
        return $this->timeRanks;
    }

}