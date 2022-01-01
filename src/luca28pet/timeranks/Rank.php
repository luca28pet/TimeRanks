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

