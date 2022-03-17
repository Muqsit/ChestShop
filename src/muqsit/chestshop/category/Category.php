<?php

declare(strict_types=1);

namespace muqsit\chestshop\category;

use muqsit\chestshop\database\Database;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class Category{

	private Database $database;
	private int $id;

	/** @var array<int, CategoryPage>|CategoryPage[] */
	private array $pages = [];

	public function __construct(
		private string $name,
		private Item $button
	){}

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
		if($page > 0 && $page <= count($this->pages)){
			$this->pages[$page - 1]->send($player);
			return true;
		}
		return false;
	}

	public function addEntry(CategoryEntry $entry, bool $update = true) : void{
		$page = $this->pages[count($this->pages) - 1] ?? null;
		if($page === null){
			$page = new CategoryPage();
			$page->init($this->database, $this);
			$this->pages[] = $page;
			$page->updatePageNumber($this, count($this->pages));
		}
		$page->addEntry($entry, $update);
	}

	public function rebuildPages() : void{
		$entries = [];
		foreach($this->pages as $page){
			array_push($entries, ...$page->getEntries());
		}

		foreach($this->pages as $page){
			$page->onDelete();
		}
		$this->pages = [];

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
		return $this->pages[$page - 1] ?? null;
	}

	/**
	 * @return array<int, CategoryPage>
	 */
	public function getPages() : array{
		return $this->pages;
	}
}