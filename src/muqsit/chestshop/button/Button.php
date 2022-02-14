<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\Loader;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Config;

abstract class Button{

	public static function init(Loader $loader, Config $config) : void{
	}

	/**
	 * @param Item $item
	 * @param CompoundTag $nbt
	 * @return static
	 */
	abstract public static function from(Item $item, CompoundTag $nbt);

	abstract public function getItem() : Item;

	public function getNamedTag() : CompoundTag{
		return new CompoundTag();
	}
}