<?php
declare(strict_types=1);

namespace luca28pet\timeranks\command;

use pocketmine\command\Command;
use luca28pet\timeranks\TimeRanksApi;
use pocketmine\command\CommandSender;
use luca28pet\timeranks\lang\LangManager;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class TimeRanksCommand extends Command {
	public function __construct(
		private TimeRanksApi $api,
		private LangManager $langManager
	) {
		parent::__construct(
			'timeranks',
			$this->langManager->getTranslation('tr-command-desc', []),
			$this->langManager->getTranslation('tr-command-usage', []),
			['tr']
		);
		$this->setPermission('timeranks.command.self');
		$this->setPermissionMessage($this->langManager->getTranslation('tr-command-no-perm', []));
	}

	public function execute(CommandSender $sn, string $lbl, array $args) : void {
		$argc = count($args);
		if ($argc !== 1 && $argc !== 2) {
			throw new InvalidCommandSyntaxException();
		}
		switch ($args[0]) {
		case 'check':
			if (isset($args[1])) {
				$target = $args[1];
			} else {
				$target = $sn->getName();
			}
			if ($target !== $sn->getName() && !$sn->hasPermission('timeranks.command.others')) {
				$sn->sendMessage($this->langManager->getTranslation('tr-command-no-perm', []));
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
							$sn->sendMessage($this->langManager->getTranslation('tr-command-other', [
								'player' => $target,
								'minutes' => (string) $minutes,
								'rank' => $this->api->getRankFromMinutes($minutes)->getName()
							]));
						} else {
							$sn->sendMessage($this->langManager->getTranslation('tr-command-self', [
								'minutes' => (string) $minutes,
								'rank' => $this->api->getRankFromMinutes($minutes)->getName()
							]));
						}
					} else {
						$sn->sendMessage($this->langManager->getTranslation('tr-command-player-not-found', [
							'player' => $target
						]));
					}
				},
				function(SqlError $err) use ($sn) : void {
					if (!($sn instanceof Player) || $sn->isConnected()) {
						$sn->sendMessage($this->langManager->getTranslation('tr-command-fail', []));
					}
				}
			);
			break;
		default:
			throw new InvalidCommandSyntaxException();
		}
	}
}

