<?php

namespace TimeRanks;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use TimeRanks\commands\TimeRanksCommand;
use TimeRanks\event\PlayerRankUpEvent;
use TimeRanks\task\Timer;
use TimeRanks\dataprovider\MySQLDataprovider;
use TimeRanks\dataprovider\SQLLiteDataprovider;


class TimeRanks extends PluginBase{

    /**@var null|TimeRanks*/
    public static $api = null;

    /**@var Rank[]*/
    public $ranks;
    /**@var \_64FF00\PurePerms\PurePerms*/
    public $purePerms;
    /**@var \TimeRanks\dataprovider\Dataprovider*/
    public $dataprovider;
	/**@var trackAllPlayersTimes*/
	public $trackAllPlayersTimes;
	/**@var cfg[]*/
	public $cfg;
	

    //API\\

    /**
     * @return null|TimeRanks
     */
    public static function getAPI(){
        return self::$api;
    }

    /**
     * @param string $playerName
     * @param int|string $minutes must be numeric
     */
    public function register($playerName, $minutes = 0){
        if( $this->dataprovider->register($playerName, $minutes) ) {
			$this->getLogger()->debug($playerName." has been registered in TimeRanks database");
		}
    }

    /**
     * @param string $playerName
     * @return int|null
     */
    public function getMinutes($playerName){
        return $this->dataprovider->getMinutes($playerName);
    }

    /**
     * @param string $playerName
     * @param int|string $minutes must be numeric
     * @param doCheckRankUp bool
     * @return bool
     */
    public function setMinutes($playerName, $minutes, $doCheckRankUp = true){
		$before = $this->dataprovider->getMinutes($playerName);
		$before = ( ! is_null($before) ) ? $before : 0;
		if( $this->dataprovider->setMinutes($playerName, $minutes) ) {
			if($doCheckRankUp) {
				$this->checkRankUp($playerName, $before, $minutes);
			}
			return true;
		}
		return false;
    }
	
	/**
     * @param string $playerName
     * @param int|string $minutes must be numeric
	 * @param doCheckRankUp bool
     * @return bool
     */
    public function incrementMinutes($playerName, $doCheckRankUp = true){
		$before = $this->dataprovider->getMinutes($playerName);
		$before = ( ! is_null($before) ) ? $before : 0;
		if( $this->dataprovider->setMinutes($playerName, $minutes) ) {
			if($doCheckRankUp) {
				$this->checkRankUp($playerName, $before, $minutes);
			}
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
        $old = $this->getRankFromMinutes((int) $before);
        $new = $this->getRankFromMinutes((int) $after);
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
		$mins = $this->dataprovider->getMinutes($playerName);
        return !is_null($mins) ? $this->getRankFromMinutes($mins) : null;
    }

    /**
     * @param int|string $minutes must be numeric
     * @return Rank
     */
    public function getRankFromMinutes($minutes){
		$minutes = (int) $minutes;
		$lastRankMins = 0;
		$currank = $this->getDefaultRank();
		foreach($this->ranks as $rank){
			if( ($rank->getMinutes() < $minutes) && ($rank->getMinutes() > $currank->getMinutes()) ) {
				$currank = $rank;
			}
		}
		return $currank;
		
		/* This doesnt work for me - Serialization of 'Closure' is not allowed
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
		*/
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
	
	// check key exists, if not return default value, if default value not given raise error
	public function readcfg($keyname, $defaultvalue = null) {
		if( isset($this->cfg[$keyname]) ) {
			return $this->cfg[$keyname];
		}
		if( ! is_null($defaultvalue) ) {
			return $defaultvalue;
		}
		$this->getLogger()->alert("No value found in config.yml for key " . $keyname);
        $this->getServer()->getPluginManager()->disablePlugin($this);
        return null;
 	}

    public function onEnable(){
		$this->saveDefaultConfig();
		$this->cfg = $this->getConfig()->getAll();
        if(($plugin = $this->getServer()->getPluginManager()->getPlugin("PurePerms")) !== null){
            $this->purePerms = $plugin;
            $this->getLogger()->info("Successfully loaded with PurePerms");
        }else{
            $this->getLogger()->alert("Dependency PurePerms not found");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
		
		$dataprovidername = $this->readcfg("dataprovider" , "sqlite3");
		switch($dataprovidername) {
			case "mysql":
				$this->dataprovider = New \TimeRanks\dataprovider\MySQLDataprovider($this);
				break;
			case "sqlite3":
				$this->dataprovider = New \TimeRanks\dataprovider\SQLiteDataprovider($this);
				break;
			default:
				$this->getLogger()->alert("Invalid data provider name set in config, valid values are mysql, sqlite3");
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return null;
				break;
		}
		
		// TODO should work if setting doesnt exist - default false
		$this->trackAllPlayersTimes = $this->readcfg("track-all-players-times" , false);
		
		// If dataprovider setup failed return now
		if($this->dataprovider === false) {
			return false;
		}
		
		// check for older data.properties file and copy to new db
        if(file_exists($this->getDataFolder()."data.properties")){
            $data = array_map("intval", (new Config($this->getDataFolder()."data.properties", Config::PROPERTIES))->getAll());
            $logmsg = "found old data.properties file - attempting to copy " . count($data) . " users to  " . $dataprovidername;
			$this->getLogger()->alert($logmsg);
			$result = $this->dataprovider->register_multi($data);
			if($result === false ) {
				$logmsg = "Could not add old users - shutting down to preserve data integrity.";
				$this->getLogger()->alert(logmsg);
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return;
			} else {
				$logmsg = "Added " . $result . " users to new " . $dataprovidername . " database";
				$this->getLogger()->alert($logmsg);
				@rename($this->getDataFolder()."data.properties", $this->getDataFolder()."data_old.properties");
			}
            
        }

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

        $this->getServer()->getCommandMap()->register("timeranks", new TimeRanksCommand($this));
        $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new Timer($this), 1200, 1200);
    }

    public function onDisable(){
        unset($this->dataprovider);
    }

    public static function minutesToString($minutes){
        if($minutes < 60){
            return $minutes." minutes";
        }
        if(($modulo = $minutes % 60) === 0){
            return ($minutes / 60)." hours";
        }
        return floor($minutes / 60)." hours and ".$modulo." minutes";
    }

}