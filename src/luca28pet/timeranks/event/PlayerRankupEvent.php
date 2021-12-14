<?php
declare(strict_types=1);

namespace luca28pet\timeranks\event;

use pocketmine\event\Event;
use luca28pet\timeranks\Rank;

class PlayerRankupEvent extends Event {
	public function __construct(
		private string $playerName,
		private Rank $oldRank,
		private Rank $newRank
	) {}

	public function getPlayerName() : string {
		return $this->playerName;
	}

	public function getOldRank() : Rank {
		return $this->oldRank;
	}

	public function getNewRank() : Rank {
		return $this->newRank;
	}
}

