<?php

namespace TimeRanks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener{

	/** @var TimeRanks */
	private $tr;

	public function __construct(TimeRanks $tr){
		$this->tr = $tr;
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		if(!$this->tr->getProvider()->isPlayerRegistered($name = $event->getPlayer()->getName())){
			$this->tr->getProvider()->registerPlayer($name);
			$this->tr->getDefaultRank()->onRankUp($event->getPlayer());
			return;
		}
		//Check pending rank-up
		foreach($this->tr->getRanks() as $rank){
			if($rank->isPending($name)){
				$rank->onRankUp($event->getPlayer(), true);
				return;
			}
		}
	}

}