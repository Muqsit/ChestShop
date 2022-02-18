<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use InvalidArgumentException;
use muqsit\chestshop\Loader;
use muqsit\chestshop\util\PlayerIdentity;
use pocketmine\Server;
use function assert;

final class BedrockEconomyIntegration implements EconomyIntegration{

	private BedrockEconomy $plugin;

	public function __construct(){
		/** @var BedrockEconomy|null $plugin */
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy");
		if($plugin === null){
			throw new InvalidArgumentException("BedrockEconomy plugin was not found");
		}

		$this->plugin = $plugin;
	}

	public function init(array $config) : void{
		if(($config["unsafe-warn"] ?? true) && count($this->plugin->getConnector()->getThreads()) > 1){
			/** @var Loader|null $chest_shop */
			$chest_shop = Server::getInstance()->getPluginManager()->getPlugin("ChestShop");
			assert($chest_shop !== null);
			$chest_shop->getLogger()->warning("{$this->plugin->getName()} is unsafe to use with > 1 database threads");
			$chest_shop->getLogger()->warning("To correct this issue, set 'threads' to 1 in {$this->plugin->getConfig()->getPath()}");
			$chest_shop->getLogger()->warning("To suppress this error, set 'unsafe-warn' to false in {$chest_shop->getConfig()->getPath()}");
		}
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
		/**
		 * SAFETY NOTICE
		 * This method executes two asynchronous processes simultaneously:
		 * 		getPlayerBalance(), subtractFromPlayerBalance()
		 *
		 * It is completely possible for a race condition to happen and an unexpected value
		 * be subtracted as a consequence.
		 * BedrockEconomy lets player balances go below 0, so directly invoking
		 * subtractFromPlayerBalance() is not possible.
		 */
		$money_int = (int) ceil($money);
		$this->getMoney($player, static function(float $balance) use($player, $money_int, $callback) : void{
			if($balance >= $money_int){
				BedrockEconomyAPI::getInstance()->subtractFromPlayerBalance($player->getGamertag(), $money_int, ClosureContext::create(static function(bool $success) use($callback) : void{
					$callback($success);
				}));
			}else{
				$callback(false);
			}
		});
	}

	public function formatMoney(float $money) : string{
		return $this->plugin->getCurrencyManager()->getSymbol() . number_format($money);
	}
}