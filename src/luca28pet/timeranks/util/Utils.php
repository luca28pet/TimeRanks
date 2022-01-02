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

namespace luca28pet\timeranks\util;

final class Utils {
	private function __construct() {
	}

	public static function isValidPlayerName(string $name) : bool {
		if (strlen($name) > 16 * 4) {
			return false;
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			return false;
		}
		if (mb_strlen($name, 'UTF-8') > 16) {
			return false;
		}
		return true;
	}

	public static function validatePlayerName(string $name) : void {
		if (strlen($name) > 16 * 4) {
			throw new InvalidPlayerNameException('Name is too long');
		}
		if (!mb_check_encoding($name, 'UTF-8')) {
			throw new InvalidPlayerNameException('Name is not UTF-8');
		}
		if (mb_strlen($name, 'UTF-8') > 16) {
			throw new InvalidPlayerNameException('Name is too long');
		}
	}
}

