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
            $this->plugin->data->exists($name = strtolower($player->getName())) ? $this->plugin->data->set($name, (int) $this->plugin->data->get($name) + 1) : $this->plugin->data->set($name, 1);
            $this->plugin->data->save();
            $this->plugin->checkRank($player);
        }
    }

}