<?php

namespace luca28pet\timeranks;

final class Rank{
	public function __construct(
		private string $name,
		private int $minutes,
		private bool $isDefault,
		private string $msg,
		/** @var string[] */
		private array $commands,
	) {
		if ($this->minutes < 0) {
			throw new \InvalidArgumentException('Rank '.$this->name.' constructed with negative minutes');
		}
	}

    public function getName() : string {
        return $this->name;
    }

    public function getMinutes() : int {
        return $this->minutes;
    }

	public function isDefault() : bool {
		return $this->isDefault;
	}

	public function getMessage() : string {
		return $this->msg;
	}

	/**
	 * @return string[]
	 */
	public function getCommands() : array {
		return $this->commands;
	}
}

