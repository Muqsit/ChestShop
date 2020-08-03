<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\Loader;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;

final class ButtonFactory{

	private const TAG_BUTTON = "chestshop:button";
	private const TAG_ID = "id";
	private const TAG_DATA = "data";

	/**
	 * @var Button[]|string[]
	 * @phpstan-var array<string, class-string<Button>>
	 */
	private static $buttons = [];

	/**
	 * @var string[]
	 * @phpstan-var array<class-string<Button>, string>
	 */
	private static $identifiers = [];

	public static function init(Loader $loader) : void{
		$loader->saveResource("buttons.yml");

		$config = new Config($loader->getDataFolder() . "buttons.yml", Config::YAML);
		self::register($loader, $config, ButtonIds::CATEGORY, CategoryButton::class);
		self::register($loader, $config, ButtonIds::TURN_LEFT, TurnLeftButton::class);
		self::register($loader, $config, ButtonIds::TURN_RIGHT, TurnRightButton::class);
		self::register($loader, $config, ButtonIds::CATEGORIES, CategoriesButton::class);
	}

	/**
	 * @param Loader $loader
	 * @param Config $config
	 * @param string $identifier
	 * @param string $class
	 *
	 * @phpstan-param class-string<Button> $class
	 */
	public static function register(Loader $loader, Config $config, string $identifier, string $class) : void{
		Utils::testValidInstance($class, Button::class);
		/**
		 * @var Button|string $class
		 * @phpstan-var class-string<Button>
		 */

		self::$buttons[$identifier] = $class;
		self::$identifiers[$class] = $identifier;
		$class::init($loader, $config);
	}

	private static function toItem(Button $button) : Item{
		$item = $button->getItem();

		$tag = new CompoundTag(self::TAG_BUTTON);
		$tag->setString(self::TAG_ID, self::$identifiers[get_class($button)]);
		$tag->setTag($button->getNamedTag(self::TAG_DATA));
		$item->setNamedTagEntry($tag);

		return $item;
	}

	/**
	 * @param string $identifier
	 * @param mixed ...$args
	 * @return Item
	 */
	public static function get(string $identifier, ...$args) : Item{
		return self::toItem(new self::$buttons[$identifier](...$args));
	}

	public static function fromItem(Item $item) : ?Button{
		$tag = $item->getNamedTagEntry(self::TAG_BUTTON);
		/** @var CompoundTag|null $tag */
		return $tag !== null ? self::$buttons[$tag->getString(self::TAG_ID)]::from($item, $tag->getCompoundTag(self::TAG_DATA)) : null;
	}
}