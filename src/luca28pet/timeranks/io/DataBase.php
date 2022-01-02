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

namespace luca28pet\timeranks\io;

use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use luca28pet\timeranks\util\InvalidPlayerNameException;
use luca28pet\timeranks\util\Utils;

/**
 * @internal
 */
final class DataBase {
	/**
	 * @throws SqlError if table initialisation failed
	 */
	public function __construct(
		private DataConnector $connector
	) {
		$this->connector->executeGeneric('timeranks.create_tables', [], function() : void {}, function(SqlError $err) : void {
			throw $err;
		});
		$this->connector->waitAll();
	}

	/**
	 * @param string $name UTF-8 encoded string with a maximum length of 16 characters
	 * @param callable(?int $minutes) : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 * @throws InvalidPlayerNameException if $name does not satisfy preconditions
	 */
	public function getPlayerMinutes(string $name, callable $onCompletion, callable $onError) : void {
		Utils::validatePlayerName($name);
		$this->connector->executeSelect(
			'timeranks.get_player',
			['player' => mb_strtolower($name, 'UTF-8')],
			function(array $rows, array $ci) use ($onCompletion) : void {
				if (count($rows) === 0) {
					$onCompletion(null);
				} else {
					$row = $rows[array_key_first($rows)];
					$onCompletion($row['minutes']);
				}
			},
			$onError
		);
	}

	/**
	 * @param string $name UTF-8 encoded string with a maximum length of 16 characters
	 * @param int $minutes a non negative integer
	 * @param callable() : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 * @throws InvalidPlayerNameException if $name does not satisfy preconditions
	 * @throws \InvalidArgumentException if $minutes is negative
	 */
	public function setPlayerMinutes(string $name, int $minutes, callable $onCompletion, callable $onError) : void {
		Utils::validatePlayerName($name);
		if ($minutes < 0) {
			throw new \InvalidArgumentException('Invalid Minutes');
		}
		$this->connector->executeInsert(
			$query = 'timeranks.set_player_minutes',
			$args = ['player' => mb_strtolower($name, 'UTF-8'), 'minutes' => $minutes],
			function(int $insertId, int $affectedRows) use ($query, $args, $onCompletion, $onError) : void {
				if ($affectedRows !== 0) {
					$onCompletion();
				} else {
					$onError(new SqlError(SqlError::STAGE_EXECUTE, 'No affected rows', $query, $args));
				}
			},
			$onError
		);
	}

	/**
	 * @param string $name UTF-8 encoded string with a maximum length of 16 characters
	 * @param int $minutes a non negative integer
	 * @param callable() : void $onCompletion
	 * @param callable(SqlError $err) : void $onError
	 * @throws InvalidPlayerNameException if $name does not satisfy preconditions
	 * @throws \InvalidArgumentException if $minutes is negative
	 */
	public function incrementPlayerMinutes(string $name, int $minutes, callable $onCompletion, callable $onError) : void {
		Utils::validatePlayerName($name);
		if ($minutes < 0) {
			throw new \InvalidArgumentException('Invalid Minutes');
		}
		$this->connector->executeInsert(
			$query = 'timeranks.increment_player_minutes',
			$args = ['player' => mb_strtolower($name, 'UTF-8'), 'minutes' => $minutes],
			function(int $insertId, int $affectedRows) use ($query, $args, $onCompletion, $onError) : void {
				if ($affectedRows !== 0) {
					$onCompletion();
				} else {
					$onError(new SqlError(SqlError::STAGE_EXECUTE, 'No affected rows', $query, $args));
				}
			},
			$onError
		);
	}

	public function close() : void {
		$this->connector->waitAll();
		$this->connector->close();
	}
}

