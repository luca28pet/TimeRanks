<?php

namespace TimeRanks\provider;

use TimeRanks\TimeRanks;

class YamlProvider implements TimeRanksProvider{

	/** @var TimeRanks */
	private $tr;
	/** @var array */
	private $data;

	public function __construct(TimeRanks $tr){
		$this->tr = $tr;
		if(!file_exists($this->tr->getDataFolder() . 'timeranks.yml')){
			yaml_emit_file($this->tr->getDataFolder() . 'timeranks.yml', []);
		}
		$this->data = yaml_parse_file($this->tr->getDataFolder() . 'timeranks.yml');
	}

	public function isPlayerRegistered(string $name) : bool{
		return isset($this->data[strtolower($name)]);
	}

	public function registerPlayer(string $name) : void{
		$this->data[strtolower($name)] = 0;
	}

	public function getMinutes(string $name) : int{
		return $this->data[strtolower($name)] ?? -1;
	}

	public function setMinutes(string $name, int $minutes) : void{
		$this->data[strtolower($name)] = $minutes;
	}

	public function close() : void{
		yaml_emit_file($this->tr->getDataFolder() . 'timeranks.yml', $this->data);
	}

}