<?php
declare(strict_types=1);

namespace luca28pet\timeranks;

use luca28pet\timeranks\event\PlayerRankupEvent;
use luca28pet\timeranks\io\DataBase;
use luca28pet\timeranks\Rank;
use poggit\libasynql\SqlError;
use pocketmine\Server;
use pocketmine\console\ConsoleCommandSender;

final class TimeRanksApi {
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
		$this->dataBase->getPlayerMinutes($name, $onCompletion, $onError);
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
				$oldRank = self::getRankFromMinutes($oldMinutes ?? 0);
				$this->dataBase->setPlayerMinutes(
					$name,
					$minutes,
					function() use ($oldRank, $name, $oldMinutes, $minutes, $onCompletion) : void {
						if ($this->server->getPlayerExact($name)?->hasPermission('timeranks.exempt') !== true) {
							$newRank = self::getRankFromMinutes(($oldMinutes ?? 0) + $minutes);
							if ($oldRank === $newRank) {
								return;
							}
							(new PlayerRankupEvent($name, $oldRank, $newRank))->call();
							$this->server->getPlayerExact($name)?->sendMessage($newRank->getMessage());
							foreach ($newRank->getCommands() as $cmd) {
								$this->server->dispatchCommand(new ConsoleCommandSender(
									$this->server, $this->server->getLanguage()), str_replace('{%player}', $name, $cmd));
							}
						}
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
		$this->getPlayerMinutes(
			$name,
			function(?int $oldMinutes) use ($name, $minutes, $onCompletion, $onError) : void {
				$oldRank = self::getRankFromMinutes($oldMinutes ?? 0);
				$this->dataBase->incrementPlayerMinutes(
					$name,
					$minutes,
					function() use ($oldRank, $name, $oldMinutes, $minutes, $onCompletion) : void {
						if ($this->server->getPlayerExact($name)?->hasPermission('timeranks.exempt') !== true) {
							$newRank = self::getRankFromMinutes(($oldMinutes ?? 0) + $minutes);
							if ($oldRank === $newRank) {
								return;
							}
							(new PlayerRankupEvent($name, $oldRank, $newRank))->call();
							$this->server->getPlayerExact($name)?->sendMessage($newRank->getMessage());
							foreach ($newRank->getCommands() as $cmd) {
								$this->server->dispatchCommand(new ConsoleCommandSender(
									$this->server, $this->server->getLanguage()), str_replace('{%player}', $name, $cmd));
							}
						}
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
		$this->closed = true;
	}

	private bool $closed = false;

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

