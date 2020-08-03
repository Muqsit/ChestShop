<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use InvalidArgumentException;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;

final class ButtonUtils{

	/**
	 * @param array<string, mixed> $config
	 * @return Item
	 */
	public static function itemFromConfig(array $config) : Item{
		["id" => $id, "damage" => $damage, "count" => $count, "name" => $name, "lore" => $lore] = $config;

		$item = ItemFactory::fromString("{$id}:{$damage}");
		if(!($item instanceof Item)){
			throw new InvalidArgumentException("Invalid item string supplied: {$id}:{$damage}");
		}

		return $item
			->setCount($count)
			->setCustomName(TextFormat::colorize($name))
			->setLore(array_map(TextFormat::class . "::colorize", $lore));
	}
}