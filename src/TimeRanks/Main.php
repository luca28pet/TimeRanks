<?php

namespace TimeRanks;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase{

    public $ranks;
    /**@var \_64FF00\PurePerms\PurePerms*/
    public $purePerms;
    /**@var Config::PROPERTIES*/
    public $data;
    /**@var TimeRanksCommand*/
    public $command;
    public $default;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        # Groups config
        $c = new Config($this->getDataFolder()."ranks.yml", Config::YAML, [
            "DefaultRank" => [
                "default" => true,
                "pureperms_group" => "Default"
            ],
            "ExampleRank" => [
                "minutes" => 20,
                "pureperms_group" => "Example"
            ]
        ]);
        $c->save();
        $this->ranks = $c->getAll();
        # Check for default rank
        $found = false;
        foreach($this->ranks as $rank => $values){
            if(isset($values["default"])){
                if($values["default"] == true){
                    $found = $rank;
                }
            }
        }
        if(!$found){
            $this->getLogger()->alert("Default rank not found. Please create a rank with the parameter - default: true");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }else{
            $this->default = $found;
        }
        # Properties data
        $this->data = new Config($this->getDataFolder()."data.properties", Config::PROPERTIES);
        # Convert old data.json
        if(file_exists($this->getDataFolder()."data.json")){
            $data = json_decode(file_get_contents($this->getDataFolder()."data.json"), true);
            foreach($data as $playerName => $datum){
                $this->data->set($playerName, $datum["minutes"]);
            }
            @unlink($this->getDataFolder()."data.json");
        }
        # Load PurePerms
        $plugin = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        if($plugin instanceof Plugin){
            $this->purePerms = $plugin;
            $this->getLogger()->info("Successfully loaded with PurePerms");
        }else{
            $this->getLogger()->alert("Dependency PurePerms not found");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        # Task
        $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new Timer($this), 1200, 1200);
        # Command
        $this->command = new TimeRanksCommand($this);
    }

    public function onDisable(){
        $c =  new Config($this->getDataFolder()."ranks.yml", Config::YAML);
        $c->setAll($this->ranks);
        $c->save();
        $this->data->save();
    }

    public function checkRank(Player $player){
        if(!$player->hasPermission("timeranks.exempt")){
            $name = strtolower($player->getName());
            foreach($this->ranks as $rank => $values){
                if(isset($values["default"])){
                    continue;
                }
                if($values["minutes"] == $this->data->get($name)){
                    $PPGroup = $this->purePerms->getGroup($values["pureperms_group"]);
                    if($PPGroup === null){
                        $player->sendMessage("An error occurred during RankUp. Please contact an administrator");
                    }else{
                        $player->sendMessage("You are now rank ".$rank);
                        $this->purePerms->setGroup($player, $PPGroup);
                    }
                }
            }
        }
    }

    public function getRank($player){
        $lowerRanks = [];
        foreach($this->ranks as $rank => $values){
            if(isset($values["default"])){
                continue;
            }
            if($values["minutes"] == $this->data->get($player)){
                return $rank;
            }elseif((int) $values["minutes"] < (int) $this->data->get($player)){
                $lowerRanks[$rank] = (int) $values["minutes"];
            }
        }
        arsort($lowerRanks);
        return array_keys($lowerRanks)[0];
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if(strtolower($command->getName()) === "timeranks"){
            $this->command->run($sender, $args);
        }
    }

}