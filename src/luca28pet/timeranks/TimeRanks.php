<?php

namespace luca28pet\timeranks;

use pocketmine\plugin\PluginBase;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use pocketmine\console\ConsoleCommandSender;
use luca28pet\timeranks\io\DataBase;
use luca28pet\timeranks\command\TimeRanksCommand;
use luca28pet\timeranks\lang\LangManager;

final class TimeRanks extends PluginBase {
	private TimeRanksApi $api;

    protected function onEnable() : void {
		$this->saveResource('ranks.yml');
		$data = yaml_parse_file($this->getDataFolder().'ranks.yml');
		if ($data === false) {
			$this->getLogger()->error('Failed loading ranks.yml');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		if (!is_array($data) || !isset($data['ranks']) || !is_array($data['ranks'])) {
			$this->getLogger()->error('Cannot find ranks field in ranks.yml');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		try {
			$ranks = ConfigParser::getList($data['ranks'], [ConfigParser::class, 'getRank']);
		} catch (\InvalidArgumentException $e) {
			$this->getLogger()->error('Configuration error in ranks.yml');
			do {
				$this->getLogger()->error($e->getMessage());
			} while (($e = $e->getPrevious()) !== null);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$defaultRanks = 0;
		$defaultRank = null;
		foreach ($ranks as $rank) {
			if ($rank->isDefault()) {
				++$defaultRanks;
				$defaultRank = $rank;
			}
		}
		if ($defaultRanks !== 1) {
			$this->getLogger()->error('Only one default rank allowed, found '.$defaultRanks);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->reloadConfig();
		try {
			$dataBase = new DataBase(libasynql::create($this, $this->getConfig()->get('database'), [
				'mysql' => 'mysql.sql',
				'sqlite' => 'sqlite.sql'
			]));
		} catch (SqlError $err) {
			$this->getLogger()->error('Database error');
			$this->getLogger()->logException($err);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		// @phpstan-ignore-next-line
		$this->api = new TimeRanksApi($ranks, $defaultRank, $dataBase, $this->getServer());

		$langManager = new LangManager($this->getDataFolder().'lang.yml', $this->getLogger());
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this->getScheduler(), $this->api, $this->getLogger()), $this);
		$this->getServer()->getCommandMap()->register($this->getName(), new TimeRanksCommand($this->api, $langManager));
    }

    protected function onDisable() : void {
		if (isset($this->api)) {
			$this->api->close();
		}
    }

	public function getApi() : TimeRanksApi {
		return $this->api;
	}
}

