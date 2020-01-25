<?php

declare(strict_types=1);

namespace muqsit\chestshop;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ChestShop extends PluginBase{

	/** @var EventHandler */
	private $eventHandler;

	/** @var Category[] */
	private $categories = [];

	/** @var bool */
	private $crashed = false;

	/** @var SharedInvMenu */
	private $menu;

	/** @var Config */
	private $buttonsConfig;

	public function onEnable() : void{
		if(!is_dir($this->getDataFolder())){
			/** @noinspection MkdirRaceConditionInspection */
			mkdir($this->getDataFolder());
		}

		$this->saveResource("config.yml");
		$this->saveResource("buttons.yml");

		$config = $this->getConfig();

		$this->eventHandler = new EventHandler($this, $config->get("double-tapping", false));
		$this->buttonsConfig = new Config($this->getDataFolder() . "buttons.yml");

		$buttons = $this->getButtonsConfig()->get("buttons");
		if(is_array($buttons)){
			Button::setOptions($buttons);
		}

		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}

		$this->menu = InvMenu::create(InvMenu::TYPE_CHEST)->readonly()->setName("Choose A Category...")->setListener([$this->eventHandler, "handleCategoryChoosing"])->setInventoryCloseListener([$this->eventHandler, "handlePageCacheRemoval"]);

