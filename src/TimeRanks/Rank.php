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

    private function __construct(TimeRanks $tr, string $name, bool $default, int $minutes, PPGroup $ppGroup, string $message, array $commands){
        $this->tr = $tr;
        $this->name = $name;
        $this->default = $default;
        $this->minutes = $minutes;
        $this->ppGroup = $ppGroup;
        $this->message = $message;
        $this->commands = $commands;
    }

    public function onRankUp(string $name, bool $removePending = false) : void{
        $player = $this->tr->getServer()->getPlayer($name);
        if($player === null or !$player->isOnline()){
            $this->pending[strtolower($name)] = true;
            return;
        }
        if($removePending){
            $this->removePending($name);
        }
        $this->tr->getPurePerms()->setGroup($player, $this->ppGroup);
        $player->sendMessage($this->message);
        foreach($this->commands as $command){
            $this->tr->getServer()->dispatchCommand(new ConsoleCommandSender(), $command);
        }
    }

    public function isDefault() : bool{
        return $this->default;
    }

    public function getName() : string{
        return $this->name;
    }

    public function getMinutes() : int{
        return $this->minutes;
    }

    public function getGroup() : PPGroup{
        return $this->ppGroup;
    }

    public function getMessage() : string{
        return $this->message;
    }

    public function getCommands() : array{
        return $this->commands;
    }

    public function isPending(string $name) : bool{
        return isset($this->pending[strtolower($name)]);
    }

    public function removePending(string $name){
        if($this->isPending($name)){
            unset($this->pending[strtolower($name)]);
        }
    }

    public static function fromData(TimeRanks $tr, string $name, array $data){
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