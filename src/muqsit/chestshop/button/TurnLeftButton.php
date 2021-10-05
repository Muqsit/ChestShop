<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\category\Category;
use muqsit\chestshop\Loader;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\Config;

class TurnLeftButton extends TurnButton{

	/** @var Item */
	private static $item;

	public static function init(Loader $loader, Config $config) : void{
		self::$item = ButtonUtils::itemFromConfig($config->get("turn_left"));
	}

	public function getItem() : Item{
		return clone self::$item;
	}

	public function navigate(Player $player, Category $category, int $current_page) : void{
		if(!$category->send($player, $current_page - 1)){
			$category->send($player, count($category->getPages()));
		}
	}
}