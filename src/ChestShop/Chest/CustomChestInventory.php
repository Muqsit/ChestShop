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

use pocketmine\inventory\InventoryType;
use pocketmine\Player;

class CustomChestInventory extends \pocketmine\inventory\ChestInventory{

	public function __construct(CustomChest $tile){
		parent::__construct($tile, InventoryType::get(InventoryType::CHEST));
	}

	public function onOpen(Player $who){
		parent::onOpen($who);
	}

	public function onClose(Player $who){
		parent::onClose($who);
		$this->holder->getLevel()->setBlock($this->holder, $this->holder->getReplacement());
		$this->holder->close();
	}
}