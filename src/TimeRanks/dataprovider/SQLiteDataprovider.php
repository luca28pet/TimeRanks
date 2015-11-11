<?php
namespace TimeRanks\dataprovider;

class SQLiteDataprovider extends \TimeRanks\dataprovider\Dataprovider {
	private $plugin;
	private $statements = array();
	private $dbpath;
	
	public function __construct(\TimeRanks\TimeRanks $plugin) {
		$this->plugin = $plugin;
		$this->db_path = $this->plugin->readcfg("database-path","{PLUGIN_DATA_FOLDER}");
		$this->db_path = str_replace("{PLUGIN_DATA_FOLDER}", $this->plugin->getDataFolder(), $this->db_path);
		if( ! $this->connectdb() ) {
			return false;
		}
		if( ! $this->setup_db() ) {
			return false;
		}
		return true;
	}
	
	// close statements on class destruct ( this should get done anyway though its here for clarity )
	public function __destruct() {
       foreach($this->statements as $statement) {
		   @$statement->close();
	   }
    }
	
	// defines behaviour for an unexpected database error (shutdown server to preserve data integrity)
	// perhaps add an option in config to shutdown plugin or server instance on db failure?
	private function criticalError($errmsg) {
		$this->plugin->getLogger()->critical($errmsg);
		$this->plugin->getServer()->getInstance()->shutdown();
	}
	
	// (Attempt to) connect to database
	private function connectdb() {
		$dbpath = $this->db_path;
		@mkdir($dbpath);
		try {
			$opts = file_exists($dbpath."timeranks.db") ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
			$this->data = new \SQLite3($dbpath."timeranks.db", $opts);
		} catch (Exception $exception) {
			$this->criticalError("Error opening TimeRanks DB " . $exception->getMessage());
			return false;
		}
		return true;
	}
	
	// create table and prepare statements
	private function setup_db() {
		// TODO catch errors
		$sql = "CREATE TABLE IF NOT EXISTS timeranks (name VARCHAR(16), minutes INTEGER)";
		if( ! $this->data->exec($sql) ) {
			$this->criticalError("Error creating timeranks table: " . $this->data->lastErrorMsg() );
			return false;
		}
		
		$statement_name = "register";
		$sql = "INSERT INTO timeranks (name, minutes) VALUES (:name, :minutes)";
		$this->statements[$statement_name] = $this->data->prepare($sql);
		if( $this->statements[$statement_name] === false ) {
			$this->criticalError("Error preparing statement  " . $statement_name . ": " . $this->data->lastErrorMsg() );
			return false;
		}
		
		$statement_name = "registerifnotexists";
		$sql = "INSERT INTO timeranks (name, minutes) 
				SELECT :name, :minutes
				WHERE NOT EXISTS(SELECT name FROM timeranks WHERE name = :name)";
		$this->statements[$statement_name] = $this->data->prepare($sql);
		if( $this->statements[$statement_name] === false ) {
			$this->criticalError("Error preparing statement  " . $statement_name . ": " . $this->data->lastErrorMsg() );
			return false;
		}
		
		$statement_name = "getMinutes";
		$sql = "SELECT minutes FROM timeranks WHERE name = :name";
		$this->statements[$statement_name] = $this->data->prepare($sql);
		if( $this->statements[$statement_name] === false ) {
			$this->criticalError("Error preparing statement  " . $statement_name . ": " . $this->data->lastErrorMsg() );
			return false;
		}
		
		$statement_name = "setMinutes";
		$sql = "UPDATE timeranks SET minutes = :minutes WHERE name = :name";
		$this->statements[$statement_name] = $this->data->prepare($sql);
		if( $this->statements[$statement_name] === false ) {
			$this->criticalError("Error preparing statement  " . $statement_name . ": " . $this->data->lastErrorMsg() );
			return false;
		}
	}
	
