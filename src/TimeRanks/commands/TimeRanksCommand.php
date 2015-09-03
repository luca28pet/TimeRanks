<?php

namespace TimeRanks;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;

class TimeRanksCommand extends Command implements PluginIdentifiableCommand{

    private $timeRanks;

    public function __construct(Main $timeRanks){
        parent::__construct("timeranks", "TimeRanks", "/tr help", ["tranks", "tr"]);
        $this->timeRanks = $timeRanks;
    }

    public function execute(CommandSender $sender, $label, array $args){
        //todo
    }

    public function getPlugin(){
        return $this->timeRanks;
    }

}