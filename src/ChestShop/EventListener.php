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
use pocketmine\inventory\{PlayerCursorInventory, PlayerInventory};
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class EventListener implements Listener{

	/** @var Main */
	protected $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	/**
	* Prevents duplication of GUI items.
	* Doesn't allow block breaking if there
	* is a Chest Shop tile where the block is.
	*/
	public function onBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		if($block->getLevel()->getTileAt($block->x, $block->y, $block->z) instanceof CustomChest){
			$event->setCancelled();
		}
	}

	/**
	* This is where all the chest shop
	* transaction are handled.
	*/
	public function onTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();

		$actions = $transaction->getActions();
		if(count($actions) !== 2){
			return;
		}

		$inventoryAction = null;
		$playerAction = null;

		foreach($actions as $action){
			if($action instanceof DropItemAction)
				$event->setCancelled(true);
			$inventory = $action->getInventory();

			if($inventory instanceof CustomChestInventory){
				$inventoryAction = $action;
			}elseif($inventory instanceof PlayerInventory || $inventory instanceof PlayerCursorInventory){
				$playerAction = $action;
			}
		}

		if($inventoryAction !== null){
			$event->setCancelled();//cancel ALL inventory transactions happening in CustomChestInventory
			if($playerAction !== null){
				//$itemPuttingIn = $playerAction->getSourceItem();
				$itemTakingOut = $inventoryAction->getSourceItem();//item in the chest inventory when clicked.

				$nbt = $itemTakingOut->getNamedTag();
				if($nbt->hasTag("turner")){
					$pagedata = $nbt->getIntArray("turner");
					$page = $pagedata[Main::NBT_TURNER_DIRECTION] === Main::LEFT_TURNER ? --$pagedata[Main::NBT_TURNER_CURRENTPAGE] : ++$pagedata[Main::NBT_TURNER_CURRENTPAGE];
					$this->plugin->fillInventoryWithShop($inventoryAction->getInventory(), $page);
					return;
				}

				if($nbt->hasTag("ChestShop")){
					$cs = $nbt->getIntArray("ChestShop");

					$price = $cs[Main::NBT_CHESTSHOP_PRICE] ?? $this->plugin->defaultprice;
					if(EconomyAPI::getInstance()->myMoney($player) >= $price){
		 			 	$item = $this->plugin->getItemFromShop($cs[Main::NBT_CHESTSHOP_ID]);
						$player->sendMessage(Main::PREFIX.TF::GREEN.'Purchased '.TF::BOLD.$item->getName().TF::RESET.TF::GREEN.TF::GRAY.' (x'.$item->getCount().')'.TF::GREEN.' for $'.$price.'.');
						$player->getInventory()->addItem($item);
						EconomyAPI::getInstance()->reduceMoney($player, $price);
						unset($this->plugin->clicks[$player->getId()]);

						$pk = new LevelEventPacket();
						$pk->evid = LevelEventPacket::EVENT_SOUND_ORB;
						$pk->data = PHP_INT_MAX;
						$pk->position = $player->asVector3();
						$player->dataPacket($pk);
					}else{
						$player->sendMessage(Main::PREFIX.TF::RED.'You cannot afford this item.');
						$inventoryAction->getInventory()->onClose($player);
					}
					return;
				}
			}
		}
	}
}
