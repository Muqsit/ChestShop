<?php
namespace muqsit\chestshop;

use pocketmine\item\Item;
use pocketmine\nbt\tag\{ByteTag, StringTag};
use pocketmine\utils\TextFormat as TF;

class Button{

	const TURN_LEFT = 0;
	const TURN_RIGHT = 1;
	const CATEGORIES = 2;

	private static $options = [
		"turn_left" => [
			"id" => Item::PAPER,
			"damage" => 0,
			"count" => 1,
			"name" => TF::RESET.TF::GOLD.TF::BOLD."<- Turn Left",
			"lore" => [
				TF::RESET.TF::GRAY."Turn to left page."
			]
		],
		"turn_right" => [
			"id" => Item::PAPER,
			"damage" => 0,
			"count" => 1,
			"name" => TF::RESET.TF::GOLD.TF::BOLD."Turn Right ->",
			"lore" => [
				TF::RESET.TF::GRAY."Turn to right page."
			]
		],
		"categories" => [
			"id" => Item::CHEST,
			"damage" => 0,
			"count" => 1,
			"name" => TF::RESET.TF::YELLOW.TF::BOLD."View Categories",
			"lore" => [
				TF::RESET.TF::GRAY."Back to categories."
			]
		],
	];

	public static function setOptions(array $options) : void{
		self::$options = $options;
		array_walk_recursive(self::$options, function(&$value) : void{
			if(is_string($value)){
				$value = TF::colorize($value);
			}
		});
	}

	public static function getOptions() : array{
		$options = self::$options;
		array_walk_recursive($options, function(&$value) : void{
			if(is_string($value)){
				$value = str_replace(TF::ESCAPE, "&", $value);
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
		[
			"id" => $id,
			"damage" => $damage,
			"count" => $count,
			"name" => $name,
			"lore" => $lore
		] = $data;

		$item = Item::get($id, $damage, $count);
		$item->setCustomName($name);
		$item->setLore($lore);

		return $item;
	}
}