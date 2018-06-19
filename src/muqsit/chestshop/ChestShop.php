<?php
namespace muqsit\chestshop;

use muqsit\chestshop\tasks\DelayedInvMenuSendTask;
use muqsit\invmenu\{InvMenu, InvMenuHandler};

use pocketmine\command\{Command, CommandSender};
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\{CompoundTag, ListTag};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\{Config, TextFormat as TF};

class ChestShop extends PluginBase{

	/** @var EventHandler */
	private $eventHandler;

	/** @var Category[] */
	private $categories = [];

	/** @var bool */
	private $crashed = false;

	/** @var InvMenu */
	private $menu;

	/** @var Config */
	private $buttonsConfig;

	public function onEnable() : void{
		if(!is_dir($this->getDataFolder())){
			mkdir($this->getDataFolder());
		}

		$this->saveResource("config.yml");
		$this->saveResource("buttons.yml");

		$config = $this->getConfig();

		$this->eventHandler = new EventHandler($this, $config->get("double-tapping", false));
		$this->buttonsConfig = new Config($this->getDataFolder()."buttons.yml");

		$buttons = $this->getButtonsConfig()->get("buttons");
		if(is_array($buttons)){
			Button::setOptions($buttons);
		}

		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}

		$this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$this->menu
			->readonly()
			->setName("Choose A Category...")
			->setListener([$this->eventHandler, "handleCategoryChoosing"])
			->setInventoryCloseListener([$this->eventHandler, "handlePageCacheRemoval"]);

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
		$file = $this->getDataFolder()."shops.dat";

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

		file_put_contents($this->getDataFolder()."shops.dat", (new BigEndianNBTStream())->writeCompressed(new CompoundTag("", [$tag])));
	}

	public function getEventHandler() : EventHandler{
		return $this->eventHandler;
	}

	public function addCategory(string $name, Item $identifier) : bool{
		if(isset($this->categories[strtolower(TF::clean($name))])){
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

		unset($this->categories[strtolower(TF::clean($name))]);
		$this->menu->getInventory()->removeItem($category->getIdentifier());
		return true;
	}

	public function send(Player $player, int $delay = 0) : void{
		if($delay > 0){
			$this->getScheduler()->scheduleDelayedTask(new DelayedInvMenuSendTask($this, $player, $this->menu), $delay);
			return;
		}

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
		if(empty($args)){
			$this->menu->send($sender);
			return true;
		}

		switch($args[0]){
			case "addcat":
			case "addcategory":
				if($sender->hasPermission("chestshop.command.admin")){
					if(!isset($args[1])){
						$sender->sendMessage(TF::RED."/cs addcategory <name>");
						return false;
					}

					$item = $sender->getInventory()->getItemInHand();
					if($item->isNull()){
						$sender->sendMessage(TF::RED."Please hold an item in your hand. That item will be used as a button in the /{$label} GUI.");
						return false;
					}

					if(!$this->addCategory($args[1], $item)){
						$sender->sendMessage(TF::RED."A category named ".TF::clean($args[1])." already exists, please choose a new name.");
						return false;
					}

					$sender->sendMessage(TF::GREEN."Successfully created category {$args[1]}, use /cs to view it.");
					return true;
				}
				break;
			case "removecat":
			case "removecategory":
				if($sender->hasPermission("chestshop.command.admin")){
					if(!isset($args[1])){
						$sender->sendMessage(TF::RED."/cs removecategory <name>");
						return false;
					}

					if(!$this->removeCategory($args[1])){
						$sender->sendMessage(TF::RED."No category named ".TF::clean($args[1])." could be found.");
						return false;
					}

					$sender->sendMessage(TF::GREEN."Successfully removed category {$args[1]}.");
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
						$sender->sendMessage(TF::RED."/cs additem <category> <price>");
						return false;
					}

					$category = $this->getCategory($args[1]);
					if($category === null){
						$sender->sendMessage(TF::RED."No category named ".TF::clean($args[1])." could be found.");
						return false;
					}

					$item = $sender->getInventory()->getItemInHand();
					if($item->isNull()){
						$sender->sendMessage(TF::RED."Please hold an item in your hand.");
						return false;
					}

					if(isset($args[2]) && is_numeric($args[2]) && $args[2] >= 0) {
						$category->addItem($item, $args[2]);
						$sender->sendMessage(TF::YELLOW."Added ".$item->getName()." to category '".$category->getName().TF::RESET.TF::YELLOW."' for \${$args[2]}.");
						return true;
					}

					$sender->sendMessage(TF::RED."Please enter a valid number.");
					return false;
				}
				break;
		}

		if($sender->hasPermission("chestshop.command.admin")){
			$sender->sendMessage(
				TF::YELLOW.TF::BOLD."ChestShop v".$this->getDescription()->getVersion().TF::RESET."\n".
				TF::GOLD."/".$label." ".TF::GRAY."addcat/addcategory <name> - Add a category named <name> in /".$label."\n".
				TF::GOLD."/".$label." ".TF::GRAY."removecat/removecategory <name> - Remove category <name> from /".$label."\n".
				TF::GOLD."/".$label." ".TF::GRAY."categories - List all categories\n".
				TF::GOLD."/".$label." ".TF::GRAY."additem <category> <price> - Add held item to <category> for <price>"
			);
			return true;
		}

		return false;
	}
}
