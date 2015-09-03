<?php

namespace TimeRanks;

use pocketmine\scheduler\PluginTask;

class Timer extends PluginTask{

    public function __construct(Main $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($tick){

    }

}