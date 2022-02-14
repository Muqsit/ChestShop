<?php

declare(strict_types=1);

namespace muqsit\chestshop\category;

use pocketmine\item\Item;

final class CategoryEntry{

	public function __construct(
		private Item $item,
		private float $price
	){}

	public function getItem() : Item{
		return $this->item;
	}

	public function getPrice() : float{
		return $this->price;
	}
}