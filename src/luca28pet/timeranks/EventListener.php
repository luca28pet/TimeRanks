<?php
declare(strict_types=1);

namespace luca28pet\timeranks;

use pocketmine\scheduler\TaskScheduler;
use Logger;
use luca28pet\timeranks\task\IncreaseMinutesTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;

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
		$this->scheduler->scheduleDelayedRepeatingTask(new IncreaseMinutesTask($this->api, $ev->getPlayer(), $this->logger), 1200, 1200);
	}
}

