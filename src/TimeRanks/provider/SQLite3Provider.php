<?php

namespace TimeRanks\provider;

use TimeRanks\TimeRanks;

class SQLite3Provider implements TimeRanksProvider{

    private $tr;
    private $db;

    public function __construct(TimeRanks $tr){
        $this->tr = $tr;
        $this->db = new \SQLite3($this->tr->getDataFolder()."timeranks.db", file_exists($this->tr->getDataFolder()."timeranks.db") ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $this->db->exec("CREATE TABLE IF NOT EXISTS timeranks (name VARCHAR(16) PRIMARY KEY, minutes INTEGER)");
    }

    public function isPlayerRegistered($name){
        $res = $this->db->query("SELECT * FROM timeranks WHERE name = '".$this->db->escapeString(strtolower($name))."'");
        if($res instanceof \SQLite3Result){
            return $res->numColumns() > 0;
        }
        return false;
    }

    public function registerPlayer($name){
        $this->db->exec("INSERT OR IGNORE INTO timeranks (name, minutes) VALUES ('".$this->db->escapeString(strtolower($name))."', 0)");
    }

    public function getMinutes($name){
        $res = $this->db->query("SELECT minutes FROM timeranks WHERE name = '".$this->db->escapeString(strtolower($name))."'");
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);
            $res->finalize();
            return isset($array["minutes"]) ? (int) $array["minutes"] : false;
        }
        return false;
    }

    public function setMinutes($name, $minutes){
        if(!$this->isPlayerRegistered($name)){
            $this->registerPlayer($name);
        }
        $this->db->exec("UPDATE timeranks SET minutes = '".$minutes."' WHERE name = '".$this->db->escapeString(strtolower($name))."'");
    }

}