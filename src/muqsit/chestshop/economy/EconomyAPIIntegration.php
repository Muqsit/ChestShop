<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use Closure;
use muqsit\chestshop\util\PlayerIdentity;
use onebone\economyapi\EconomyAPI;

final class EconomyAPIIntegration implements EconomyIntegration{

	private EconomyAPI $plugin;

	public function __construct(){
		$this->plugin = EconomyAPI::getInstance();
	}

	public function init(array $config) : void{
	}

	public function getMoney(PlayerIdentity $player, Closure $callback) : void{
		$money = $this->plugin->myMoney($player->getGamertag());
		assert(is_float($money));
		$callback($money);
	}

	public function addMoney(PlayerIdentity $player, float $money) : void{
		$this->plugin->addMoney($player->getGamertag(), $money);
	}

	public function removeMoney(PlayerIdentity $player, float $money, Closure $callback) : void{
		$callback($this->plugin->reduceMoney($player->getGamertag(), $money) === EconomyAPI::RET_SUCCESS);
	}

	public function formatMoney(float $money) : string{
		return $this->plugin->getMonetaryUnit() . number_format($money);
	}
}