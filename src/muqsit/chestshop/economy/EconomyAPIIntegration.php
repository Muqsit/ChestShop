<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

final class EconomyAPIIntegration implements EconomyIntegration{

	/** @var EconomyAPI */
	private $plugin;

	public function __construct(){
		$this->plugin = EconomyAPI::getInstance();
	}

	public function init(array $config) : void{
	}

	public function getMoney(Player $player) : float{
		$money = $this->plugin->myMoney($player);
		assert(is_float($money));
		return $money;
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