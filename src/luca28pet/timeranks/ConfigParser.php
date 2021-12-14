<?php
declare(strict_types=1);

namespace luca28pet\timeranks;

use AdvancedKits\kit\Kit;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Armor;

/**
 * @internal
 */
final class ConfigParser {
	private function __construct() {
	}

	public static function getRank(mixed $node) : Rank {
		if (!is_array($node)) {
			throw new \InvalidArgumentException('Rank node must be an array, '.gettype($node).' found');
		}

		if (!isset($node['name'])) {
			throw new \InvalidArgumentException('Rank node must have a \'name\' field');
		}
		try {
			$name = self::getString($node['name']);
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException('Rank node: error loading name', previous: $e);
		}

		$default = false;
		if (isset($node['default'])) {
			try {
				$default = self::getBool($node['default']);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('Rank node: error loading default', previous: $e);
			}
		}

		if (!$default) {
			if (!isset($node['minutes'])) {
				throw new \InvalidArgumentException('Rank node must have a \'minutes\' field');
			}
			try {
				$minutes = self::getInt($node['minutes']);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('Rank node: error loading minutes', previous: $e);
			}
		}

		$message = '';
		if (isset($node['message'])) {
			try {
				$message = self::getString($node['message']);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('Rank node: error loading message', previous: $e);
			}
		}

		$commands = [];
		if (isset($node['commands'])) {
			try {
				$commands = self::getList($node['commands'], [self::class, 'getString']);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('Rank node: error loading commands', previous: $e);
			}
		}

		return new Rank($name, $default ? 0 : $minutes, $default, $message, $commands);
	}

	public static function getString(mixed $node) : string {
		if (!is_string($node)) {
			throw new \InvalidArgumentException('String node contains wrong type '.gettype($node));
		}
		return $node;
	}

	public static function getInt(mixed $node) : int {
		if (!is_int($node)) {
			throw new \InvalidArgumentException('Int node contains wrong type '.gettype($node));
		}
		return $node;
	}

	public static function getBool(mixed $node) : bool {
		if (!is_bool($node)) {
			throw new \InvalidArgumentException('Bool node contains wrong typw '.gettype($node));
		}
		return $node;
	}

	/**
	 * @template T
	 * @param callable(mixed) : T $getter a function that returns T or throws an InvalidArgumentException if not possible
	 * @return list<T>
	 */
	public static function getList(mixed $node, callable $getter) : array {
		if (!is_array($node)) {
			throw new \InvalidArgumentException('List node must be of type array, '.gettype($node).' found');
		}
		$list = [];
		foreach ($node as $n) {
			try {
				$list[] = $getter($n);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('List node: error loading element', previous: $e);
			}
		}
		return $list;
	}
}

