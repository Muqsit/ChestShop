<?php

declare(strict_types=1);

namespace muqsit\chestshop\category;

use InvalidArgumentException;
use muqsit\chestshop\Loader;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

final class CategoryConfig{

	public const BUTTON = "button";
	public const TITLE = "title";
	public const ITEM_BUTTON_NAME = "item-button.name";
	public const ITEM_BUTTON_LORE_TYPE = "item-button.lore.type";
	public const ITEM_BUTTON_LORE_VALUE = "item-button.lore.value";
	public const BACK_TO_CATEGORIES = "send-back-to-categories";
	public const PURCHASE_MESSAGE = "purchase-message";
	public const NOT_ENOUGH_MONEY_MESSAGE = "not-enough-money-message";

	/** @var mixed[] */
	private static array $properties = [];

	public static function init(Loader $loader) : void{
		$loader->saveResource("category.yml");

		$config = new Config($loader->getDataFolder() . "category.yml");
		self::setStringList(self::BUTTON, $config->getNested(self::BUTTON));
		self::setString(self::TITLE, $config->getNested(self::TITLE));
		self::setString(self::ITEM_BUTTON_NAME, $config->getNested(self::ITEM_BUTTON_NAME));
		self::setString(self::ITEM_BUTTON_LORE_TYPE, $config->getNested(self::ITEM_BUTTON_LORE_TYPE));
		self::setStringList(self::ITEM_BUTTON_LORE_VALUE, $config->getNested(self::ITEM_BUTTON_LORE_VALUE));
		self::setBool(self::BACK_TO_CATEGORIES, $config->getNested(self::BACK_TO_CATEGORIES));
		self::setString(self::PURCHASE_MESSAGE, $config->getNested(self::PURCHASE_MESSAGE));
		self::setString(self::NOT_ENOUGH_MONEY_MESSAGE, $config->getNested(self::NOT_ENOUGH_MONEY_MESSAGE));
	}

	public static function getString(string $property) : string{
		return self::$properties[$property];
	}

	private static function setString(string $property, mixed $value) : void{
		if(!is_string($value)){
			throw new InvalidArgumentException("Invalid value for property {$property} in category.yml");
		}

		self::$properties[$property] = TextFormat::colorize($value);
	}

	public static function getBool(string $property) : bool{
		return self::$properties[$property];
	}

	private static function setBool(string $property, mixed $value) : void{
		if(!is_bool($value)){
			throw new InvalidArgumentException("Invalid value for property {$property} in category.yml");
		}

		self::$properties[$property] = $value;
	}

	/**
	 * @param string $property
	 * @return string[]
	 */
	public static function getStringList(string $property) : array{
		return self::$properties[$property];
	}

	private static function setStringList(string $property, mixed $value) : void{
		if(!is_array($value)){
			throw new InvalidArgumentException("Invalid value for property {$property} in category.yml");
		}

		self::$properties[$property] = array_map(TextFormat::class . "::colorize", $value);
	}
}