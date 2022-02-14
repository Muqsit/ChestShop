<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\category\CategoryConfig;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class CategoryButton extends Button{

	private const TAG_CATEGORY = "Category";
	private const TAG_ITEM = "Item";

	public static function from(Item $item, CompoundTag $nbt) : CategoryButton{
		return new CategoryButton($nbt->getString(self::TAG_CATEGORY), Item::nbtDeserialize($nbt->getCompoundTag(self::TAG_ITEM)));
	}

	public function __construct(
		private string $category,
		private Item $item
	){}

	public function getCategory() : string{
		return $this->category;
	}

	public function getItem() : Item{
		$display = CategoryConfig::getStringList(CategoryConfig::BUTTON);
		return (clone $this->item)
			->setCustomName(str_replace("{CATEGORY}", $this->category, array_shift($display)))
			->setLore(str_replace("{CATEGORY}", $this->category, $display));
	}

	public function getNamedTag() : CompoundTag{
		return parent::getNamedTag()
			->setString(self::TAG_CATEGORY, $this->category)
			->setTag(self::TAG_ITEM, $this->item->nbtSerialize());
	}
}