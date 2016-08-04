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

    public function isPlayerRegistered(string $name) : bool{
        return $this->db->querySingle("SELECT * FROM timeranks WHERE name = '".$this->db->escapeString(strtolower($name))."'");
    }

    public function registerPlayer(string $name){
        $this->db->exec("INSERT OR IGNORE INTO timeranks (name, minutes) VALUES ('".$this->db->escapeString(strtolower($name))."', 0)");
    }

    public function getMinutes(string $name) : int{
        $res = $this->db->query("SELECT minutes FROM timeranks WHERE name = '".$this->db->escapeString(strtolower($name))."'");
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);
            $res->finalize();
            return $array["minutes"] ?? -1;
        }
        return -1;
    }

    public function setMinutes(string $name, int $minutes){
        $this->db->exec("UPDATE timeranks SET minutes = '".$minutes."' WHERE name = '".$this->db->escapeString(strtolower($name))."'");
    }

    public function close(){
        $this->db->close();
    }

}