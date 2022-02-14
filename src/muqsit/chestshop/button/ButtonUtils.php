<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use InvalidArgumentException;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\TextFormat;

final class ButtonUtils{

	/**
	 * @param array<string, mixed> $config
	 * @return Item
	 */
	public static function itemFromConfig(array $config) : Item{
		["id" => $id, "damage" => $damage, "count" => $count, "name" => $name, "lore" => $lore] = $config;

		$identifier = "{$id}:{$damage}"; // TODO: Support raw identifiers
		try{
			$item = StringToItemParser::getInstance()->parse($identifier) ?? LegacyStringToItemParser::getInstance()->parse($identifier);
		}catch(LegacyStringToItemParserException $e){
			throw new InvalidArgumentException("Invalid item string supplied: {$id}:{$damage}", $e->getCode(), $e);
		}

		return $item
			->setCount($count)
			->setCustomName(TextFormat::colorize($name))
			->setLore(array_map(TextFormat::class . "::colorize", $lore));
	}
}