<?php

declare(strict_types=1);

namespace muqsit\chestshop\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;

final class ItemSerializer{

	/** @var BigEndianNBTStream */
	private static $serializer;

	public static function init() : void{
		self::$serializer = new BigEndianNBTStream();
	}

	public static function serialize(Item $item) : string{
		return self::$serializer->writeCompressed($item->nbtSerialize());
	}

	public static function unserialize(string $string) : Item{
		/** @noinspection PhpParamsInspection */
		return Item::nbtDeserialize(self::$serializer->readCompressed($string));
	}
}