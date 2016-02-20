<?php

namespace TimeRanks\provider;

interface TimeRanksProvider{

    public function isPlayerRegistered($name);

    public function registerPlayer($name);

    public function getMinutes($name);

    public function setMinutes($name, $minutes);

}