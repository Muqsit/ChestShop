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

use ChestShop\Chest\CustomChestInventory;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\Listener;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\Player;
use pocketmine\inventory\ChestInventory;
use pocketmine\utils\TextFormat as TF;
use pocketmine\item\Item;

class EventListener implements Listener{

	protected $plugin;
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	public function onTransaction(InventoryTransactionEvent $event){
		$transactions = $event->getTransaction()->getTransactions();

		$player = null;
		$chestinv = null;
		$action = null;
		foreach($transactions as $transaction){
			if(($inv = $transaction->getInventory()) instanceof CustomChestInventory){
				foreach($inv->getViewers() as $assumed)
					if($assumed instanceof Player) {
						$player = $assumed;
						$chestinv = $inv;
					}
			}
			$action = $transaction;
		}
		if($chestinv === null) return;

		$item = $action->getTargetItem();
		$event->setCancelled();

		if(isset($item->getNamedTag()->turner)){
			$action = $item->getNamedTag()->turner->getValue();
			$page = $action[0] === 0 ? --$action[1] : ++$action[1];
			$this->plugin->fillInventoryWithShop($chestinv, $page);
			return;
		}

		$data = $item->getNamedTag()->ChestShop->getValue();
		$price = $data ?? 0;
		if(EconomyAPI::getInstance()->myMoney($player) >= $price){
			$player->sendMessage(Main::PREFIX.TF::GREEN.'Purchased '.TF::BOLD.$item->getName().TF::RESET.TF::GREEN.TF::GRAY.' (x'.$item->getCount().')'.TF::GREEN.' for $'.$price);
			unset($item->getNamedTag()->ChestShop);
			if (!isset($item->getNamedTag()->ChestShop)) $player->getInventory()->addItem($item);
			EconomyAPI::getInstance()->reduceMoney($player, $price);
		}else{
			$player->sendMessage(Main::PREFIX.TF::RED.'You cannot afford this item.');
			$chestinv->onClose($player);
		}
	}
}