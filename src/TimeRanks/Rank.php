<?php

namespace TimeRanks;

use pocketmine\command\ConsoleCommandSender;

class Rank{

    private $timeRanks, $name, $minutes, $PPGroup, $default, $commands = [], $message, $blocks = [];

    /**
     * @param TimeRanks $timeRanks
     * @param $name
     * @param array $data
     * @throws \Exception
     */
    public function __construct(TimeRanks $timeRanks, $name, array $data){
        try{
            $this->timeRanks = $timeRanks;
            $this->name = $name;
            $this->minutes = (int) $data["minutes"];
            $pureGroup = $this->timeRanks->purePerms->getGroup($data["pureperms_group"]);
            if($pureGroup !== null){
                $this->PPGroup = $pureGroup;
            }else{
                throw new \Exception("Rank has not been initialized. PurePerms group ".$data["pureperms_group"]." cannot be found");
            }
            $this->default = isset($data["default"]) ? $data["default"] : false;

            isset($data["message"]) and $this->message = $data["message"];
            isset($data["commands"]) and $this->commands = $data["commands"];
            isset($data["blocks"]) and $this->blocks = $data["blocks"];
        }catch(\Exception $e){
            $this->timeRanks->getLogger()->alert("Exception while loading rank: ".isset($data["name"]) ? $data["name"] : "unknown rank");
            $this->timeRanks->getLogger()->alert("Error: ".$e->getMessage());
            if(isset($data["name"]) and isset($this->timeRanks->ranks[$data["name"]])){
                unset($this->timeRanks->ranks[strtolower($data["name"])]);
            }
        }
    }

    public function getName(){
        return $this->name;
    }

    public function isDefault(){
        return $this->default;
    }

    public function getMinutes(){
        return $this->minutes;
    }

    public function onRankUp($playerName){
        $player = $this->timeRanks->getServer()->getPlayer($playerName);
        if($player !== null and $player->isOnline()){
            isset($this->message) ? $player->sendMessage($this->message) : $player->sendMessage("Congratulations, you are now rank ".$this->getName());
        }else{
            $player = $this->timeRanks->getServer()->getOfflinePlayer($playerName);
        }
        foreach($this->commands as $cmd){
            $this->timeRanks->getServer()->dispatchCommand(new ConsoleCommandSender(), str_ireplace("{player}", $player->getName(), $cmd));
        }
        $this->timeRanks->purePerms->getUser($player)->setGroup($this->PPGroup, null);
    }

}