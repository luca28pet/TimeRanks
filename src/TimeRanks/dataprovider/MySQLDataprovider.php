<?php
namespace TimeRanks\dataprovider;

class MySQLDataprovider extends \TimeRanks\dataprovider\Dataprovider {
	private $statements = array();
	private $data;
	private $db_table;
        private $cache;
        
        public function getFromCache($playerName) {
            $playerName = strtolower($playerName);
            if( ! isset($this->cache[$playerName]) ) {
                return false;
            }
            $cacheAge = $this->cache[$playerName]["cacheAge"];
            $now = time();
            $cacheIsStale = ($now - $cacheAge) > 240; // 4 mins
            if($cacheIsStale) {
                unset($this->cache[$playerName]);
                return false;
            }
            return $this->cache[$playerName]["mins"];
        }
        
        public function storeInCache($playerName, $mins) {
            $playerName = strtolower($playerName);
            $this->cache[$playerName] = [
                "cacheAge" => time(),
                "mins" => $mins
            ];
        }
	
	public function __construct(\TimeRanks\TimeRanks $plugin) {
		$this->plugin = $plugin;
		
		if( ! $this->connect_db() ) {
			return false;
		}
		
		if( ! $this->setup_db() ) {
			return false;
		}
	}
	
	// defines behaviour for an unexpected database error (shutdown server to preserve data integrity)
	// perhaps add an option in config to shutdown plugin or server instance on db failure?
	private function criticalError($errmsg) {
		$this->plugin->getLogger()->critical($errmsg);
		$this->plugin->getServer()->getInstance()->shutdown();
	} 
	
	// (Attempt to) Prepare a statement and add the statements array
	private function checkPreparedStatement($queryname, $sql) {
		if (! isset ( $this->statements [$queryname] )) {
			$this->statements [$queryname] = $this->data->prepare ( $sql );
		}
		if ($this->statements [$queryname] === false) {
			$this->criticalError ( "Database error preparing query for  " . $queryname . ": " . $this->data->error );
			return false;
		}
		return true;
	}
	
	// (Attempt to) connect to database
	// TODO human readable error not config values not set
	private function connect_db() {
		$host = $this->plugin->readcfg("mysql-host");
		$user = $this->plugin->readcfg("mysql-user");
		$pass = $this->plugin->readcfg("mysql-pass");
		$dbname = $this->plugin->readcfg("mysql-database");
		$this->db_table = $this->plugin->readcfg("mysql-database_table");
		
		if( is_null($host) || is_null($user) || is_null($pass)  || is_null($dbname)  || is_null($this->db_table) ) {
			return false;
		}
		
		$this->data = new \mysqli ( $host, $user, $pass, $dbname );
		if ($this->data->connect_errno) {
			$errmsg = $this->criticalError ( "Error connecting to database: " . $this->data->error );
			return false;
		}
		
		return true;
	}
	
	// (Attempt to) create database table and prepare statements that will be used
	private function setup_db() {
		// TODO catch errors
		$statement_name = "db table setup";
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->db_table . "`
				(`name` VARCHAR(16), `minutes` INTEGER, PRIMARY KEY (`name`) )";
		if( ! $this->checkPreparedStatement($statement_name, $sql) ) {
			return false;
		}
		$qresult = $this->statements[$statement_name]->execute();
		if ($qresult === false) {
			$errmsg = "Database error executing " . $statement_name . " " . $this->statements[$statement_name]->error;
			$this->criticalError ( $errmsg );
			return false;
		}
		$this->statements[$statement_name]->free_result();
		
		$statement_name = "register or update";
		$sql = "INSERT INTO `" . $this->db_table . "` (`name`, `minutes`) 
				VALUES (?, ?) 
				ON DUPLICATE KEY UPDATE 
				minutes = GREATEST(`" . $this->db_table . "`.`minutes`, ?);";
		if( ! $this->checkPreparedStatement($statement_name, $sql) ) {
			return false;
		}
		
		$statement_name = "get minutes";
		$sql = "SELECT `minutes` FROM `" . $this->db_table . "` WHERE `name` = ?;";
		if( ! $this->checkPreparedStatement($statement_name, $sql) ) {
			return false;
		}
	}
	
