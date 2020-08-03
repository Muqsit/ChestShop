<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

abstract class TurnButton extends Button implements CategoryNavigationButton{

	private const TAG_CATEGORY = "Category";

	final public static function from(Item $item, CompoundTag $nbt) : TurnButton{
		return new static($nbt->getString(self::TAG_CATEGORY));
	}

	/** @var string */
	private $category;

	final public function __construct(string $category){
		$this->category = $category;
	}

	public function getCategory() : string{
		return $this->category;
	}

	public function getNamedTag(string $name) : CompoundTag{
		$tag = parent::getNamedTag($name);
		$tag->setString(self::TAG_CATEGORY, $this->category);
		return $tag;
	}
}