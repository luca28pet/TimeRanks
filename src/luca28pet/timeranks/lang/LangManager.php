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

namespace luca28pet\timeranks\lang;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use luca28pet\configparser\ConfigNode;

/**
 * @internal
 */
final class LangManager {
	public const LANG_VERSION = 1;
	public const DEFAULTS = [
		'command-no-perm' => self::PREFIX.'You do not have permission to execute this command',
		'rank-command-self' => self::PREFIX.'You have played for {%minutes} minutes on the server. Rank: {%rank}',
		'rank-command-other' => self::PREFIX.'Player {%player} has played for {%minutes} minutes on the server. Rank: {%rank}',
		'rank-command-player-not-found' => self::PREFIX.'Player {%player} has never played on this server',
		'rank-command-name' => 'rank',
		'rank-command-desc' => 'TimeRanks command to check your own rank',
		'rank-command-usage' => 'Usage: /rank [player]',
		'rank-command-fail' => self::PREFIX.'An error has occurred, please try again in some time',
		'timeranks-command-name' => 'timeranks',
		'timeranks-command-desc' => 'General information about TimeRanks',
		'timeranks-command-usage' => 'Usage: /timeranks',
	];
	private const PREFIX = TextFormat::AQUA.'['.TextFormat::RED.'TimeRanks'.TextFormat::AQUA.'] '.TextFormat::WHITE;

	/** @var array<string, ?string> */
	private array $data;

	public function __construct(string $configPath, ?\Logger $logger) {
		if (!file_exists($configPath)) {
			yaml_emit_file($configPath, array_merge(self::DEFAULTS, ['lang-version' => self::LANG_VERSION]));
		}
		$root = new ConfigNode(yaml_parse_file($configPath));
		if ($root->getMapEntry('lang-version')?->toInt() !== self::LANG_VERSION) {
			$logger?->warning('Translation file is outdated. The old file has been renamed and a new one has been created');
			@rename($configPath, $configPath.'.old');
			yaml_emit_file($configPath, array_merge(self::DEFAULTS, ['lang-version' => self::LANG_VERSION]));
			$this->data = self::DEFAULTS;
		} else {
			foreach (self::DEFAULTS as $k => $v) {
				$this->data[$k] = $root->getMapEntry($k)?->toString();
			}
		}
	}

	/**
	 * @param array<string, string> $args
	 */
	public function getTranslation(string $dataKey, array $args) : string {
		if(!isset(self::DEFAULTS[$dataKey])){
			throw new \InvalidArgumentException('Invalid datakey '.$dataKey.' passed to method LangManager::getTranslation()');
		}
		$str = $this->data[$dataKey] ?? self::DEFAULTS[$dataKey];
		foreach($args as $key => $arg){
			$str = str_replace('{%'.$key.'}', $arg, $str);
		}
		return TextFormat::colorize($str);
	}
}

