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

namespace luca28pet\timeranks\command;

use pocketmine\command\Command;
use luca28pet\timeranks\TimeRanksApi;
use pocketmine\command\CommandSender;
use luca28pet\timeranks\lang\LangManager;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

/**
 * @internal
 */
final class RankCommand extends Command {
	public function __construct(
		private TimeRanksApi $api,
		private LangManager $langManager
	) {
		parent::__construct(
			$this->langManager->getTranslation('rank-command-name', []),
			$this->langManager->getTranslation('rank-command-desc', []),
			$this->langManager->getTranslation('rank-command-usage', [])
		);
		$this->setPermission('timeranks.command.rank.self');
		$this->setPermissionMessage($this->langManager->getTranslation('command-no-perm', []));
	}

	public function execute(CommandSender $sn, string $lbl, array $args) : void {
		if (count($args) > 1) {
			throw new InvalidCommandSyntaxException();
		}
		if (isset($args[0])) {
			$target = $args[0];
		} else {
			$target = $sn->getName();
		}
		if ($target !== $sn->getName() && !$sn->hasPermission('timeranks.command.rank.others')) {
			$sn->sendMessage($this->langManager->getTranslation('command-no-perm', []));
			return;
		}
		if (!mb_check_encoding($target, 'UTF-8')) {
			$sn->sendMessage('Invalid string');
			return;
		}
		$this->api->getPlayerMinutes(
			$target,
			function(?int $minutes) use ($sn, $target) : void {
				if ($sn instanceof Player && !$sn->isConnected()) {
					return;
				}
				if ($minutes !== null) {
					if ($sn->getName() !== $target) {
						$sn->sendMessage($this->langManager->getTranslation('rank-command-other', [
							'player' => $target,
							'minutes' => (string) $minutes,
							'rank' => $this->api->getRankFromMinutes($minutes)->getName()
						]));
					} else {
						$sn->sendMessage($this->langManager->getTranslation('rank-command-self', [
							'minutes' => (string) $minutes,
							'rank' => $this->api->getRankFromMinutes($minutes)->getName()
						]));
					}
				} else {
					$sn->sendMessage($this->langManager->getTranslation('rank-command-player-not-found', [
						'player' => $target
					]));
				}
			},
			function(SqlError $err) use ($sn) : void {
				if (!($sn instanceof Player) || $sn->isConnected()) {
					$sn->sendMessage($this->langManager->getTranslation('rank-command-fail', []));
				}
			}
		);
	}
}