		try{
			$this->loadShops();
		}catch(\Throwable $t){
			$this->crashed = true;//don't know if this is the best way to avoid data loss
			throw $t;
		}
	}

	public function onDisable() : void{
		if(!$this->crashed){
			$this->saveShops();
			$this->getButtonsConfig()->set("buttons", Button::getOptions());
			$this->getButtonsConfig()->save();
		}
	}

	private function getButtonsConfig() : Config{
		return $this->buttonsConfig;
	}

	private function loadShops() : void{
		$file = $this->getDataFolder() . "shops.dat";

		if(is_file($file)){
			$raw = file_get_contents($file);
			if(!empty($raw)){
				$cats = (new BigEndianNBTStream())->readCompressed($raw)->getListTag("Categories");
				foreach($cats as $tag){
					$this->setCategory(Category::nbtDeserialize($this, $tag));
				}
			}
		}
	}

	private function saveShops() : void{
		$tag = new ListTag("Categories");

		foreach($this->categories as $category){
			$tag->push($category->nbtSerialize());
		}

		file_put_contents($this->getDataFolder() . "shops.dat", (new BigEndianNBTStream())->writeCompressed(new CompoundTag("", [$tag])));
	}

	public function getEventHandler() : EventHandler{
		return $this->eventHandler;
	}

	public function addCategory(string $name, Item $identifier) : bool{
		if(isset($this->categories[strtolower(TextFormat::clean($name))])){
			return false;
		}

		$this->setCategory(new Category($this, $name, $identifier));
		return true;
	}

	private function setCategory(Category $category) : void{
		$this->categories[strtolower($category->getRealName())] = $category;
		$this->menu->getInventory()->addItem($category->getIdentifier());
	}

	public function getCategory(string $category) : ?Category{
		return $this->categories[strtolower($category)] ?? null;
	}

	public function removeCategory(string $name) : bool{
		$category = $this->getCategory($name);
		if($category === null){
			return false;
		}

		unset($this->categories[strtolower(TextFormat::clean($name))]);
		$this->menu->getInventory()->removeItem($category->getIdentifier());
		return true;
	}

	public function send(Player $player) : void{
		$this->menu->send($player);
	}

	public function sendCategory(Player $player, string $category, int $page = 1, bool $send = true){
		$category = $this->getCategory($category);
		if($category === null){
			return false;
		}

		return $category->send($player, $page, $send);
	}

	public static function toOriginalItem(Item $item) : void{
		$item->removeNamedTagEntry("ChestShop");

		$lore = $item->getLore();
		array_pop($lore);
		$item->setLore($lore);
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "This command can only be executed as a player.");
			return true;
		}

		if(empty($args)){
			$this->menu->send($sender);
			return true;
		}

		switch($args[0]){
			case "addcat":
			case "addcategory":
				if($sender->hasPermission("chestshop.command.admin")){
					if(!isset($args[1])){
						$sender->sendMessage(TextFormat::RED . "/cs addcategory <name>");
						return false;
					}

					$item = $sender->getInventory()->getItemInHand();
					if($item->isNull()){
						$sender->sendMessage(TextFormat::RED . "Please hold an item in your hand. That item will be used as a button in the /{$label} GUI.");
						return false;
					}

					$name = implode(" ", array_slice($args, 1));

					if(!$this->addCategory($name, $item)){
						$sender->sendMessage(TextFormat::RED . "A category named " . TextFormat::clean($name) . " already exists, please choose a new name.");
						return false;
					}

					$sender->sendMessage(TextFormat::GREEN . "Successfully created category {$name}, use /cs to view it.");
					return true;
				}
				break;
			case "removecat":
			case "removecategory":
				if($sender->hasPermission("chestshop.command.admin")){
					if(!isset($args[1])){
						$sender->sendMessage(TextFormat::RED . "/cs removecategory <name>");
						return false;
					}

					$name = implode(" ", array_slice($args, 1));

					if(!$this->removeCategory($name)){
						$sender->sendMessage(TextFormat::RED . "No category named " . TextFormat::clean($name) . " could be found.");
						return false;
					}

					$sender->sendMessage(TextFormat::GREEN . "Successfully removed category {$name}.");
					return true;
				}
				break;
			case "categories":
				if($sender->hasPermission("chestshop.command.admin")){
					foreach($this->categories as $category){
						$sender->sendMessage($category->getName());
					}
					return true;
				}
				break;
			case "additem":
				if($sender->hasPermission("chestshop.command.admin")){
					if(!isset($args[1])){
						$sender->sendMessage(TextFormat::RED . "/cs additem <category> <price>");
						return false;
					}

					$category = $this->getCategory($args[1]);
					if($category === null){
						$sender->sendMessage(TextFormat::RED . "No category named " . TextFormat::clean($args[1]) . " could be found.");
						return false;
					}

					$item = $sender->getInventory()->getItemInHand();
					if($item->isNull()){
						$sender->sendMessage(TextFormat::RED . "Please hold an item in your hand.");
						return false;
					}

					if(isset($args[2]) && is_numeric($args[2]) && $args[2] >= 0){
						$category->addItem($item, (float) $args[2]);
						$sender->sendMessage(TextFormat::YELLOW . "Added " . $item->getName() . " to category '" . $category->getName() . TextFormat::RESET . TextFormat::YELLOW . "' for \${$args[2]}.");
						return true;
					}

					$sender->sendMessage(TextFormat::RED . "Please enter a valid number.");
					return false;
				}
				break;
		}

		if($sender->hasPermission("chestshop.command.admin")){
			$sender->sendMessage(
				TextFormat::YELLOW . TextFormat::BOLD . "ChestShop v" . $this->getDescription()->getVersion() . TextFormat::RESET . TextFormat::EOL .
				TextFormat::GOLD . "/" . $label . " " . TextFormat::GRAY . "addcat/addcategory <name> - Add a category named <name> in /" . $label . TextFormat::EOL .
				TextFormat::GOLD . "/" . $label . " " . TextFormat::GRAY . "removecat/removecategory <name> - Remove category <name> from /" . $label . TextFormat::EOL .
				TextFormat::GOLD . "/" . $label . " " . TextFormat::GRAY . "categories - List all categories" . TextFormat::EOL .
				TextFormat::GOLD . "/" . $label . " " . TextFormat::GRAY . "additem <category> <price> - Add held item to <category> for <price>");
			return true;
		}

		return false;
	}
}