	// register player
	public function register($playerName, $minutes = 0) {
        if($this->getMinutes($playerName) === null) {
            $stmt = $this->statements["register"];

			if( ! $stmt->bindValue(":name", trim(strtolower($playerName)), SQLITE3_TEXT) ) {
				$this->criticalError("Failed to bind name to register statement:" . $this->data->lastErrorMsg() );
				return false;
			}
			
			if( ! $stmt->bindValue(":minutes", (int) $minutes, SQLITE3_INTEGER) ) {
				$this->criticalError("Failed to bind minutes to register statement:" . $this->data->lastErrorMsg() );
				return false;
			}
			
			if( ! $stmt->execute() ) {
				$this->criticalError("Failed to execute register statement:" . $this->data->lastErrorMsg() );
				return false;
			}

            return true;
        }
    }
	
	// fast multiple insert - used when importing old data
	public function register_multi($data) {
		if( ! $this->start_transaction() ) {
			return false;
		}
		
		$playerName = "";
		$stmt = $this->statements["registerifnotexists"];
		
		$added = 0;
		
		foreach($data as $rawPlayerName => $rawminutes) {
			$playerName = trim(strtolower($rawPlayerName));
			if( ! $stmt->bindValue(":name", $playerName, SQLITE3_TEXT) ) {
			$this->criticalError("Failed to bind name to register statement:" . $this->data->lastErrorMsg() );
			return false;
			}
			
			$minutes = (int) $rawminutes;
			if( ! $stmt->bindValue(":minutes", $minutes, SQLITE3_INTEGER) ) {
				$this->criticalError("Failed to bind minutes to register statement:" . $this->data->lastErrorMsg() );
				return false;
			}
			
			if( ! $stmt->execute() ) {
				$this->criticalError("Failed to execute register statement:" . $this->data->lastErrorMsg() );
				return false;
			}
			
			$added += $this->data->changes();
        }
		
		return $this->data->query("COMMIT;") === false ? false : $added;
	}
	
	// return minutes of a player
	public function getMinutes($playerName) {
		$stmt = $this->statements["getMinutes"];
		
		if( ! $stmt->bindValue(":name", trim(strtolower($playerName)), SQLITE3_TEXT) ) {
			$this->criticalError("Failed to bind name to getMinutes statement:" . $this->data->lastErrorMsg() );
			return false;
		}
		
        $res = $stmt->execute();
        if($res instanceof \SQLite3Result){
            $array = $res->fetchArray(SQLITE3_ASSOC);
            $res->finalize();
            $returnval = isset($array["minutes"]) ? $array["minutes"] : null;
			return $returnval;
        }

        return null;
    }
	
	// set the minutes of a player
	public function setMinutes($playerName, $minutes) {
		$playerName = $this->data->escapeString(trim(strtolower($playerName))); 
		$stmt = $this->statements["setMinutes"];
		
        if( ! $stmt->bindValue(":minutes", (int) $minutes, SQLITE3_INTEGER) ) {
			$this->criticalError("Failed to bind minutes to setMinutes statement:\n" . $this->data->lastErrorMsg() );
			return false;
		}
		
        if( ! $stmt->bindValue(":name", $playerName, SQLITE3_TEXT) ) {
			$this->criticalError("Failed to bind name to setMinutes statement:\n" . $this->data->lastErrorMsg() );
			return false;
		}
		
		if( ! $stmt->execute() ) {
			$this->criticalError("Failed to execute setMinutes statement:\n" . $this->data->lastErrorMsg() );
			return false;
        }
		
        return true;
    }
	
	// allow controlling transaction start from outside the class (e.g. when starting an increment of all users online)
	public function start_transaction() {
		if( ! $this->data->query("BEGIN TRANSACTION;") ) {
			$this->criticalError("Failed to start transaction: " . $this->data->lastErrorMsg() );
			return false;
		}
		return true;
	}
	
	// allow controlling transaction commits from outside the class (e.g. when ending an increment of all users online)
	public function commit_transaction() {
		if( ! $this->data->query("COMMIT;") ) {
			$this->criticalError("Failed to commit transaction: " . $this->data->lastErrorMsg() );
			return false;
		}
		return true;
	}
}
