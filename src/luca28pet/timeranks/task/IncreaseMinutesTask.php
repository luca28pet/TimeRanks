<?php
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

