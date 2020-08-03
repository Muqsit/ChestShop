<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use pocketmine\Player;
use pocketmine\Server;
use twisted\multieconomy\Currency;
use twisted\multieconomy\MultiEconomy;

final class MultiEconomyIntegration implements EconomyIntegration{

	/** @var Currency */
	private $currency;

	public function init(array $config) : void{
		/** @var MultiEconomy $plugin */
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("MultiEconomy");
		$this->currency = $plugin->getCurrency($config["currency"]);
		if($this->currency === null){
			throw new \InvalidArgumentException("Invalid currency {$config["currency"]}");
		}
	}

	public function getMoney(Player $player) : float{
		return $this->currency->getBalance($player->getName());
	}

	public function addMoney(Player $player, float $money) : void{
		$this->currency->addToBalance($player->getName(), $money);
	}

	public function removeMoney(Player $player, float $money) : void{
		$this->currency->removeFromBalance($player->getName(), $money);
	}

	public function formatMoney(float $money) : string{
		return $this->currency->formatBalance($money);
	}
}