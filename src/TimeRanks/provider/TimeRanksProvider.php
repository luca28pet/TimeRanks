<?php

namespace TimeRanks\provider;

interface TimeRanksProvider{

    public function isPlayerRegistered(string $name): bool;

    public function registerPlayer(string $name);

    public function getMinutes(string $name);

    public function setMinutes(string $name, int $minutes);

    public function close();

}