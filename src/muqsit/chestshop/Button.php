<?php
namespace muqsit\chestshop;

use pocketmine\item\Item;
use pocketmine\nbt\tag\{ByteTag, StringTag};
use pocketmine\utils\TextFormat as TF;

class Button{

	const TURN_LEFT = 0;
	const TURN_RIGHT = 1;
	const CATEGORIES = 2;

	public static function get(int $turn, ...$args) : Item{
		switch($turn){
			case Button::TURN_LEFT:
				$item = Item::get(Item::PAPER);
				$item->setCustomName(TF::RESET.TF::GOLD.TF::BOLD.'<- Turn Left');
				$item->setLore([TF::GRAY.'Turn to left page.']);
				$item->setNamedTagEntry(new ByteTag("Button", Button::TURN_LEFT));
				$item->setNamedTagEntry(new StringTag("Category", $args[0]));
				return $item;
			case Button::TURN_RIGHT:
				$item = Item::get(Item::PAPER);
				$item->setCustomName(TF::RESET.TF::GOLD.TF::BOLD.'Turn Right ->');
				$item->setLore([TF::GRAY.'Turn to right page.']);
				$item->setNamedTagEntry(new ByteTag("Button", Button::TURN_RIGHT));
				$item->setNamedTagEntry(new StringTag("Category", $args[0]));
				return $item;
			case Button::CATEGORIES:
				$item = Item::get(Item::CHEST);
				$item->setCustomName(TF::RESET.TF::YELLOW.TF::BOLD.'Categories');
				$item->setLore([TF::GRAY.'Back to categories.']);
				$item->setNamedTagEntry(new ByteTag("Button", Button::CATEGORIES));
				return $item;
		}

		throw new \InvalidArgumentException("Invalid button type '$turn'");
	}
}