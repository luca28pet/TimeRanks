<?php

namespace TimeRanks;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use TimeRanks\event\PlayerRankUpEvent;

class Main extends PluginBase{

    /**@var  Rank[]*/
    public $ranks;
    /**@var \_64FF00\PurePerms\PurePerms*/
    public $purePerms;
    /**@var \SQLite3*/
    public $data;

    public function onEnable(){
        $this->saveDefaultConfig();

        @mkdir($dbpath = str_replace("{PLUGIN_DATA_FOLDER}", $this->getDataFolder(), $this->getConfig()->get("database-path")));

        $this->saveResource("ranks.yml");
        $ranks = yaml_parse_file($this->getDataFolder()."ranks.yml");
        $default = 0;
        foreach($ranks as $name => $data){
            if(isset($data["default"]) and $data["default"]){
                $default += 1;
            }
            $this->ranks[strtolower($name)] = new Rank($this, $name, $data);
        }

        if($default !== 1){
            $this->getLogger()->alert("Default rank not found. Please specify one in the config");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->data = new \SQLite3($dbpath."timeranks.db", file_exists($dbpath."timeranks.db") ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $this->data->exec("CREATE TABLE IF NOT EXISTS timeranks (name VARCHAR(16), minutes INTEGER)");

        //todo convert old data

        if(($plugin = $this->getServer()->getPluginManager()->getPlugin("PurePerms")) !== null){
            $this->purePerms = $plugin;
            $this->getLogger()->info("Successfully loaded with PurePerms");
        }else{
            $this->getLogger()->alert("Dependency PurePerms not found");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new Timer($this), 1200, 1200);
    }

    public function onDisable(){
        $this->data->close();
    }

    public function register($playerName){
        if($this->getMinutes($playerName) === null){
            $this->data->exec("INSERT INTO timeranks (name, minutes) VALUES ('".$this->data->escapeString(trim(strtolower($playerName)))."', 0)");
            $this->getLogger()->debug($playerName." has been registered in TimeRanks database");
        }
    }

    /**
     * @param string $playerName
     * @return int|null
     */
    public function getMinutes($playerName){
        $res = $this->data->query("SELECT minutes FROM timeranks WHERE name = '".$this->data->escapeString(trim(strtolower($playerName)))."'");
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);

            $this->getLogger()->debug("Called in getMinutes($playerName) query returned: ".var_export($array)." in result: ".var_export($res));

            return isset($array["minutes"]) ? $array["minutes"] : null;
        }
        return null;
    }

    /**
     * @param $playerName
     * @param $minutes
     */
    public function setMinutes($playerName, $minutes){
        $playerName = $this->data->escapeString(trim(strtolower($playerName)));
        $res = $this->data->query("SELECT minutes FROM timeranks WHERE name = '".$playerName."'");
        $before = 0;
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);

            $this->getLogger()->debug("Called in setMinutes($playerName) query returned: ".var_export($array)." in result: ".var_export($res));

            $before = isset($array["minutes"]) ? $array["minutes"] : 0;
        }
        $this->data->exec("UPDATE timeranks SET minutes = $minutes WHERE name = '".$playerName."'");
        $this->checkRankUp($playerName, $before, $minutes);
    }

    /**
     * @param $playerName
     * @param $before
     * @param $after
     * @return bool|Rank
     */
    public function checkRankUp($playerName, $before, $after){
        $old = $this->getRankFromMinutes($before);
        $new = $this->getRankFromMinutes($after);
        if($old !== $new){
            $this->getServer()->getPluginManager()->callEvent($ev = new PlayerRankUpEvent($this, $playerName, $new));
            if(!$ev->isCancelled()){
                $new->onRankUp($playerName);
                return $new;
            }
        }
        return false;
    }

    /**
     * @param $playerName
     * @return null|Rank
     */
    public function getRank($playerName){
        $res = $this->data->query("SELECT minutes FROM timeranks WHERE name = '".$this->data->escapeString(trim(strtolower($playerName)))."'");
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);

            $this->getLogger()->debug("Called in getRank($playerName) query returned: ".var_export($array)." in result: ".var_export($res));

            return isset($array["minutes"]) ? $this->getRankFromMinutes($array["minutes"]) : null;
        }
        return null;
    }

    /**
     * @param $minutes
     * @return Rank
     */
    public function getRankFromMinutes($minutes){
        if($minutes === 0){
            return $this->getDefaultRank();
        }
        $lowerRanks = [];
        foreach($this->ranks as $rank){
            if($minutes === $rank->getMinutes()){
                return $rank;
            }elseif($minutes > $rank->getMinutes()){
                $lowerRanks[serialize($rank)] = $rank->getMinutes();
            }
        }
        arsort($lowerRanks);
        return unserialize(array_keys($lowerRanks)[0]);
    }

    /**
     * @return null|Rank
     */
    public function getDefaultRank(){
        foreach($this->ranks as $rank){
            if($rank->isDefault()){
                return $rank;
            }
        }
        return null;
    }

}