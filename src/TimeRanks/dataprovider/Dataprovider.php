<?php
namespace TimeRanks\dataprovider;

abstract class Dataprovider {
	private $plugin;
	
	abstract public function __construct(\TimeRanks\TimeRanks $plugin);
	
	abstract public function register($playerName, $minutes = 0);
	
	abstract public function getMinutes($playerName);
	
	abstract public function setMinutes($playerName, $minutes);
	
	abstract public function register_multi($data);
	
	abstract public function start_transaction();
	
	abstract public function commit_transaction();

}