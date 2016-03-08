<?php

namespace TimeRanks;

use _64FF00\PurePerms\PurePerms;
use pocketmine\plugin\PluginBase;
use TimeRanks\provider\SQLite3Provider;
use TimeRanks\provider\TimeRanksProvider;

class TimeRanks extends PluginBase{

    /** @var  TimeRanksProvider */
    private $provider = null;
    /** @var  Rank[] */
    private $ranks = [];
    /** @var  PurePerms */
    private $purePerms = null;
    private $defaultRank = null;

    public function onEnable(){
        if(($pp = $this->getServer()->getPluginManager()->getPlugin("PurePerms")) === null){
            $this->getLogger()->alert("TimeRanks: Dependency PurePerms not found, disabling plugin");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->purePerms = $pp;
        $this->saveDefaultConfig();
        switch($this->getConfig()->get("data-provider", "sqlite3")){
            case "sqlite3":
                $this->provider = new SQLite3Provider($this);
                break;
            default:
                $this->getLogger()->alert("Invalid TimeRanks data provider set in config.yml, disabling plugin");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        $this->loadRanks();
    }

    private function loadRanks(){
        $this->saveResource("ranks.yml");
        $ranks = yaml_parse_file($this->getDataFolder()."ranks.yml");
        foreach($ranks as $name => $data){
            $rank = Rank::fromData($this, $name, $data);
            if($rank !== null){
                $this->ranks[$name] = $rank;
            }
        }
        $default = 0;
        foreach($this->ranks as $rank){
            if($rank->isDefault()){
                ++$default;
            }
        }
        if($default !== 1){
            $this->getLogger()->alert("No/Too many default rank(s) set in ranks.yml, disabling plugin");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function onDisable(){
        $this->provider->close();
        $this->provider = null;
        $this->ranks = [];
        $this->purePerms = null;
        $this->defaultRank = null;
    }

    public function getPurePerms() : PurePerms{
        return $this->purePerms;
    }

    public function getProvider() : TimeRanksProvider{
        return $this->provider;
    }

    /**
     * @return Rank[]
     */
    public function getRanks() : array{
        return $this->ranks;
    }

    public function getRank(string $name){
        if(isset($this->ranks[$name])){
            return $this->ranks[$name];
        }
        return null;
    }

    public function getDefaultRank() : Rank{
        if(!($this->defaultRank instanceof Rank)){
            foreach($this->ranks as $rank){
                if($rank->isDefault()){
                    $this->defaultRank = $rank;
                    break;
                }
            }
        }
        return $this->defaultRank;
    }

    public function checkRankUp(string $name, int $before, int $after) : bool{
        $old = $this->getRankOnMinute($before);
        $new = $this->getRankOnMinute($after);
        if($old !== $new){
            $new->onRankUp($name);
            return true;
        }
        return false;
    }

    public function getRankOnMinute(int $min) : Rank{
        $res = array_filter($this->ranks, function($rank) use ($min){ /** @var Rank $rank*/
            return $rank->getMinutes() <= $min;
        });
        uasort($res, function($a, $b){ /** @var Rank $a */ /** @var Rank $b */
            return $a->getMinutes() === $b->getMinutes() ? 0 : ($a->getMinutes() < $b->getMinutes()) ? 1 : -1;
        });
        reset($res); //TODO: test if this is needed
        return current($res);

        /*$res = [];
        foreach($this->ranks as $rank){
            if($rank->getMinutes() === $min){
                return $rank;
            }
            if($rank->getMinutes() < $min){
                $res[$rank->getMinutes()] = $rank;
            }
        }
        $k = array_keys($res);
        rsort($k);
        return $res[$k[0]];*/
    }

}