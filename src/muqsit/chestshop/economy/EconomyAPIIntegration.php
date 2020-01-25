<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

final class EconomyAPIIntegration implements EconomyIntegration{

	/** @var EconomyAPI */
	private $plugin;

	public function __construct(array $config){
		$this->plugin = EconomyAPI::getInstance();
	}

	public function getMoney(Player $player) : float{
		return $this->plugin->myMoney($player);
	}

	public function addMoney(Player $player, float $money) : void{
		$this->plugin->addMoney($player, $money);
	}

	public function removeMoney(Player $player, float $money) : void{
		$this->plugin->reduceMoney($player, $money);
	}

	public function formatMoney(float $money) : string{
		return $this->plugin->getMonetaryUnit() . number_format($money);
	}
}