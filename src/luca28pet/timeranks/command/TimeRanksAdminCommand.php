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

namespace luca28pet\timeranks\command;

use pocketmine\command\Command;
use luca28pet\timeranks\TimeRanksApi;
use pocketmine\command\CommandSender;
use luca28pet\timeranks\lang\LangManager;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

/**
 * @internal
 */
final class TimeRanksAdminCommand extends Command implements PluginOwned {
	public function __construct(
		private TimeRanksApi $api,
		private Plugin $plugin
	) {
		parent::__construct(
			'timeranksadmin',
			'TimeRanks admin command',
			'/tradm setmin <player> <minutes>',
			['tradm']
		);
		$this->setPermission('timeranks.command.timeranksadmin');
	}

	public function execute(CommandSender $sn, string $lbl, array $args) : void {
		$argc = count($args);
		if ($argc < 1) {
			throw new InvalidCommandSyntaxException();
		}
		switch ($args[0]) {
		case 'setmin':
			if ($argc !== 3) {
				throw new InvalidCommandSyntaxException();
			}
			$target = $args[1];
			if (!mb_check_encoding($target, 'UTF-8')) {
				$sn->sendMessage('Invalid string');
				return;
			}
			$minString = $args[2];
			if (!ctype_digit($minString)) {
				$sn->sendMessage('Minutes must be a number');
				return;
			}
			$minutes = (int) $minString;
			if ($minutes < 0) {
				$sn->sendMessage('Minutes cannot be negative');
				return;
			}
			$this->api->setPlayerMinutes(
				$target,
				$minutes,
				function() use ($sn, $target, $minutes) : void {
					if (!($sn instanceof Player) || $sn->isConnected()) {
						$sn->sendMessage('Set '.$target.' minutes to '.$minutes);
					}
				},
				function(SqlError $err) use ($sn) : void {
					if (!($sn instanceof Player) || $sn->isConnected()) {
						$sn->sendMessage('DataBase error');
					}
				}
			);
			break;
		default:
			throw new InvalidCommandSyntaxException();
		}
	}

	public function getOwningPlugin() : Plugin {
		return $this->plugin;
	}
}

