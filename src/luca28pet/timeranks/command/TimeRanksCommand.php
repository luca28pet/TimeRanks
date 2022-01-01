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
use pocketmine\command\CommandSender;
use luca28pet\timeranks\lang\LangManager;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

/**
 * @internal
 */
final class TimeRanksCommand extends Command implements PluginOwned {
	public function __construct(
		private LangManager $langManager,
		private Plugin $plugin
	) {
		parent::__construct(
			$this->langManager->getTranslation('timeranks-command-name', []),
			$this->langManager->getTranslation('timeranks-command-desc', []),
			$this->langManager->getTranslation('timeranks-command-usage', [])
		);
		$this->setPermission('timeranks.command.timeranks');
		$this->setPermissionMessage($this->langManager->getTranslation('command-no-perm', []));
	}

	public function execute(CommandSender $sn, string $lbl, array $args) : void {
		if (count($args) !== 0) {
			throw new InvalidCommandSyntaxException();
		}
		$sn->sendMessage('TimeRanks v'.$this->plugin->getDescription()->getVersion().' '.$this->plugin->getDescription()->getWebsite());
	}

	public function getOwningPlugin() : Plugin {
		return $this->plugin;
	}
}

