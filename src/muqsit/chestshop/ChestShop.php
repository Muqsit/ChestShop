<?php

declare(strict_types=1);

namespace muqsit\chestshop;

use muqsit\chestshop\button\ButtonFactory;
use muqsit\chestshop\button\ButtonIds;
use muqsit\chestshop\button\CategoryButton;
use muqsit\chestshop\category\Category;
use muqsit\chestshop\database\Database;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

final class ChestShop{

	/** @var Database */
	private $database;

	/** @var SharedInvMenu */
	private $menu;

	/** @var Category[] */
	private $categories = [];

	public function __construct(Database $database){
		$this->database = $database;
		$this->menu = InvMenu::create(InvMenu::TYPE_CHEST)
			->readonly()
			->setName("Categories")
			->setListener(function(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : void{
				$button = ButtonFactory::fromItem($itemClicked);
				if($button instanceof CategoryButton){
					$category = null;
					try{
						$category = $this->getCategory($button->getCategory());
					}catch(\InvalidArgumentException $e){
						$player->sendMessage(TextFormat::RED . $e->getMessage());
					}

					if($category !== null && !$category->send($player)){
						$player->sendMessage(TextFormat::RED . "This category is empty.");
						$player->removeWindow($action->getInventory());
					}
				}
			});
	}

	public function addCategory(Category $category, bool $update = true) : void{
		if(isset($this->categories[$name = $category->getName()])){
			throw new \InvalidArgumentException("A category with the name " . $name . " already exists.");
		}

		$this->categories[$name] = $category;
		$this->menu->getInventory()->addItem(ButtonFactory::get(ButtonIds::CATEGORY, $category->getName(), $category->getButton()));

		if($update){
			$this->database->addCategory($category, function(int $id) use($category) : void{
				$category->init($this->database, $id);
			});
		}
	}

	public function removeCategory(string $name) : void{
		if(!isset($this->categories[$name])){
			throw new \InvalidArgumentException("No category with the name " . $name . " exists.");
		}

		$category = $this->categories[$name];
		unset($this->categories[$name]);

		$inventory = $this->menu->getInventory();
		$contents = $inventory->getContents();
		foreach($contents as $slot => $item){
			$button = ButtonFactory::fromItem($item);
			if($button instanceof CategoryButton && $button->getCategory() === $name){
				unset($contents[$slot]);
			}
		}

		$inventory->setContents($contents);
		$this->database->removeCategory($category);
	}

	public function getCategory(string $name) : Category{
		if(!isset($this->categories[$name])){
			throw new \InvalidArgumentException("No category with the name " . $name . " exists.");
		}

		return $this->categories[$name];
	}

	public function send(Player $player) : void{
		$this->menu->send($player);
	}
}