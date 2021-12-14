-- #! mysql

-- # {timeranks

-- #   {create_tables
CREATE TABLE IF NOT EXISTS timeranks_minutes (
	recordId BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	playerName VARCHAR(32) NOT NULL UNIQUE,
	minutes BIGINT NOT NULL
) ENGINE = INNODB CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- #   }

-- #   {get_player
-- #     :player string
SELECT * FROM timeranks_minutes WHERE playerName = :player;
-- #   }

-- #   {set_player_minutes
-- #     :player string
-- #     :minutes int
INSERT INTO timeranks_minutes (playerName, minutes) VALUES (:player, :minutes) ON DUPLICATE KEY UPDATE minutes = :minutes;
-- #   }

-- #   {increment_player_minutes
-- #     :player string
-- #     :minutes int
INSERT INTO timeranks_minutes (playerName, minutes) VALUES (:player, :minutes) ON DUPLICATE KEY UPDATE minutes = minutes + :minutes;
-- #   }

-- # }

