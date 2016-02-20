<?php

namespace TimeRanks;

use _64FF00\PurePerms\PPGroup;
use pocketmine\command\ConsoleCommandSender;

class Rank{

    private $tr;
    private $name;
    private $default;
    private $minutes;
    private $ppGroup;
    private $message;
    private $commands;

    private $pending = [];

    private function __construct(TimeRanks $tr, $name, $default, $minutes, PPGroup $ppGroup, $message, array $commands){
        $this->tr = $tr;
        $this->name = $name;
        $this->default = $default;
        $this->minutes = $minutes;
        $this->ppGroup = $ppGroup;
        $this->message = $message;
        $this->commands = $commands;
    }

    public function onRankUp($name){
        $player = $this->tr->getServer()->getPlayer($name);
        if($player === null or !$player->isOnline()){
            $this->pending[] = $name;
            return;
        }
        $this->tr->getPurePerms()->setGroup($player, $this->ppGroup);
        $player->sendMessage($this->message);
        foreach($this->commands as $command){
            $this->tr->getServer()->dispatchCommand(new ConsoleCommandSender(), $command);
        }
    }

    public function isDefault(){
        return $this->default;
    }

    public function getName(){
        return $this->name;
    }

    public function getMinutes(){
        return $this->minutes;
    }

    public function getGroup(){
        return $this->ppGroup;
    }

    public function getMessage(){
        return $this->message;
    }

    public function getCommands(){
        return $this->commands;
    }

    public static function fromData(TimeRanks $tr, $name, $data){
        if(!isset($data["default"])){
            $data["default"] = false;
        }
        if((!isset($data["minutes"]) and !((bool) $data["default"])) or (isset($data["minutes"]) and !is_numeric($data["minutes"]))){
            $tr->getLogger()->alert("Rank $name failed loading, please set a valid minutes parameter");
            return null;
        }
        if(((bool) $data["default"])){
            $data["minutes"] = 0;
        }
        if(!isset($data["pureperms_group"]) or ($group = $tr->getPurePerms()->getGroup($data["pureperms_group"])) === null){
            $tr->getLogger()->alert("Rank $name failed loading, please set a valid pureperms group");
            return null;
        }
        if(!isset($data["message"])){
            $tr->getLogger()->alert("Rank $name failed loading, please set a valid message parameter");
            return null;
        }
        if(!isset($data["commands"]) or (isset($data["commands"]) and !is_array($data["commands"]))){
            $data["commands"] = [];
        }
        return new Rank($tr, $name, (bool) $data["default"], (int) $data["minutes"], $group, $data["message"], (isset($data["commands"]) and is_array($data["commands"])) ? $data["commands"] : []);
    }

}