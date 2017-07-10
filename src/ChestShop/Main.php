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

use pocketmine\command\{Command, CommandSender};
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\nbt\tag\{CompoundTag, IntTag, ListTag, StringTag, IntArrayTag};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat as TF;

class Main extends PluginBase{

	const PREFIX = TF::BOLD.TF::YELLOW.'CS '.TF::RESET;

	const CONFIG = [
		'config.yml' => [
			'default-price' => 15000,
			'banned-items' => [
				'35:1',
				7,
			],
			'enable-sync' => false
		]
	];

	public $defaultprice;
	public $inChestShop, $clicks = [];
	protected $shops = [];
	private $helpcmd = [];
	private static $instance = null;
	private $notallowed = [];
	private $economyshop = false;

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
		foreach(array_keys(self::CONFIG) as $file){
			$this->updateConfig($file);
		}

		$shops = yaml_parse_file($this->getDataFolder().'shops.yml');
		if(!empty($shops)) foreach($shops as $key => $val) $this->shops[$key] = $val;
		
		$config = yaml_parse_file($this->getDataFolder().'config.yml');
		$this->defaultprice = $config["default-price"] ?? 15000;
		$this->notallowed = array_flip($config["banned-items"] ?? []);

		$this->helpcmd = [
			TF::YELLOW.TF::BOLD.'Chest Shop'.TF::RESET,
			TF::YELLOW.'/{:cmd:} add [price]'.TF::GRAY.' - Add the item in your hand to the chest shop.',
			TF::YELLOW.'/{:cmd:} remove [page] [slot]'.TF::GRAY.' - Remove an item off the chest shop.',
			TF::YELLOW.'/{:cmd:} reload'.TF::GRAY.' - Reload the plugin (to fix errors or refresh data).'
		];
		Tile::registerTile(CustomChest::class);

