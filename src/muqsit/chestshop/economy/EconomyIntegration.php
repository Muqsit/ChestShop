<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use pocketmine\Player;

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
	 * @param Player $player
	 * @return float
	 */
	public function getMoney(Player $player) : float;

	/**
	 * Adds a given amount of money to the player.
	 *
	 * @param Player $player
	 * @param float $money
	 */
	public function addMoney(Player $player, float $money) : void;

	/**
	 * Removes a given amount of money from the player.
	 *
	 * @param Player $player
	 * @param float $money
	 */
	public function removeMoney(Player $player, float $money) : void;

	/**
	 * Formats money.
	 *
	 * @param float $money
	 * @return string
	 */
	public function formatMoney(float $money) : string;
}