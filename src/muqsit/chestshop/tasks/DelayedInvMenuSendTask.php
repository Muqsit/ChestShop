<?php
namespace muqsit\chestshop\tasks;

use muqsit\chestshop\ChestShop;
use muqsit\invmenu\InvMenu;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class DelayedInvMenuSendTask extends PluginTask{

	/** @var Player */
	private $player;

	/** @var InvMenu */
	private $menu;

	public function __construct(ChestShop $plugin, Player $player, InvMenu $menu){
		parent::__construct($plugin);
		$this->player = $player;
		$this->menu = $menu;
	}

	public function onRun(int $tick) : void{
		if($this->player->isAlive() && $this->player->isConnected()){
			$this->menu->send($this->player);
		}
	}
}