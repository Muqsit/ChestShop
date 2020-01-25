<?php

declare(strict_types=1);

namespace muqsit\chestshop\economy;

use muqsit\chestshop\Loader;
use pocketmine\utils\Utils;

final class EconomyManager{

	/** @var string[] */
	private static $integrations = [];

	/** @var EconomyIntegration */
	private static $integrated;

	public static function init(Loader $loader) : void{
		self::registerDefaults();

		$plugin = $loader->getConfig()->get("economy-plugin", "EconomyAPI");
		if(!isset(self::$integrations[$plugin])){
			throw new \InvalidArgumentException($loader->getName() . " does not support the economy plugin " . $plugin);
		}

		self::$integrated = new self::$integrations[$plugin]();
	}

	private static function registerDefaults() : void{
		self::register("EconomyAPI", EconomyAPIIntegration::class);
	}

	private static function register(string $plugin, string $class) : void{
		Utils::testValidInstance($class, EconomyIntegration::class);
		self::$integrations[$plugin] = $class;
	}

	public static function get() : EconomyIntegration{
		return self::$integrated;
	}
}