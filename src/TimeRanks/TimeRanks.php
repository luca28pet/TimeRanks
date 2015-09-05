<?php

namespace TimeRanks;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use TimeRanks\event\PlayerRankUpEvent;

class TimeRanks extends PluginBase{

    /**@var null|TimeRanks*/
    public static $api = null;

    /**@var Rank[]*/
    public $ranks;
    /**@var \_64FF00\PurePerms\PurePerms*/
    public $purePerms;
    /**@var \SQLite3*/
    public $data;

    //API\\

    /**
     * @return null|TimeRanks
     */
    public static function getAPI(){
        return self::$api;
    }

    /**
     * @param string $playerName
     * @param int $minutes
     */
    public function register($playerName, $minutes = 0){
        if($this->getMinutes($playerName) === null){
            $this->data->exec("INSERT INTO timeranks (name, minutes) VALUES ('".$this->data->escapeString(trim(strtolower($playerName)))."', $minutes)");
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

            return isset($array["minutes"]) ? (int) $array["minutes"] : null;
        }
        return null;
    }

    /**
     * @param string $playerName
     * @param int|string $minutes must be numeric
     * @return bool
     */
    public function setMinutes($playerName, $minutes){
        $playerName = $this->data->escapeString(trim(strtolower($playerName)));
        $res = $this->data->query("SELECT minutes FROM timeranks WHERE name = '".$playerName."'");
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);

            $this->getLogger()->debug("Called in setMinutes($playerName) query returned: ".var_export($array)." in result: ".var_export($res));

            $before = isset($array["minutes"]) ? $array["minutes"] : 0;
            $minutes = (int) $minutes;
            $this->data->exec("UPDATE timeranks SET minutes = $minutes WHERE name = '".$playerName."'");
            $this->checkRankUp($playerName, $before, $minutes);
            return true;
        }
        return false;
    }

    /**
     * @param string $playerName
     * @param int|string $before must be numeric
     * @param int|string $after must be numeric
     */
    public function checkRankUp($playerName, $before, $after){
        $old = $this->getRankFromMinutes($before);
        $new = $this->getRankFromMinutes($after);
        if($old !== $new){
            $this->getServer()->getPluginManager()->callEvent($ev = new PlayerRankUpEvent($this, $playerName, $old, $new));
            if(!$ev->isCancelled()){
                $ev->getNewRank()->onRankUp($playerName);
            }
        }
    }

    /**
     * @param string $playerName
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
     * @param int|string $minutes must be numeric
     * @return Rank
     */
    public function getRankFromMinutes($minutes){
        if($minutes == 0){
            return $this->getDefaultRank();
        }
        $lowerRanks = [];
        foreach($this->ranks as $rank){
            if($minutes == $rank->getMinutes()){
                return $rank;
            }elseif($minutes > $rank->getMinutes()){
                $lowerRanks[serialize($rank)] = $rank->getMinutes();
            }
        }
        arsort($lowerRanks);
        return unserialize(key($lowerRanks));
    }

    /**
     * @return Rank
     */
    public function getDefaultRank(){
        foreach($this->ranks as $rank){
            if($rank->isDefault()){
                return $rank;
            }
        }
        return null;
    }

    /**
     * @param $name
     * @return null|Rank
     */
    public function getRankByName($name){
        $name = strtolower($name);
        foreach($this->ranks as $rName => $rank){
            if($rName === $name){
                return $rank;
            }
        }
        return null;
    }

    //NON-API\\

    public function onLoad(){
        if(!(self::$api instanceof TimeRanks)){
            self::$api = $this;
        }
    }

    public function onEnable(){
        $this->saveDefaultConfig();

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

        @mkdir($dbpath = str_replace("{PLUGIN_DATA_FOLDER}", $this->getDataFolder(), $this->getConfig()->get("database-path")));
        $this->data = new \SQLite3($dbpath."timeranks.db", file_exists($dbpath."timeranks.db") ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $this->data->exec("CREATE TABLE IF NOT EXISTS timeranks (name VARCHAR(16), minutes INTEGER)");

        if(file_exists($this->getDataFolder()."data.properties")){
            $data = array_map("intval", (new Config($this->getDataFolder()."data.properties", Config::PROPERTIES))->getAll());
            foreach($data as $name => $minutes){
                $this->register($name, $minutes);
            }
            @rename($this->getDataFolder()."data.properties", $this->getDataFolder()."data_old.properties");
        }

        if(($plugin = $this->getServer()->getPluginManager()->getPlugin("PurePerms")) !== null){
            $this->purePerms = $plugin;
            $this->getLogger()->info("Successfully loaded with PurePerms");
        }else{
            $this->getLogger()->alert("Dependency PurePerms not found");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->getServer()->getCommandMap()->register("timeranks", new TimeRanksCommand($this));
        $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new Timer($this), 1200, 1200);
    }

    public function onDisable(){
        $this->data->close();
    }

    public static function minutesToString($minutes){
        if($minutes < 60){
            return $minutes." minutes";
        }
        if(($modulo = $minutes % 60) === 0){
            return ($minutes / 60)." hours";
        }
        return ($minutes / 60)." hours and ".$modulo." minutes";
    }

}