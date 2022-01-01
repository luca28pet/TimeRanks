<?php

/* Copyright 2021, 2022 luca28pet
 *
 * This file is part of TimeRanks.
 * TimeRanks is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 only,
 * as published by the Free Software Foundation.
 *
 * TimeRanks is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with TimeRanks. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace luca28pet\timeranks\event;

use pocketmine\event\Event;
use luca28pet\timeranks\Rank;

/**
 * Called when the rank of a player (online or offline) changes
 */
final class PlayerRankChangeEvent extends Event {
	public function __construct(
		private string $playerName,
		private ?Rank $oldRank,
		private Rank $newRank
	) {}

	public function getPlayerName() : string {
		return $this->playerName;
	}

	/**
	 * @return ?Rank the old Rank of the player or null if the player was not
	 * registered
	 */
	public function getOldRank() : ?Rank {
		return $this->oldRank;
	}

	public function getNewRank() : Rank {
		return $this->newRank;
	}
}

