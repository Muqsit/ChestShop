<?php

declare(strict_types=1);

namespace muqsit\chestshop\category;

use Ds\Set;
use muqsit\chestshop\button\ButtonFactory;
use muqsit\chestshop\button\ButtonIds;
use muqsit\chestshop\button\CategoryNavigationButton;
use muqsit\chestshop\database\Database;
use muqsit\chestshop\economy\EconomyManager;
use muqsit\chestshop\Loader;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use OutOfRangeException;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class CategoryPage{

	public const MAX_ENTRIES_PER_PAGE = 45;

	/** @var Database */
	private $database;

	/** @var InvMenu */
	private $menu;

	/** @var Category */
	private $category;

	/** @var Set<CategoryEntry>|CategoryEntry[] */
	private $entries;

	/** @var int */
	private $page;

	/**
	 * @param CategoryEntry[] $entries
	 */
	public function __construct(array $entries = []){
		$this->entries = new Set($entries);
	}

	/**
	 * @return Set<CategoryEntry>
	 */
	public function getEntries(){
		return $this->entries;
	}

	public function getPage() : int{
		return $this->page;
	}

	public function init(Database $database, Category $category) : void{
		$this->database = $database;
		$this->menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$this->category = $category;

		/** @var Loader $loader */
		$loader = Server::getInstance()->getPluginManager()->getPlugin("ChestShop");

		if(CategoryConfig::getBool(CategoryConfig::BACK_TO_CATEGORIES)){
			$this->menu->setInventoryCloseListener(static function(Player $player, Inventory $inventory) use($loader) : void{
				static $shop = null;
				if($shop === null){
					$shop = $loader->getChestShop();
				}
				$shop->send($player);
			});
		}

		$this->menu->getInventory()->setContents([
			48 => ButtonFactory::get(ButtonIds::TURN_LEFT, $category->getName()),
			49 => ButtonFactory::get(ButtonIds::CATEGORIES),
			50 => ButtonFactory::get(ButtonIds::TURN_RIGHT, $category->getName())
		]);

		$confirmation_ui = $loader->getConfirmationUi();
		$this->menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use($confirmation_ui) : void{
			$player = $transaction->getPlayer();
			$action = $transaction->getAction();

			$slot = $action->getSlot();
			$entry = $this->getPurchasableEntry($slot);

			if($entry !== null){
				if($confirmation_ui !== null){
					$item = $entry->getItem();

					$wildcards = [
						"{NAME}" => $item->getName(),
						"{COUNT}" => (string) $item->getCount(),
						"{PRICE}" => (string) $entry->getPrice(),
						"{PRICE_FORMATTED}" => EconomyManager::get()->formatMoney($entry->getPrice()),
						"{CATEGORY}" => $this->category->getName(),
						"{PAGE}" => (string) $this->page
					];

					$callback = function(Player $player, $data) use($slot, $entry) : void{
						if($data === 0 && $this->getPurchasableEntry($slot) === $entry){
							$this->attemptPurchase($player, $entry);
						}
						$this->send($player);
					};

					$player->removeWindow($action->getInventory());
					$transaction->then(static function(Player $player) use($confirmation_ui, $wildcards, $callback) : void{
						$confirmation_ui->send($player, $wildcards, $callback);
					});
				}else{
					$this->attemptPurchase($player, $entry);
				}
			}else{
				$button = ButtonFactory::fromItem($transaction->getItemClicked());
				if($button instanceof CategoryNavigationButton){
					$button->navigate($player, $this->category, $this->page);
				}
			}
		}));
	}

	private function getPurchasableEntry(int $slot) : ?CategoryEntry{
		$entry = null;
		try{
			/** @var CategoryEntry $entry */
			$entry = $this->entries->get($slot);
		}catch(OutOfRangeException $e){
		}
		return $entry;
	}

	private function attemptPurchase(Player $player, CategoryEntry $entry) : void{
		$price = $entry->getPrice();
		$economy = EconomyManager::get();
		$money = $economy->getMoney($player);
		if($money >= $price){
			$economy->removeMoney($player, $price);
			foreach($player->getInventory()->addItem($entry->getItem()) as $item){
				$player->getLevel()->dropItem($player, $item);
			}
			$player->sendMessage(strtr(CategoryConfig::getString(CategoryConfig::PURCHASE_MESSAGE), [
				"{PLAYER}" => $player->getName(),
				"{PRICE}" => $price,
				"{PRICE_FORMATTED}" => $economy->formatMoney($price),
				"{ITEM}" => $entry->getItem()->getName(),
				"{COUNT}" => $entry->getItem()->getCount()
			]));
		}else{
			$player->sendMessage(strtr(CategoryConfig::getString(CategoryConfig::NOT_ENOUGH_MONEY_MESSAGE), [
				"{PLAYER}" => $player->getName(),
				"{PRICE}" => $price,
				"{PRICE_FORMATTED}" => $economy->formatMoney($price),
				"{MONEY}" => $money,
				"{MONEY_FORMATTED}" => $economy->formatMoney($money),
				"{ITEM}" => $entry->getItem()->getName(),
				"{COUNT}" => $entry->getItem()->getCount()
			]));
		}
	}

	public function updatePageNumber(Category $category, int $page) : void{
		$this->page = $page;
		$this->menu->setName(strtr(CategoryConfig::getString(CategoryConfig::TITLE), [
			"{NAME}" => $category->getName(),
			"{PAGE}" => $page
		]));
	}

	public function addEntry(CategoryEntry $entry, bool $update) : void{
		if(count($this->entries) === self::MAX_ENTRIES_PER_PAGE){
			throw new \OverflowException("Cannot add more than " . self::MAX_ENTRIES_PER_PAGE . " entries to a page.");
		}

		$slot = $this->entries->count();
		$this->entries->add($entry);

		$item = clone $entry->getItem();

		$find = ["{NAME}", "{COUNT}", "{PRICE}", "{PRICE_FORMATTED}", "{CATEGORY}", "{PAGE}"];
		$replace = [$item->getName(), $item->getCount(), $entry->getPrice(), EconomyManager::get()->formatMoney($entry->getPrice()), $this->category->getName(), $this->page];

		$item->setCustomName(str_replace($find, $replace, CategoryConfig::getString(CategoryConfig::ITEM_BUTTON_NAME)));
		$lore = str_replace($find, $replace, CategoryConfig::getStringList(CategoryConfig::ITEM_BUTTON_LORE_VALUE));
		switch(CategoryConfig::getString(CategoryConfig::ITEM_BUTTON_LORE_TYPE)){
			case "push":
				$new = $item->getLore();
				array_push($new, ...$lore);
				$item->setLore($new);
				break;
			case "unshift":
				$new = $item->getLore();
				array_unshift($new, ...$lore);
				$item->setLore($new);
				break;
			case "override":
				$item->setLore($lore);
				break;
		}

		$this->menu->getInventory()->setItem($slot, $item);
		if($update){
			$this->database->addToCategory($this->category, $this->getOffset() + $slot, $entry);
		}
	}

	public function isEmpty() : bool{
		return count($this->entries) === 0;
	}

	public function removeItem(Item $item) : bool{
		foreach($this->entries as $slot => $entry){
			if($entry->getItem()->equals($item)){
				$this->removeSlot($slot);
				return true;
			}
		}
		return false;
	}

	private function getOffset() : int{
		return ($this->page - 1) * self::MAX_ENTRIES_PER_PAGE;
	}

	public function removeSlot(int $slot) : void{
		$this->entries->remove($this->entries->get($slot));
		$inventory = $this->menu->getInventory();
		for($item_slot = $slot + 1; $item_slot < self::MAX_ENTRIES_PER_PAGE; ++$item_slot){
			$inventory->setItem($item_slot - 1, $inventory->getItem($item_slot));
		}
	}

	public function send(Player $player) : void{
		$this->menu->send($player);
	}

	public function onDelete() : void{
		$inventory = $this->menu->getInventory();
		foreach($inventory->getViewers() as $viewer){
			$viewer->sendMessage(TextFormat::GRAY . "The page you were viewing is no longer available.");
		}
		$inventory->removeAllViewers();
	}
}