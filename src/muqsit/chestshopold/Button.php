<?php

declare(strict_types=1);

namespace muqsit\chestshop;

use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

final class Button{

	public const TURN_LEFT = 0;
	public const TURN_RIGHT = 1;
	public const CATEGORIES = 2;

	/** @var array */
	private static $options;

	public static function setOptions(array $options) : void{
		self::$options = $options;
		array_walk_recursive(self::$options, static function(&$value) : void{
			if(is_string($value)){
				$value = TextFormat::colorize($value);
			}
		});
	}

	public static function getOptions() : array{
		$options = self::$options;
		array_walk_recursive($options, static function(&$value) : void{
			if(is_string($value)){
				$value = str_replace(TextFormat::ESCAPE, "&", $value);
			}
		});

		return $options;
	}

	public static function get(int $turn, ...$args) : Item{
		switch($turn){
			case Button::TURN_LEFT:
				$item = Button::itemFromData(self::$options["turn_left"]);
				$item->setNamedTagEntry(new ByteTag("Button", Button::TURN_LEFT));
				$item->setNamedTagEntry(new StringTag("Category", $args[0]));
				return $item;
			case Button::TURN_RIGHT:
				$item = Button::itemFromData(self::$options["turn_right"]);
				$item->setNamedTagEntry(new ByteTag("Button", Button::TURN_RIGHT));
				$item->setNamedTagEntry(new StringTag("Category", $args[0]));
				return $item;
			case Button::CATEGORIES:
				$item = Button::itemFromData(self::$options["categories"]);
				$item->setNamedTagEntry(new ByteTag("Button", Button::CATEGORIES));
				return $item;
		}

		throw new \InvalidArgumentException("Invalid button type '$turn'");
	}

	public static function itemFromData(array $data) : Item{
		["id" => $id, "damage" => $damage, "count" => $count, "name" => $name, "lore" => $lore] = $data;
		return Item::fromString($id . ":" . $damage)->setCount($count)
			->setCustomName($name)
			->setLore($lore);
	}
}