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
		if($this->pages->count() >= $page){
			/** @noinspection PhpUndefinedMethodInspection */
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

	public function removeItem(Item $item) : int{
		$removed = 0;
		foreach($this->pages as $page){
			if($page->removeItem($item)){
				if($page->isEmpty()){
					$this->pages->remove($page);
					$page->onDelete();
				}
				++$removed;
			}
		}
		return $removed;
	}

	public function getPage(int $page) : ?CategoryPage{
		/** @noinspection ProperNullCoalescingOperatorUsageInspection */
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->pages->get($page - 1) ?? null;
	}
}