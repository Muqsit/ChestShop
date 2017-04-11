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

use ChestShop\Chest\{CustomChest, CustomChestInventory};
use onebone\economyapi\EconomyAPI;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class EventListener implements Listener{

	protected $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	/**
	* Prevents duplication of GUI items.
	* Doesn't allow block breaking if there
	* is a Chest Shop tile where the block is.
	*/
	public function onBreak(BlockBreakEvent $event){
		if($event->getBlock()->getLevel()->getTile($event->getBlock()) instanceof CustomChest) $event->setCancelled();
	}

	/**
	* This is where all the chest shop
	* transaction are handled.
	*/
	public function onTransaction(InventoryTransactionEvent $event){
		$transactions = $event->getTransaction()->getTransactions();

		$player = null;
		$chestinv = null;
		$action = null;
		foreach($transactions as $transaction){
			if(($inv = $transaction->getInventory()) instanceof CustomChestInventory){
				foreach($inv->getViewers() as $assumed){
					if($assumed instanceof Player){
						$player = $assumed;
						$chestinv = $inv;
						$action = $transaction;
						break 2;
					}
				}
			}
		}

		if(($player ?? $chestinv ?? $action) === null){
			return;
		}

		/*
		* $player => Player interacting with the GUI.
		* $chestinv => The chest's inventory.
		* $action => BaseTransaction|Transaction|SimpleTransactionGroup
		*/
		$event->setCancelled();
		$item = $action->getSourceItem();
		if($item->getId() === Item::AIR){
			return;
		}

		if(isset($item->getNamedTag()->turner)){
			$pagedata = $item->getNamedTag()->turner->getValue();
			$page = $pagedata[0] === 0 ? --$pagedata[1] : ++$pagedata[1];
			$this->plugin->fillInventoryWithShop($chestinv, $page);
			return;
		}

		$data = isset($item->getNamedTag()->ChestShop) ? $item->getNamedTag()->ChestShop->getValue() : null;
		if($data === null){
			return;
		}

		$price = $data[0] ?? $this->plugin->defaultprice;
		if(!isset($this->plugin->clicks[$player->getId()][$data[1]])){
			$this->plugin->clicks[$player->getId()][$data[1]] = 1;
			return;
		}

		if(EconomyAPI::getInstance()->myMoney($player) >= $price){
	 	 	$item = $this->plugin->getItemFromShop($data[1]);
			$player->sendMessage(Main::PREFIX.TF::GREEN.'Purchased '.TF::BOLD.$item->getName().TF::RESET.TF::GREEN.TF::GRAY.' (x'.$item->getCount().')'.TF::GREEN.' for $'.$price.'.');
			$player->getInventory()->addItem($item);
			EconomyAPI::getInstance()->reduceMoney($player, $price);
			unset($this->plugin->clicks[$player->getId()]);
		}else{
			$player->sendMessage(Main::PREFIX.TF::RED.'You cannot afford this item.');
			$chestinv->onClose($player);
		}
	}
}
