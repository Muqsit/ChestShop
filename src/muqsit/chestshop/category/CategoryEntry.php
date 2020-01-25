<?php

declare(strict_types=1);

namespace muqsit\chestshop\category;

use pocketmine\item\Item;

final class CategoryEntry{

	/** @var Item */
	private $item;

	/** @var float */
	private $price;

	public function __construct(Item $item, float $price){
		$this->item = $item;
		$this->price = $price;
	}

	public function getItem() : Item{
		return $this->item;
	}

	public function getPrice() : float{
		return $this->price;
	}

	public function createDisplayItem() : void{
	}
}