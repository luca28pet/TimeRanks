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

namespace luca28pet\timeranks;

use luca28pet\timeranks\event\PlayerRankChangeEvent;
use luca28pet\timeranks\io\DataBase;
use luca28pet\timeranks\Rank;
use poggit\libasynql\SqlError;
use pocketmine\Server;
use pocketmine\console\ConsoleCommandSender;

final class TimeRanksApi {
	public function getPlayerMinutesCached(string $name) : ?int {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new \InvalidArgumentException('Invalid name');
		}
		return $this->minutesCache[mb_strtolower($name, 'UTF-8')] ?? null;
	}

	public function deleteCacheEntry(string $name) : bool {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new \InvalidArgumentException('Invalid name');
		}
		$iname = mb_strtolower($name, 'UTF-8');
		if (isset($this->minutesCache[$iname])) {
			unset($this->minutesCache[$iname]);
			return true;
		}
		return false;
	}

	/**
	 * @param callable(?int $minutes) : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 */
	public function getPlayerMinutes(string $name, callable $onCompletion, callable $onError) : void {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new \InvalidArgumentException('Invalid name');
		}
		$this->dataBase->getPlayerMinutes($name, function(?int $minutes) use ($name, $onCompletion) : void {
			if ($minutes !== null) {
				$this->minutesCache[mb_strtolower($name, 'UTF-8')] = $minutes;
			}
			$onCompletion($minutes);
		}, $onError);
	}

	/**
	 * Check if the player exists in the database, if not, register the player
	 * @param callable() : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 */
	public function registerPlayer(string $name, callable $onCompletion, callable $onError) : void {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new \InvalidArgumentException('Invalid name');
		}
		$this->getPlayerMinutes(
			$name,
			function(?int $minutes) use ($name, $onCompletion, $onError) : void {
				if ($minutes !== null) {
					//Player is already registered
					$onCompletion();
					return;
				}
				$this->dataBase->setPlayerMinutes(
					$name,
					0,
					function() use ($name, $onCompletion) : void {
						$this->minutesCache[mb_strtolower($name, 'UTF-8')] = 0;
						$this->checkRankChange($name, null, 0);
						$onCompletion();
					},
					$onError
				);
			},
			$onError
		);
	}

	/**
	 * @param callable() : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 */
	public function setPlayerMinutes(string $name, int $minutes, callable $onCompletion, callable $onError) : void {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if ($minutes < 0) {
			throw new \InvalidArgumentException('Minutes must be non negative');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new \InvalidArgumentException('Invalid name');
		}
		$this->getPlayerMinutes(
			$name,
			function(?int $oldMinutes) use ($name, $minutes, $onCompletion, $onError) : void {
				if ($oldMinutes === $minutes) {
					$onCompletion();
					return;
				}
				$this->dataBase->setPlayerMinutes(
					$name,
					$minutes,
					function() use ($name, $oldMinutes, $minutes, $onCompletion) : void {
						$this->minutesCache[mb_strtolower($name, 'UTF-8')] = $minutes;
						$this->checkRankChange($name, $oldMinutes, $minutes);
						$onCompletion();
					},
					$onError
				);
			},
			$onError
		);
	}

	/**
	 * @param callable() : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 */
	public function incrementPlayerMinutes(string $name, int $minutes, callable $onCompletion, callable $onError) : void {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if ($minutes < 0) {
			throw new \InvalidArgumentException('Minutes must be non negative');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new \InvalidArgumentException('Invalid name');
		}
		if ($minutes === 0) {
			$onCompletion();
			return;
		}
		$this->getPlayerMinutes(
			$name,
			function(?int $oldMinutes) use ($name, $minutes, $onCompletion, $onError) : void {
				$this->dataBase->incrementPlayerMinutes(
					$name,
					$minutes,
					function() use ($name, $oldMinutes, $minutes, $onCompletion) : void {
						$this->minutesCache[mb_strtolower($name, 'UTF-8')] = ($oldMinutes ?? 0) + $minutes;
						$this->checkRankChange($name, $oldMinutes, ($oldMinutes ?? 0) + $minutes);
						$onCompletion();
					},
					$onError
				);
			},
			$onError
		);
	}

	/**
	 * @return Rank[] all the ranks configured by the user
	 */
	public function getRanks() : array {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		return $this->ranks;
	}

	public function getDefaultRank() : Rank {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		return $this->defaultRank;
	}

	/**
	 * @param int $minutes a non-negative integer
	 */
	public function getRankFromMinutes(int $minutes) : Rank {
		if ($this->closed) {
			throw new \BadMethodCallException('API has been destructed');
		}
		if ($minutes < 0) {
			throw new \InvalidArgumentException('Minutes must be non negative');
		}
		foreach ($this->ranks as $rank) {
			if ($minutes >= $rank->getMinutes()) {
				return $rank;
			}
		}
		throw new \LogicException();
	}

	/**
	 * @internal
	 */
	public function close() : void {
		$this->dataBase->close();
		$this->minutesCache = [];
		$this->closed = true;
	}

	private bool $closed = false;
	/** @var array<string, int> */
	private $minutesCache = [];

	private function checkRankChange(string $name, ?int $oldMinutes, int $newMinutes) : void {
		$oldRank = $oldMinutes === null ? null : $this->getRankFromMinutes($oldMinutes);
		$newRank = $this->getRankFromMinutes($newMinutes);
		if ($oldRank === $newRank) {
			return;
		}
		if ($this->server->getPlayerExact($name)?->hasPermission('timeranks.exempt') !== true) {
			(new PlayerRankChangeEvent($name, $oldRank, $newRank))->call();
			$this->server->getPlayerExact($name)?->sendMessage($newRank->getMessage());
			foreach ($newRank->getCommands() as $cmd) {
				$this->server->dispatchCommand(new ConsoleCommandSender(
					$this->server, $this->server->getLanguage()), str_replace('{%player}', $name, $cmd));
			}
		}
	}

	/**
	 * @internal
	 */
	public function __construct(
		/** @var Rank[] */
		private array $ranks,
		private Rank $defaultRank,
		private DataBase $dataBase,
		private Server $server
	) {
		if (!in_array($defaultRank, $ranks, true)) {
			throw new \InvalidArgumentException('The default rank must be included in the ranks list');
		}
		if ($defaultRank->getMinutes() !== 0) {
			throw new \InvalidArgumentException('The default rank is only allowed to have minutes = 0');
		}
		$minutesValues = [];
		foreach ($ranks as $rank) {
			if (isset($minutesValues[$rank->getMinutes()])) {
				throw new \InvalidArgumentException('There are two or more ranks with the same minutes setting');
			}
			$minutesValues[$rank->getMinutes()] = true;
		}
		usort($ranks, function(Rank $a, Rank $b) : int {
			return $b->getMinutes() <=> $a->getMinutes();
		});
		$this->ranks = array_values($ranks);
	}
}

