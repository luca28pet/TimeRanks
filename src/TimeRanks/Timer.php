<?php

namespace TimeRanks;

use pocketmine\scheduler\PluginTask;

class Timer extends PluginTask{

    public function __construct(Main $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($tick){
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            $name = strtolower($player->getName());
            if(!isset($this->plugin->data[$name]["minutes"])){
                $this->plugin->data[$name]["minutes"] = 1;
            }else{
                $this->plugin->data[$name]["minutes"] += 1;
            }
            $this->plugin->checkRank($player);
        }
    }

}