<?php
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

