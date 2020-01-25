<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\category\CategoryConfig;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class CategoryButton extends Button{

	private const TAG_CATEGORY = "Category";
	private const TAG_ITEM = "Item";

	public static function from(Item $item, CompoundTag $nbt) : CategoryButton{
		return new CategoryButton($nbt->getString(self::TAG_CATEGORY), Item::nbtDeserialize($nbt->getCompoundTag(self::TAG_ITEM)));
	}

	/** @var string */
	private $category;

	/** @var Item */
	private $item;

	public function __construct(string $category, Item $item){
		$this->category = $category;
		$this->item = $item;
	}

	public function getCategory() : string{
		return $this->category;
	}

	public function getItem() : Item{
		$display = CategoryConfig::getStringList(CategoryConfig::BUTTON);
		return (clone $this->item)
			->setCustomName(str_replace("{CATEGORY}", $this->category, array_shift($display)))
			->setLore(str_replace("{CATEGORY}", $this->category, $display));
	}

	public function getNamedTag(string $name) : CompoundTag{
		$tag = parent::getNamedTag($name);
		$tag->setString(self::TAG_CATEGORY, $this->category);
		$tag->setTag($this->item->nbtSerialize(-1, self::TAG_ITEM));
		return $tag;
	}
}