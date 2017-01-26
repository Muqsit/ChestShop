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

use ChestShop\Chest\CustomChest;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\nbt\tag\{CompoundTag, IntTag, ListTag, StringTag, IntArrayTag};
use pocketmine\command\{Command, CommandSender};
use pocketmine\utils\TextFormat as TF;
use pocketmine\block\Block;
use pocketmine\item\Item;

class Main extends PluginBase{

	const PREFIX = TF::BOLD.TF::YELLOW.'CS '.TF::RESET;

	protected $shops = [];
	private $helpcmd = [];
	private static $instance = null;
	public $inChestShop, $clicks = [];

	public function onEnable(){
		self::$instance = $this;
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$info = [
			" ",
			" _____ _               _   _____ _                 ",
			"/  __ \ |             | | /  ___| |                ",
			"| /  \/ |__   ___  ___| |_\ `--.| |__   ___  _ __  ",
			"| |   | '_ \ / _ \/ __| __|`--. \ '_ \ / _ \| '_ \ ",
			"| \__/\ | | |  __/\__ \ |_/\__/ / | | | (_) | |_) |",
			" \____/_| |_|\___||___/\__\____/|_| |_|\___/| .__/ ",
			"                                            | |    ",
			"                                            |_|    ",
			" ",
			"@author Muqsit Rayyan",
			" "
		];
		$this->getLogger()->notice(implode("\n", $info));

		if(!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		foreach(['shops.yml'] as $file){
			if(!is_file($this->getDataFolder().$file)){
				$openf = fopen($this->getDataFolder().$file, 'w') or die('Cannot open file: '.$file);
				file_put_contents($this->getDataFolder().$file, $this->getResource($file));
				fclose($openf);
			}
		}

		$shops = yaml_parse_file($this->getDataFolder().'shops.yml');
		if(!empty($shops)) foreach($shops as $key => $val) $this->shops[$key] = $val;
		$this->helpcmd = [
			TF::YELLOW.TF::BOLD.'Chest Shop'.TF::RESET,
			TF::YELLOW.'/{:cmd:} add [price]'.TF::GRAY.' - Add the item in your hand to the chest shop.',
			TF::YELLOW.'/{:cmd:} remove [page] [slot]'.TF::GRAY.' - Remove an item off the chest shop.',
			TF::YELLOW.'/{:cmd:} reload'.TF::GRAY.' - Reload the plugin (to fix errors or refresh data).'
		];
		Tile::registerTile(CustomChest::class);
	}

	public static function getInstance(){
		return self::$instance;
	}

	public function onDisable(){
		yaml_emit_file($this->getDataFolder().'shops.yml', $this->shops);
	}

	public function sendChestShop(Player $player){
		$nbt = new CompoundTag('', [
			new ListTag('Items', []),
			new StringTag('id', Tile::CHEST),
			new IntTag('ChestShop', 1),
			new IntTag('x', floor($player->x)),
			new IntTag('y', floor($player->y) - 4),
			new IntTag('z', floor($player->z))
		]);
		/** @var Chest $tile */
		$tile = Tile::createTile('CustomChest', $player->chunk, $nbt);
		$tile->namedtag->replace = new IntTag("replace", $tile->getBlock()->getId());
		$block = Block::get(Block::CHEST);
		$block->x = floor($tile->x);
		$block->y = floor($tile->y);
		$block->z = floor($tile->z);
		$block->level = $tile->getLevel();
		$block->level->sendBlocks([$player], [$block]);
		$inventory = $tile->getInventory();
		$this->fillInventoryWithShop($inventory);
		$player->addWindow($inventory);
	}

	public function reload(){
		$this->onDisable();
		$this->shops = [];
		$shops = yaml_parse_file($this->getDataFolder().'shops.yml');
		if(!empty($shops)) foreach($shops as $key => $val) $this->shops[$key] = $val;
	}

	public function getItemFromShop(int $id) : Item{
		$data = $this->shops[$id] ?? null;
		$item = null;
		if(is_array($data)){
			$item = Item::get($data[0], $data[1], $data[2]);
			$item->setNamedTag(unserialize($data[3]));
			unset($item->getNamedTag()->ChestShop);
		}
		return $item ?? Item::get(0);
	}

	public function fillInventoryWithShop($inventory, $page = 0){
		$inventory->clearAll();
		if(!empty($this->shops)) {
			$chunked = array_chunk($this->shops, 24, true);
			$page = isset($chunked[$page]) ? $page : 0;
			foreach($chunked[$page] as $data){
				$item = Item::get($data[0], $data[1], $data[2]);
				if($data[3] === null) break;
				$item->setNamedTag(unserialize($data[3]));
				$item->setCustomName(TF::RESET.TF::YELLOW.'Tap again to purchase for $'.$item->getNamedTag()->ChestShop->getValue()[0].TF::RESET."\n".' '."\n".$item->getName());
				$inventory->addItem($item);
			}
		}

		// Page turners.
		$turnleft = Item::get(Item::PAPER);
		$turnright = Item::get(Item::PAPER);
		$turnleft->setCustomName(TF::RESET.TF::GOLD.TF::BOLD.'<< Turn Left'.TF::RESET."\n".TF::GRAY.'Turn towards the left.');
		$turnright->setCustomName(TF::RESET.TF::GOLD.TF::BOLD.' Turn Right'.TF::RESET."\n".TF::GRAY.'Turn towards the right.');

		$nbtleft = $turnleft->getNamedTag();
		$nbtleft->turner = new IntArrayTag('turner', [0, $page]);
		$turnleft->setNamedTag($nbtleft);
		$nbtright = $turnright->getNamedTag();
		$nbtright->turner = new IntArrayTag('turner', [1, $page]);
		$turnright->setNamedTag($nbtright);

		$inventory->setItem(25, $turnleft);
		$inventory->setItem(26, $turnright);
	}

	public function addToChestShop(Item $item, int $price){
		$key = rand();
		$nbt = $item->getNamedTag() !== null ? serialize($item->getNamedTag()) : null;
		$nbt = $item->getNamedTag() ?? new CompoundTag("", []);
		$nbt->ChestShop = new IntArrayTag ('ChestShop', [$price, $key]);
		$nbt->CSKey = $key;
		$item->setNamedTag($nbt);
		$this->shops[$key] = [$item->getId(), $item->getDamage(), $item->getCount(), $nbt];
	}

	public function removeItemOffShop(int $page, int $slot){
		if(empty($this->shops)) return;
		$keys = array_keys($this->shops);//$shops is an associative array.
		$key = (24*$page) + $slot;//array_chunks divides $shops into 24 parts in the GUI. Hope PHP follows BODMAS.
		unset($this->shops[$keys[--$key]]);//$slot - 1. Slots are counted from 0. If $slot is 1, the issuer probably (actually) is referring to slot zero.
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		if(isset($args[0])){
			switch(strtolower($args[0])){
				case "help":
					$sender->sendMessage(str_replace('{:cmd:}', $cmd, implode("\n", $this->helpcmd)));
					break;
				case "about":
					$sender->sendMessage(TF::YELLOW.TF::BOLD.'ChestShop'.TF::RESET."\n".TF::GRAY.'Coded by Muqsit Rayyan ('.TF::AQUA.'@muqsitrayyan'.TF::GRAY.').');
					break;
				case "add":
					if($sender->hasPermission('chestshop.command.add')){
						$item = $sender->getInventory()->getItemInHand();
						if($item->getId() === 0) $sender->sendMessage(self::PREFIX.TF::RED.'Please hold an item in your hand.');
						else{
							if(isset($args[1]) && is_numeric($args[1]) && $args[1] >= 0) $this->addToChestShop($item, $args[1]);
							else $sender->sendMessage(TF::RED.'Please enter a valid number.');
						}
					}    
					break;
				case "remove":
					if($sender->hasPermission('chestshop.command.remove')){
						if(isset($args[1], $args[2]) && is_numeric($args[1]) && is_numeric($args[2]) && ($args[1] >= 0) && ($args[2] >= 1)){
							$sender->sendMessage(self::PREFIX.TF::YELLOW.'Removed item on page #'.$args[1].', slot #'.$args[2].'.');
							$this->removeItemOffShop($args[1], $args[2]);
						} else $sender->sendMessage(TF::RED.'Page number and item slot must be integers (page > -1, slot > 0).');
					}    
					break;
				case "reload":
					if($sender->hasPermission('chestshop.command.reload')){
						$sender->sendMessage(self::PREFIX.TF::AQUA.'ChestShop is reloading...');
						$this->reload();
						$sender->sendMessage(self::PREFIX.TF::AQUA.'ChestShop has reloaded successfully.');
					}    
					break;
				default:
					$sender->sendMessage(TF::RED.'Type /cs help to get a list of chest shop help commands.');
					break;
			}
		}else $this->sendChestShop($sender);
	}
}
