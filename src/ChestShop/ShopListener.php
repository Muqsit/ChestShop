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
namespace ChestShop;

use onebone\economyapi\EconomyAPI;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class ShopListener{

	/** @var Main */
	protected $plugin;

	/** @var EconomyAPI */
	protected $economy;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		$this->economy = EconomyAPI::getInstance();
	}

	/**
	 * This is where all the chest shop
	 * transaction are handled.
	 */
	public function onTransaction(Player $player, Item $itemPuttingIn, Item $itemTakingOut, SlotChangeAction $inventoryAction) : bool{
		$itemTakingOut = $inventoryAction->getSourceItem();//item in the chest inventory when clicked.

		$nbt = $itemTakingOut->getNamedTag();
		if($nbt->hasTag("turner")){
			$pagedata = $nbt->getIntArray("turner");
			$page = $pagedata[Main::NBT_TURNER_DIRECTION] === Main::LEFT_TURNER ? --$pagedata[Main::NBT_TURNER_CURRENTPAGE] : ++$pagedata[Main::NBT_TURNER_CURRENTPAGE];
			$this->plugin->fillInventoryWithShop($inventoryAction->getInventory(), $page);
		}elseif($nbt->hasTag("ChestShop")){
			$cs = $nbt->getIntArray("ChestShop");

			$price = $cs[Main::NBT_CHESTSHOP_PRICE] ?? $this->plugin->defaultprice;
			if($this->economy->myMoney($player) >= $price){
 			 	$item = $this->plugin->getItemFromShop($cs[Main::NBT_CHESTSHOP_ID]);
				$player->sendMessage(Main::PREFIX.TF::GREEN.'Purchased '.TF::BOLD.$item->getName().TF::RESET.TF::GREEN.TF::GRAY.' (x'.$item->getCount().')'.TF::GREEN.' for $'.$price.'.');
				$player->getInventory()->addItem($item);
				$this->economy->reduceMoney($player, $price);

				$pk = new LevelEventPacket();
				$pk->evid = LevelEventPacket::EVENT_SOUND_ORB;
				$pk->data = PHP_INT_MAX;
				$pk->position = $player->asVector3();
				$player->dataPacket($pk);
			}else{
				$player->sendMessage(Main::PREFIX.TF::RED.'You cannot afford this item.');
				$inventoryAction->getInventory()->onClose($player);
			}
		}
		return true;
	}
}
