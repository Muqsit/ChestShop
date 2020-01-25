<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;

final class ButtonUtils{

	public static function itemFromConfig(array $config) : Item{
		["id" => $id, "damage" => $damage, "count" => $count, "name" => $name, "lore" => $lore] = $config;
		return ItemFactory::fromString($id . ":" . $damage)
			->setCount($count)
			->setCustomName(TextFormat::colorize($name))
			->setLore(array_map(TextFormat::class . "::colorize", $lore));
	}
}