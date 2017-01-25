<?php
/*
*
* Copyright (C) 2017 Muqsit Rayyan
*
*  _____ _               _   _____ _                 
* /  __ \ |             | | /  ___| |                
* | /  \/ |__   ___  ___| |_\ `--.| |__   ___  _ __  
* | |   | '_ \ / _ \/ __| __|`--. \ '_ \ / _ \| '_ \ 
* | \__/\ | | |  __/\__ \ |_/\__/ / | | | (_) | |_) |
*  \____/_| |_|\___||___/\__\____/|_| |_|\___/| .__/ 
*                                             | |    
*                                             |_|    
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Lesser General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
*
* @author Muqsit Rayyan
*
*/
namespace ChestShop\Chest;

use pocketmine\level\format\Chunk;
use pocketmine\nbt\tag\{CompoundTag, IntTag};
use pocketmine\block\Block;

class CustomChest extends \pocketmine\tile\Chest{

	public function __construct(Chunk $chunk, CompoundTag $nbt){
		parent::__construct($chunk, $nbt);
		$this->inventory = new CustomChestInventory($this);
	}

	public function getReplacement() : Block{
		return Block::get($this->namedtag->replace->getValue() ?? 0);
	}
}