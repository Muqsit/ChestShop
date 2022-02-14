<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

abstract class TurnButton extends Button implements CategoryNavigationButton{

	private const TAG_CATEGORY = "Category";

	final public static function from(Item $item, CompoundTag $nbt) : TurnButton{
		return new static($nbt->getString(self::TAG_CATEGORY));
	}

	final public function __construct(
		private string $category
	){}

	public function getCategory() : string{
		return $this->category;
	}

	public function getNamedTag() : CompoundTag{
		return parent::getNamedTag()->setString(self::TAG_CATEGORY, $this->category);
	}
}