<?php

namespace TimeRanks;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase{

    public $groups;
    /**@var \_64FF00\PurePerms\PurePerms*/
    public $purePerms;
    public $data = [];

    public function onEnable(){
        @mkdir($this->getDataFolder());
        # Groups config
        $c = new Config($this->getDataFolder()."groups.yml", Config::YAML, [
            "ExampleGroup" => [
                "minutes" => 20,
                "pureperms_group" => "Miner"
            ]
        ]);
        $c->save();
        $this->groups = $c->getAll();
        # Data config
        if(file_exists($this->getDataFolder()."data.json")){
            $this->data = json_decode(file_get_contents($this->getDataFolder()."data.json"), true);
        }
        # Load PurePerms
        $plugin = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        if($plugin instanceof Plugin){
            $this->purePerms = $plugin;
            $this->getLogger()->info("Successfully loaded with PurePerms");
        }else{
            $this->getLogger()->info("Dependency PurePerms not found");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        # Task
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Timer($this), 1200);
    }

    public function onDisable(){
        $c =  new Config($this->getDataFolder()."groups.yml", Config::YAML);
        $c->setAll($this->groups);
        $c->save();
        file_put_contents($this->getDataFolder()."data.json", json_encode($this->data));
    }

    public function checkRank(Player $player){
        foreach($this->groups as $group => $values){
            if($values["minutes"] == $this->data[strtolower($player->getName())]){
                $PPGroup = $this->purePerms->getGroup($values["pureperms_group"]);
                if($PPGroup === null){
                    $player->sendMessage("An error occurred during RankUp. Please contact an administrator");
                }else{
                    $player->sendMessage("You are now in group ".$group);
                    $this->purePerms->setGroup($player, $PPGroup);
                }
            }
        }
    }

}