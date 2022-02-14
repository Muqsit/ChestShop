<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\category\Category;
use muqsit\chestshop\ChestShop;
use muqsit\chestshop\Loader;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class CategoriesButton extends Button implements CategoryNavigationButton{

	private static Item $item;
	private static ChestShop $shop;

	public static function init(Loader $loader, Config $config) : void{
		self::$shop = $loader->getChestShop();
		self::$item = ButtonUtils::itemFromConfig($config->get("categories"));
	}

	public static function from(Item $item, CompoundTag $nbt) : self{
		return new self();
	}

	public function getItem() : Item{
		return clone self::$item;
	}

	public function navigate(Player $player, Category $category, int $current_page) : void{
		self::$shop->send($player);
	}
}