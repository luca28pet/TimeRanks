<?php

namespace luca28pet\timeranks\lang;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

/**
 * @internal
 */
final class LangManager {
	public const LANG_VERSION = 1;
	public const DEFAULTS = [
		'lang-version' => self::LANG_VERSION,
		'tr-commadn-self' => self::PREFIX.'You have played for {%minutes} minutes on the server. Rank: {%rank}',
		'tr-command-other' => self::PREFIX.'Player {%player} has played for {%minutes} minutes on the server. Rank: {%rank}',
		'tr-command-player-not-found' => self::PREFIX.'Player {%player} has never played on this server',
		'tr-command-desc' => 'Main TimeRanks command',
		'tr-command-usage' => 'Usage: /tr <check> [player]',
		'tr-command-fail' => self::PREFIX.'An error has occurred, please try again in some time',
		'tr-command-no-perm' => self::PREFIX.'You do not have permission to execute this command'
	];
	private const PREFIX = TextFormat::AQUA.'['.TextFormat::RED.'TimeRanks'.TextFormat::AQUA.'] '.TextFormat::WHITE;

	/** @var array<mixed> */
	private array $data;

	public function __construct(string $configPath, ?\Logger $logger) {
		$this->data = (new Config($configPath, Config::YAML, self::DEFAULTS))->getAll();
		if(!isset($this->data['lang-version']) || $this->data['lang-version'] != self::LANG_VERSION){
			$logger?->warning('Translation file is outdated. The old file has been renamed and a new one has been created');
			@rename($configPath, $configPath.'.old');
			$this->data = (new Config($configPath, Config::PROPERTIES, self::DEFAULTS))->getAll();
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
			$str = str_replace('{%'.$key.'}', $arg, strval($str));
		}
		return TextFormat::colorize(strval($str));
	}
}

