<?php

/* Copyright 2021 luca28pet
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

namespace luca28pet\timeranks\task;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use poggit\libasynql\SqlError;
use luca28pet\timeranks\TimeRanksApi;
use Logger;

/**
 * @internal
 */
final class IncreaseMinutesTask extends Task {
	public function __construct(
		private TimeRanksApi $api,
		private Player $player,
		private ?Logger $logger
	) {}

	public function onRun() : void {
		if (!$this->player->isConnected()) {
			throw new CancelTaskException();
		}
		$this->api->incrementPlayerMinutes(
			$this->player->getName(),
			1,
			function() : void {},
			function(SqlError $err) : void {
				$this->logger?->error('Error while incrementing player minutes from task');
				$this->logger?->logException($err);
			}
		);
	}
}

