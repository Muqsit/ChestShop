<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use Closure;
use muqsit\chestshop\util\PlayerIdentity;

interface EconomyIntegration{

	/**
	 * @param array $config
	 *
	 * @phpstan-param array<string, mixed> $config
	 */
	public function init(array $config) : void;

	/**
	 * Returns how much money the player has.
	 *
	 * @param PlayerIdentity $player
	 * @param Closure $callback
	 *
	 * @phpstan-param Closure(float) : void $callback
	 */
	public function getMoney(PlayerIdentity $player, Closure $callback) : void;

	/**
	 * Adds a given amount of money to the player.
	 *
	 * @param PlayerIdentity $player
	 * @param float $money
	 */
	public function addMoney(PlayerIdentity $player, float $money) : void;

	/**
	 * Removes a given amount of money from the player.
	 *
	 * @param PlayerIdentity $player
	 * @param Closure $callback
	 *
	 * @phpstan-param Closure(bool) : void $callback
	 */
	public function removeMoney(PlayerIdentity $player, float $money, Closure $callback) : void;

	/**
	 * Formats money.
	 *
	 * @param float $money
	 * @return string
	 */
	public function formatMoney(float $money) : string;
}