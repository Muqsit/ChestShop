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
	private static array $buttons = [];

	/**
	 * @var string[]
	 * @phpstan-var array<class-string<Button>, string>
	 */
	private static array $identifiers = [];

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
		 * @phpstan-var class-string<Button> $class
		 */
		self::$buttons[$identifier] = $class;
		self::$identifiers[$class] = $identifier;
		$class::init($loader, $config);
	}

	private static function toItem(Button $button) : Item{
		$item = $button->getItem();
		$item->getNamedTag()->setTag(self::TAG_BUTTON, CompoundTag::create()
			->setString(self::TAG_ID, self::$identifiers[$button::class])
			->setTag(self::TAG_DATA, $button->getNamedTag())
		);
		return $item;
	}

	public static function get(string $identifier, mixed ...$args) : Item{
		return self::toItem(new self::$buttons[$identifier](...$args));
	}

	public static function fromItem(Item $item) : ?Button{
		$tag = $item->getNamedTag()->getCompoundTag(self::TAG_BUTTON);
		return $tag !== null ? self::$buttons[$tag->getString(self::TAG_ID)]::from($item, $tag->getCompoundTag(self::TAG_DATA)) : null;
	}
}