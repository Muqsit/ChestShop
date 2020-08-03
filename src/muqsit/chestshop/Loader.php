<?php

declare(strict_types=1);

namespace muqsit\chestshop;

use muqsit\chestshop\button\ButtonFactory;
use muqsit\chestshop\category\Category;
use muqsit\chestshop\category\CategoryConfig;
use muqsit\chestshop\category\CategoryEntry;
use muqsit\chestshop\database\Database;
use muqsit\chestshop\economy\EconomyManager;
use muqsit\chestshop\ui\ConfirmationUI;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

final class Loader extends PluginBase{

	/** @var Database */
	private $database;

	/** @var ConfirmationUI|null */
	private $confirmation_ui;

	/** @var ChestShop */
	private $chest_shop;

	public function onEnable() : void{
		$this->initVirions();
		$this->database = new Database($this);

		if($this->getConfig()->getNested("confirmation-ui.enabled", false)){
			$this->confirmation_ui = new ConfirmationUI($this);
		}

		$this->chest_shop = new ChestShop($this->database);

		ButtonFactory::init($this);
		CategoryConfig::init($this);
		EconomyManager::init($this);

		$this->database->load($this->chest_shop);
	}

	private function initVirions() : void{
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}
	}

	public function onDisable() : void{
		$this->database->close();
	}

	public function getConfirmationUi() : ?ConfirmationUI{
		return $this->confirmation_ui;
	}

	public function getChestShop() : ChestShop{
		return $this->chest_shop;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "This command can only be executed as a player.");
			return true;
		}

		if(isset($args[0])){
			switch($args[0]){
				case "addcat":
				case "addcategory":
					if($sender->hasPermission("chestshop.command.add")){
						$button = $sender->getInventory()->getItemInHand();
						if(!$button->isNull()){
							if(isset($args[1])){
								$name = implode(" ", array_slice($args, 1));
								$success = true;
								try{
									$this->chest_shop->addCategory(new Category($name, $button));
								}catch(\InvalidArgumentException $e){
									$sender->sendMessage(TextFormat::RED . $e->getMessage());
									$success = false;
								}
								if($success){
									$sender->sendMessage(
										TextFormat::GREEN . "Successfully added category {$name}" . TextFormat::RESET . TextFormat::GREEN . "!" . TextFormat::EOL .
										TextFormat::GRAY . "Use " . TextFormat::GREEN . "/{$label} additem {$name} <price>" . TextFormat::GRAY . " to list the item in your hand!"
									);
								}
								return true;
							}
						}else{
							$sender->sendMessage(TextFormat::RED . "Please hold an item in your hand. That item will be used as an icon in /{$label}.");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
						return true;
					}
					$sender->sendMessage(TextFormat::RED . "Usage: /{$label} {$args[0]} <name>");
					return true;
				case "removecat":
				case "removecategory":
					if($sender->hasPermission("chestshop.command.remove")){
						if(isset($args[1])){
							$name = implode(" ", array_slice($args, 1));
							$success = true;
							try{
								$this->chest_shop->removeCategory($name);
							}catch(\InvalidArgumentException $e){
								$sender->sendMessage(TextFormat::RED . $e->getMessage());
								$success = false;
							}
							if($success){
								$sender->sendMessage(TextFormat::GREEN . "Successfully removed category {$name}" . TextFormat::RESET . TextFormat::GREEN . "!");
							}
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
						return true;
					}
					$sender->sendMessage(TextFormat::RED . "Usage: /{$label} {$args[0]} <name>");
					return true;
				case "additem":
					if($sender->hasPermission("chestshop.command.add")){
						if(isset($args[1]) && isset($args[2])){
							$category = null;
							try{
								$category = $this->chest_shop->getCategory($args[1]);
							}catch(\InvalidArgumentException $e){
								$sender->sendMessage(TextFormat::RED . $e->getMessage());
							}
							if($category !== null){
								$item = $sender->getInventory()->getItemInHand();
								if(!$item->isNull()){
									$price = (float) $args[2];
									if($price >= 0.0){
										$category->addEntry(new CategoryEntry($item, $price));
										$sender->sendMessage(TextFormat::GREEN . "Added item {$item->getName()}" . TextFormat::RESET . TextFormat::GREEN . " to category {$category->getName()}!");
									}else{
										$sender->sendMessage(TextFormat::RED . "Invalid price {$args[2]}");
									}
								}else{
									$sender->sendMessage(TextFormat::RED . "Please hold an item in your hand that you'd like to list.");
								}
							}
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
						return true;
					}
					$sender->sendMessage(TextFormat::RED . "Usage: /{$label} {$args[0]} <category> <price>");
					return true;
				case "removeitem":
					if($sender->hasPermission("chestshop.command.remove")){
						if(isset($args[1])){
							$category = null;
							try{
								$category = $this->chest_shop->getCategory($args[1]);
							}catch(\InvalidArgumentException $e){
								$sender->sendMessage(TextFormat::RED . $e->getMessage());
							}
							if($category !== null){
								$item = $sender->getInventory()->getItemInHand();
								if(!$item->isNull()){
									$removed = $category->removeItem($item);
									if($removed > 0){
										$sender->sendMessage(TextFormat::GREEN . "Removed {$removed} item" . ($removed > 1 ? "s" : "") . " from category {$category->getName()}!");
									}else{
										$sender->sendMessage(TextFormat::RED . "Found no occurrences of {$item->getName()}" . TextFormat::RESET . TextFormat::RED . " in category {$category->getName()}.");
									}
								}else{
									$sender->sendMessage(TextFormat::RED . "Please hold an item in your hand that you'd like to list.");
								}
							}
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
						return true;
					}
					$sender->sendMessage(TextFormat::RED . "Usage: /{$label} {$args[0]} <category>");
					return true;
				case "help":
					if($sender->hasPermission("chestshop.command.remove")){
						$sender->sendMessage(
							TextFormat::BOLD . TextFormat::GOLD . "ChestShop Commands" . TextFormat::RESET . TextFormat::EOL .
							TextFormat::YELLOW . "/{$label} addcategory <name>" . TextFormat::GRAY . " - Add a new shop category." . TextFormat::EOL .
							TextFormat::YELLOW . "/{$label} removecategory <name>" . TextFormat::GRAY . " - Remove a shop category." . TextFormat::EOL .
							TextFormat::YELLOW . "/{$label} additem <category> <price>" . TextFormat::GRAY . " - Add the item in your hand to a category." . TextFormat::EOL .
							TextFormat::YELLOW . "/{$label} removeitem <category>" . TextFormat::GRAY . " - Remove the item in your hand from a category."
						);
					}else{
						$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
						return true;
					}
					return true;
			}
		}

		$this->chest_shop->send($sender);
		return true;
	}
}