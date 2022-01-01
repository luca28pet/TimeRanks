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

use luca28pet\configparser\ConfigNode;
use luca28pet\configparser\IncompatibleConfigNodeTypeException;

/**
 * @internal
 */
final class TimeRanksConfigNode extends ConfigNode {
	public function toRank() : Rank {
		try {
			$name = $this->getMapEntry($field = 'name')?->toString();
			if ($name === null) {
				throw new IncompatibleConfigNodeTypeException('Rank node: must have a name field');
			}
			$default = $this->getMapEntry($field = 'default')?->toBool();
			$minutes = $this->getMapEntry($field = 'minutes')?->toInt();
			$message = $this->getMapEntry($field = 'message')?->toString();
			$commands = array_map(fn(ConfigNode $n) => $n->toString(), $this->getMapEntry($field = 'commands')?->toList() ?? []);
		} catch (IncompatibleConfigNodeTypeException $e) {
			throw new IncompatibleConfigNodeTypeException('Rank node: error loading '.$field, previous: $e);
		}
		if ($default !== true && $minutes === null) {
			throw new IncompatibleConfigNodeTypeException('Rank node: a non default rank must have a minute field');
		}
		return new Rank($name, $default === true ? 0 : $minutes, $default ?? false, $message ?? '', $commands);
	}
}

