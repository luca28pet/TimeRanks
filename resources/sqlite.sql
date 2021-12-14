-- #! sqlite

-- # {timeranks

-- #   {create_tables
CREATE TABLE IF NOT EXISTS timeranks_minutes (
	recordId INTEGER PRIMARY KEY,
	playerName TEXT NOT NULL UNIQUE,
	minutes INTEGER NOT NULL
);
-- #   }

-- #   {get_player
-- #     :player string
SELECT * FROM timeranks_minutes WHERE playerName = :player;
-- #   }

-- #   {set_player_minutes
-- #     :player string
-- #     :minutes int
INSERT INTO timeranks_minutes (playerName, minutes) VALUES (:player, :minutes) ON CONFLICT(playerName) DO UPDATE SET minutes = :minutes;
-- #   }

-- #   {increment_player_minutes
-- #     :player string
-- #     :minutes int
INSERT INTO timeranks_minutes (playerName, minutes) VALUES (:player, :minutes) ON CONFLICT(playerName) DO UPDATE SET minutes = minutes + :minutes;
-- #   }

-- # }

