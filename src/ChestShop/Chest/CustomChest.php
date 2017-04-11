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

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\nbt\tag\{CompoundTag, IntTag};
use pocketmine\Player;

class CustomChest extends \pocketmine\tile\Chest{

	private $replacement = [0, 0];

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->inventory = new CustomChestInventory($this);
		$this->replacement = [$this->getBlock()->getId(), $this->getBlock()->getDamage()];
	}

	public function getInventory() : CustomChestInventory{
		return $this->inventory;
	}

	private function getReplacement() : Block{
		return Block::get(...$this->replacement);
	}

	public function sendReplacement(Player $player){
		$block = $this->getReplacement();
		$block->x = floor($this->x);
		$block->y = floor($this->y);
		$block->z = floor($this->z);
		$block->level = $this->getLevel();
		if($block->level !== null){
			$block->level->sendBlocks([$player], [$block]);
		}
	}

	public function spawnTo(Player $player){
		//needless
	}

	public function spawnToAll(){
		//needless
	}
}