	// updates a users minutes value or adds if row not exists
	// ignores (via MySQL statement) a lower minutes value than currentley held
	private function registerorupdate($playerName, $minutes = 0) {
		$playerName = strtolower($playerName);
		$statement_name = "register or update";
		
		$result = $this->statements[$statement_name]->bind_param ( "sii", $playerName, $minutes, $minutes );
		if ($result === false) {
			$errmsg = "Failed to bind to statement " . $statement_name . ": " . $this->statements[$statement_name]->error;
			$this->criticalError ( $errmsg );
			return false;
		}
		
		$result = $this->statements[$statement_name]->execute();
		if (! $result) {
			$errmsg = "Database error executing " . $statement_name . " " . $this->statements [$statement_name]->error;
			$this->criticalError( $errmsg ) ;
			@$this->statements[$statement_name]->free_result();
			return false;
		}
		$this->statements [$statement_name]->free_result ();
		return true;
	}
	
	// wraps to registerorupdate
	public function register($playerName, $minutes = 0) {
		if( registerorupdate($playerName, $minutes = 0) ) {
			return true;
		}
		return false;
    }
	
	// fast multiple insert - used when importing old data
	public function register_multi($data) {
		if( ! $this->start_transaction() ) {
			return;
		}
		
		$statement_name = "register or update";
		
		$result = $this->statements[$statement_name]->bind_param ( "sii", $playerName, $minutes, $minutes );
		if ($result === false) {
			$errmsg = "Failed to bind to statement " . $statement_name . ": " . $this->statements[$statement_name]->error;
			$this->criticalError ( $errmsg );
			return false;
		}
		
		$added = 0;
		
		foreach($data as $rawPlayerName => $rawminutes) {
			$playerName = trim(strtolower($rawPlayerName));
			$minutes = (int) $rawminutes;
			$result = $this->statements[$statement_name]->execute();
			if (! $result) {
				$errmsg = "Database error executing " . $statement_name . " " . $this->statements [$statement_name]->error;
				$this->criticalError( $errmsg ) ;
				@$this->statements[$statement_name]->free_result();
				return false;
			}
			$added += $this->statements[$statement_name]->affected_rows;
		}
		
		return $this->commit_transaction() === false ? false : $added;
	}
	
	// return current minutes of player, or null on failure / not exists
	public function getMinutes($playerName){
		$playerName = strtolower($playerName);
                $cached = $this->getFromCache($playerName);
                if($cached !== false) {
                    return $cached;
                }
		$statement_name = "get minutes";
		
		$result = $this->statements[$statement_name]->bind_param ( "s", $playerName );
		if ($result === false) {
			$errmsg = "Failed to bind to statement " . $statement_name . ": " . $this->statements[$statement_name]->error;
			$this->criticalError ( $errmsg );
			return null;
		}
		
		$result = $this->statements[$statement_name]->execute();
		if (! $result) {
			$errmsg = "Database error executing " . $statement_name . " " . $this->statements[$statement_name]->error;
			$this->criticalError( $errmsg ) ;
			@$this->statements[$statement_name]->free_result();
			return null;
		}
		
		$result = $this->statements[$statement_name]->bind_result($db_minutes);
		if (! $result) {
			$errmsg = "Database error binding result " . $statement_name . " " . $this->statements[$statement_name]->error;
			$this->criticalError( $errmsg ) ;
			@$this->statements[$statement_name]->free_result();
			return null;
		}
		
		if ( $this->statements[$statement_name]->fetch() ) {
			$minutes = $db_minutes;
			$this->statements[$statement_name]->free_result();
			return $minutes;
		}
		
		$this->statements[$statement_name]->free_result();
		return null;
    }
	
	// wraps to registerorupdate
	public function setMinutes($playerName, $minutes) {
                $this->storeInCache($playerName, $minutes);
		return $this->registerorupdate($playerName, $minutes);
	}
	
	// allow controlling transaction starts from outside the class (e.g. when beginning an increment of all users online)
	public function start_transaction() {
		if( ! $this->data->query("START TRANSACTION;") ) {
			$this->criticalError("Failed to start transaction: " . $this->data->error );
			return false;
		}
		return true;
	}
	
	// allow controlling transaction commits from outside the class (e.g. when ending an increment of all users online)
	public function commit_transaction() {
		if( ! $this->data->query("COMMIT;") ) {
			$this->criticalError("Failed to commit transaction: " . $this->data->error );
			return false;
		}
		return true;
	}
}