<?php

declare(strict_types=1);

namespace muqsit\chestshop\category;

use Ds\Set;
use muqsit\chestshop\database\Database;
use OverflowException;
use pocketmine\item\Item;
use pocketmine\Player;
use UnderflowException;

final class Category{

	/** @var Database */
	private $database;

	/** @var string */
	private $name;

	/** @var int */
	private $id;

	/** @var Item */
	private $button;

	/** @var Set<CategoryPage>|CategoryPage[] */
	private $pages;

	public function __construct(string $name, Item $button){
		$this->name = $name;
		$this->button = $button;
		$this->pages = new Set();
	}

	public function init(Database $database, int $id) : void{
		$this->database = $database;
		$this->id = $id;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getButton() : Item{
		return $this->button;
	}

	public function getName() : string{
		return $this->name;
	}

	public function send(Player $player, int $page = 1) : bool{
		if($page > 0 && $page <= $this->pages->count()){
			/** @noinspection NullPointerExceptionInspection */
			$this->pages->get($page - 1)->send($player);
			return true;
		}
		return false;
	}

	public function addEntry(CategoryEntry $entry, bool $update = true) : void{
		try{
			/** @var CategoryPage $page */
			$page = $this->pages->last();
			$page->addEntry($entry, $update);
		}catch(OverflowException | UnderflowException $e){
			$page = new CategoryPage();
			$page->init($this->database, $this);
			$this->pages->add($page);
			$page->updatePageNumber($this, $this->pages->count());
			$page->addEntry($entry, $update);
		}
	}

	public function rebuildPages() : void{
		$entries = [];
		foreach($this->pages as $page){
			array_push($entries, ...$page->getEntries()->toArray());
		}

		foreach($this->pages as $page){
			$page->onDelete();
		}
		$this->pages->clear();

		$this->database->removeCategoryContents($this);
		foreach($entries as $entry){
			$this->addEntry($entry);
		}
	}

	public function removeItem(Item $item) : int{
		$removed = 0;
		foreach($this->pages as $page){
			if($page->removeItem($item)){
				++$removed;
			}
		}
		if($removed > 0){
			$this->rebuildPages();
		}
		return $removed;
	}

	public function getPage(int $page) : ?CategoryPage{
		/** @noinspection ProperNullCoalescingOperatorUsageInspection */
		return $this->pages->get($page - 1) ?? null;
	}

	/**
	 * @return Set<CategoryPage>
	 */
	public function getPages(){
		return $this->pages;
	}
}