<?php

namespace TimeRanks\provider;

interface TimeRanksProvider{

	public function isPlayerRegistered(string $name): bool;

	public function registerPlayer(string $name) : void;

	public function getMinutes(string $name) : int;

	public function setMinutes(string $name, int $minutes) : void;

	public function close() : void;

}