		if($config['enable-sync'] == true){
			$this->economyshop = $this->getServer()->getPluginManager()->getPlugin('EconomyShop');
		}else{
			$this->economyshop = false;
		}
	}

	/**
	* @return Main
	*/
	public static function getInstance(){
		return self::$instance;
	}

	public function onDisable(){
		yaml_emit_file($this->getDataFolder().'shops.yml', $this->shops);
	}

	/**
	* Updates config with newer data.
	*/
	private function updateConfig(string $config){
		if(isset(self::CONFIG[$config])){
			$data = [];
			if(is_file($path = $this->getDataFolder().$config)){
				$data = yaml_parse_file($path);
			}
			foreach(self::CONFIG[$config] as $key => $value){
				if(!isset($data[$key])){
					$data[$key] = $value;
				}
			}
			yaml_emit_file($path, $data);
		}
	}

	/**
	* Sends the chest shop (inventory)
	* to the player.
	*
	* @param Player $player
	*/
	public function sendChestShop(Player $player){
		/** @var Chest $tile */
		$tile = Tile::createTile('CustomChest', $player->getLevel(), new CompoundTag('', [
			new StringTag('id', Tile::CHEST),
			new IntTag('ChestShop', 1),
			new IntTag('x', floor($player->x)),
			new IntTag('y', floor($player->y) - 4),
			new IntTag('z', floor($player->z))
		]));
		$block = Block::get(Block::CHEST);
		$block->x = $tile->x;
		$block->y = $tile->y;
		$block->z = $tile->z;
		$block->level = $tile->getLevel();
		$block->level->sendBlocks([$player], [$block]);
		$this->fillInventoryWithShop($inventory = $tile->getInventory());
		$player->addWindow($inventory);
	}

	public function reload(){
		$this->onDisable();
		$this->shops = [];
		$shops = yaml_parse_file($this->getDataFolder().'shops.yml');
		if(!empty($shops)) foreach($shops as $key => $val) $this->shops[$key] = $val;
		$config = yaml_parse_file($this->getDataFolder().'config.yml');
		$this->defaultprice = $config["default-price"] ?? 15000;
		$this->notallowed = array_flip($config["banned-items"] ?? []);
	}

	/**
	* Get item from shop via shop ID.
	*
	* @param int $id
	* @return Item
	*/
	public function getItemFromShop(int $id): Item {
		$data = $this->shops[$id] ?? null;
		$item = null;
		if($data !== null){
			$item = Item::get($data[0], $data[1], $data[2]);
			$item->setNamedTag(unserialize($data[3]));
			unset($item->getNamedTag()->ChestShop);
		}
		return $item ?? Item::get(0);
	}

	/**
	* Fills the $inventory with contents
	* of chest shop.
	*
	* @param ChestInventory $inventory
	* @param int $page
	*/
	public function fillInventoryWithShop(CustomChestInventory $inventory, int $page = 0){
		$inventory->clearAll();
		if(!empty($this->shops)) {
			$chunked = array_chunk($this->shops, 24, true);
			if($page < 0){
				$page = count($chunked) - 1;
			}
			$page = isset($chunked[$page]) ? $page : 0;
			foreach($chunked[$page] as $data){
				$item = Item::get($data[0], $data[1], $data[2]);
				if($data[3] === null) break;
				$item->setNamedTag(unserialize($data[3]));
				$item->setCustomName(TF::RESET.$item->getName()."\n \n".TF::YELLOW.'Double-tap to purchase for $'.$item->getNamedTag()->ChestShop->getValue()[0].TF::RESET);
				$inventory->addItem($item);
			}
		}

		// Page turners.
		$turnleft = Item::get(Item::PAPER);
		$turnright = Item::get(Item::PAPER);
		$turnleft->setCustomName(TF::RESET.TF::GOLD.TF::BOLD.'<< Turn Left'.TF::RESET."\n".TF::GRAY.'Turn towards the left.');
		$turnright->setCustomName(TF::RESET.TF::GOLD.TF::BOLD.'Turn Right >>'.TF::RESET."\n".TF::GRAY.'Turn towards the right.');

		$nbtleft = $turnleft->getNamedTag();
		$nbtleft->turner = new IntArrayTag('turner', [0, $page]);
		$turnleft->setNamedTag($nbtleft);

		$nbtright = $turnright->getNamedTag();
		$nbtright->turner = new IntArrayTag('turner', [1, $page]);
		$turnright->setNamedTag($nbtright);

		$inventory->setItem(25, $turnleft);
		$inventory->setItem(26, $turnright);
	}

	/**
	* Adds an item to the chest shop.
	*
	* @param Item $item
	* @param int $price
	*/
	public function addToChestShop(Item $item, int $price){
		$key = rand();
		if(isset($this->shops[$key])){
			while(isset($this->shops[$key])){
				$key = rand();
			}
		}
		$nbt = $item->getNamedTag() ?? new CompoundTag("", []);
		$nbt->ChestShop = new IntArrayTag('ChestShop', [$price, $key]);
		$nbt->CSKey = $key;
		$item->setNamedTag($nbt);
		$this->shops[$key] = [$item->getId(), $item->getDamage(), $item->getCount(), serialize($nbt)];
	}

	/**
	* Removes an item off the chest shop.
	*
	* @param int $page
	* @param int $slot
	*/
	public function removeItemOffShop(int $page, int $slot){
		if(empty($this->shops)) return;
		$keys = array_keys($this->shops);//$this->shops is an associative array.
		$key = (24*$page) + $slot;//array_chunks divides $shops into 24 parts in the GUI.
		unset($this->shops[$keys[--$key]]);//$slot - 1. Slots are counted from 0. If $slot is 1, the issuer probably (actually) is referring to slot zero.
	}

	/**
	* Checks whether or not it's allowed
	* to put an item in the chest shop.
	* This can be set up in the config
	* (banned-items key in the config).
	*
	* @param int $itemId
	* @param int $itemDamage
	* @return bool
	*/
	private function isNotAllowed(int $itemId, int $itemDamage = 0) : bool{
		if($itemDamage === 0){
			return isset($this->notallowed[$itemId]) || isset($this->notallowed[$itemId.':'.$itemDamage]);
		}
		return isset($this->notallowed[$itemId.':'.$itemDamage]);
	}

	/**
	* Removes item off chest shop
	* by key.
	*
	* @param int|array $keys
	*/
	public function removeItemsByKey($keys){
		foreach((array)$keys as $key){
			unset($this->shops[$key]);
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		if(isset($args[0])){
			switch(strtolower($args[0])){
				case "help":
					$sender->sendMessage(str_replace('{:cmd:}', $cmd, implode("\n", $this->helpcmd)));
					break;
				case "about":
					$sender->sendMessage(TF::YELLOW.TF::BOLD.'ChestShop'.TF::RESET."\n".TF::GRAY.'Created by Muqsit ('.TF::AQUA.'@muqsitrayyan'.TF::GRAY.').');
					break;
				case "add":
					if($sender->hasPermission('chestshop.command.add')){
						$item = $sender->getInventory()->getItemInHand();
						if($item->getId() === 0) $sender->sendMessage(self::PREFIX.TF::RED.'Please hold an item in your hand.');
						elseif($this->isNotAllowed($item->getId(), $item->getDamage())) $sender->sendMessage(self::PREFIX.TF::RED.'You cannot sell '.((Item::get($item->getId(), $item->getDamage()))->getName()).' on /chestshop.');
						else{
							if(isset($args[1]) && is_numeric($args[1]) && $args[1] >= 0) {
								$sender->sendMessage(self::PREFIX.TF::YELLOW.'Added '.(explode("\n", $item->getName())[0]).' to '.$cmd->getName().' for $'.$args[1].'.');
								$this->addToChestShop($item, $args[1]);
							}else $sender->sendMessage(TF::RED.'Please enter a valid number.');
						}
					}    
					break;
				case "removebyid":
					if($sender->hasPermission('chestshop.command.remove')){
						if(isset($args[1]) && is_numeric($args[1]) && $args[1] >= 1){
							$damage = $args[2] ?? 0;
							if(count($this->shops) <= 27){
								$i = 0;
								foreach($this->shops as $k => $item){
									if($item[0] == $args[1] && $item[1] == $damage){
										unset($this->shops[$k]);
										++$i;
									}
								}
								$sender->sendMessage(self::PREFIX.TF::YELLOW.$i.' items were removed off auction house (ID: '.$args[1].', DAMAGE: '.$damage.').');
							}else $this->getServer()->getScheduler()->scheduleAsyncTask(new RemoveByIdTask([$sender->getName(), $args[1], $damage, &$this->shops]));
						}else $sender->sendMessage(self::PREFIX.TF::YELLOW.'Usage: /'.$cmd->getName().' removebyid [item-id] [item-damage]');
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
				case "synceconomy":
					if($sender->hasPermission('chestshop.command.opcmd')){
						switch($this->economyshop){
							case null:
								$sender->sendMessage(TF::RED."Couldn't find EconomyShop plugin. Make sure the plugin is enabled and running.");
								return false;
							case false:
								$sender->sendMessage(TF::RED.'You must set the "enable-sync" option to true in the config to use this command.');
								return false;
						}
						$depends = $this->economyshop->getDescription()->getVersion() == '2.0.3';
						$data = yaml_parse_file($this->economyshop->getDataFolder().'Shops.yml');
						$cnt = count($data);
						$keyId = $depends ? 'item' : 4;
						$keyDmg = $depends ? 'meta' : 5;
						$keyCnt = $depends ? 'amount' : 6;
						$keyPrice = $depends ? 'price' : 8;
						$i = 0;
						$this->getLogger()->info($sender->getName().' is synchronizing data from economyshop.');
						$time = microtime(true);
						foreach($data as $detail){
							$item = Item::get($detail[$keyId], $detail[$keyDmg], $detail[$keyCnt]);
							$price = $detail[$keyPrice];
							$this->addToChestShop($item, $price);
							$sender->sendMessage(TF::YELLOW.'Synchronizing data from EconomyShop '.TF::GREEN.'('.TF::GRAY.++$i.TF::GREEN.'/'.TF::GRAY.$cnt.TF::GREEN.')');
						}
						$time = microtime(true) - $time;
						$sender->sendMessage(TF::YELLOW.'Data synchronized. Took '.TF::GREEN.$time.TF::YELLOW.'s.');
						$this->getLogger()->info($sender->getName().' has synchronized data from chestshop.');
					}
					break;
				default:
					$sender->sendMessage(TF::RED.'Type /cs help to get a list of chest shop help commands.');
					break;
			}
		}else{
			if($sender instanceof Player){
				$this->sendChestShop($sender);
			}else{
				$sender->sendMessage(str_replace('{:cmd:}', $cmd, implode("\n", $this->helpcmd)));
			}
		}
		return true;
	}
}
