<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use Closure;
use muqsit\chestshop\util\PlayerIdentity;
use pocketmine\player\Player;
use pocketmine\Server;
use twisted\multieconomy\Currency;
use twisted\multieconomy\MultiEconomy;

final class MultiEconomyIntegration implements EconomyIntegration{

	private Currency $currency;

	public function init(array $config) : void{
		/** @var MultiEconomy $plugin */
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("MultiEconomy");
		$this->currency = $plugin->getCurrency($config["currency"]);
		if($this->currency === null){
			throw new \InvalidArgumentException("Invalid currency {$config["currency"]}");
		}
	}

	public function getMoney(PlayerIdentity $player, Closure $callback) : void{
		$callback($this->currency->getBalance($player->getGamertag()));
	}

	public function addMoney(PlayerIdentity $player, float $money) : void{
		$this->currency->addToBalance($player->getGamertag(), $money);
	}

	public function removeMoney(PlayerIdentity $player, float $money, Closure $callback) : void{
		$this->currency->removeFromBalance($player->getGamertag(), $money);
		$callback(true);
	}

	public function formatMoney(float $money) : string{
		return $this->currency->formatBalance($money);
	}
}