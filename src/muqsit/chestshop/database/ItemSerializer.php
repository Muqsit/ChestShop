<?php

declare(strict_types=1);

namespace muqsit\chestshop\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use RuntimeException;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

final class ItemSerializer{

	private static BigEndianNbtSerializer $serializer;

	public static function init() : void{
		self::$serializer = new BigEndianNbtSerializer();
	}

	public static function serialize(Item $item) : string{
		$result = zlib_encode(self::$serializer->write(new TreeRoot($item->nbtSerialize())), ZLIB_ENCODING_GZIP);
		if($result === false){
			/** @noinspection PhpUnhandledExceptionInspection */
			throw new RuntimeException("Failed to serialize item " . json_encode($item, JSON_THROW_ON_ERROR));
		}

		return $result;
	}

	public static function unserialize(string $string) : Item{
		return Item::nbtDeserialize(self::$serializer->read(zlib_decode($string))->mustGetCompoundTag());
	}
}