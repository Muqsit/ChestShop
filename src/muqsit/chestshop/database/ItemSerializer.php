<?php

declare(strict_types=1);

namespace muqsit\chestshop\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use RuntimeException;

final class ItemSerializer{

	/** @var BigEndianNBTStream */
	private static $serializer;

	public static function init() : void{
		self::$serializer = new BigEndianNBTStream();
	}

	public static function serialize(Item $item) : string{
		$result = self::$serializer->writeCompressed($item->nbtSerialize());
		if($result === false){
			/** @noinspection PhpUnhandledExceptionInspection */
			throw new RuntimeException("Failed to serialize item " . json_encode($item, JSON_THROW_ON_ERROR));
		}

		return $result;
	}

	public static function unserialize(string $string) : Item{
		$tag = self::$serializer->readCompressed($string);
		assert($tag instanceof CompoundTag);
		return Item::nbtDeserialize($tag);
	}
}