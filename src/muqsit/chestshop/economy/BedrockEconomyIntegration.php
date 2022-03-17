<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use InvalidArgumentException;
use muqsit\chestshop\util\PlayerIdentity;
use pocketmine\Server;

final class BedrockEconomyIntegration implements EconomyIntegration{

	private BedrockEconomy $plugin;

	public function __construct(){
		/** @var BedrockEconomy $plugin */
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy") ?? throw new InvalidArgumentException("BedrockEconomy plugin was not found");
		$this->plugin = $plugin;
	}

	public function init(array $config) : void{
	}

	public function getMoney(PlayerIdentity $player, Closure $callback) : void{
		BedrockEconomyAPI::getInstance()->getPlayerBalance($player->getGamertag(), ClosureContext::create(static function(?int $balance) use($callback) : void{
			$callback($balance ?? 0);
		}));
	}

	public function addMoney(PlayerIdentity $player, float $money) : void{
		BedrockEconomyAPI::getInstance()->addToPlayerBalance($player->getGamertag(), (int) ceil($money));
	}

	public function removeMoney(PlayerIdentity $player, float $money, Closure $callback) : void{
		$money_int = (int) ceil($money);
		BedrockEconomyAPI::getInstance()->subtractFromPlayerBalance($player->getGamertag(), $money_int, ClosureContext::create(static function(bool $success) use($callback) : void{
			$callback($success);
		}));
	}

	public function formatMoney(float $money) : string{
		return $this->plugin->getCurrencyManager()->getSymbol() . number_format($money);
	}
}