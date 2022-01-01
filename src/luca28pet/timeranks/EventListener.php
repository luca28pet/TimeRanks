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

use pocketmine\scheduler\TaskScheduler;
use Logger;
use luca28pet\timeranks\task\IncreaseMinutesTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use poggit\libasynql\SqlError;
use pocketmine\event\player\PlayerQuitEvent;

/**
 * @internal
 */
final class EventListener implements Listener {
	public function __construct(
		private TaskScheduler $scheduler,
		private TimeRanksApi $api,
		private ?Logger $logger
	) {}

	public function onJoin(PlayerJoinEvent $ev) : void {
		$this->api->registerPlayer(
			$ev->getPlayer()->getName(),
			function() use ($ev) : void {
				$this->scheduler->scheduleDelayedRepeatingTask(new IncreaseMinutesTask(
					$this->api, $ev->getPlayer(), $this->logger), 1200, 1200);
			},
			function(SqlError $err) use ($ev) : void {
				$this->logger?->error('Registration of '.$ev->getPlayer()->getName().' failed with error: '.$err->getMessage());
				$this->logger?->logException($err);
			}
		);
	}

	public function onQuit(PlayerQuitEvent $ev) : void {
		$this->api->deleteCacheEntry($ev->getPlayer()->getName());
	}
}

