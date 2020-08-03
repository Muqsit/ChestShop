<?php

declare(strict_types=1);

namespace muqsit\chestshop\database;

use Closure;
use muqsit\chestshop\category\Category;
use muqsit\chestshop\category\CategoryEntry;
use muqsit\chestshop\ChestShop;
use muqsit\chestshop\Loader;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class Database{

	/** @var DataConnector */
	private $connector;

	public function __construct(Loader $loader){
		ItemSerializer::init();

		$loader->saveResource("database.yml");

		$config = new Config($loader->getDataFolder() . "database.yml", Config::YAML);
		if(strtolower($config->getNested("database.type")) === "mysql"){
			throw new \InvalidArgumentException("{$loader->getName()} currently doesn't support MySQL.");
		}

		$this->connector = libasynql::create($loader, $config->get("database"), ["sqlite" => "db/sqlite.sql", "mysql" => "db/mysql.sql"]);

		foreach((new \ReflectionClass(DatabaseStmts::class))->getConstants() as $constant => $value){
			if(strpos($constant, "INIT_") === 0){
				$this->connector->executeGeneric($value);
			}
		}

		$this->connector->waitAll();
	}

	public function load(ChestShop $shop) : void{
		$this->connector->executeSelect(DatabaseStmts::LOAD_CATEGORIES, [], function(array $rows) use($shop) : void{
			foreach($rows as ["id" => $id, "name" => $name, "button" => $button]){
				$category = new Category($name, ItemSerializer::unserialize($button));
				$category->init($this, $id);
				$this->connector->executeSelect(DatabaseStmts::LOAD_CATEGORY_CONTENTS, ["category_id" => $category->getId()], function(array $rows2) use($shop, $category) : void{
					$slots = [];
					foreach($rows2 as ["slot" => $slot, "item" => $item, "price" => $price]){
						$slots[$slot] = new CategoryEntry(ItemSerializer::unserialize($item), $price);
					}
					ksort($slots);
					foreach($slots as $entry){
						$category->addEntry($entry, false);
					}

					$shop->addCategory($category, false);
				});
			}
		});

		$this->connector->waitAll();
	}

	public function addCategory(Category $category, Closure $callback) : void{
		$this->connector->executeInsert(DatabaseStmts::ADD_CATEGORY, [
			"name" => $category->getName(),
			"button" => bin2hex(ItemSerializer::serialize($category->getButton()))
		], function(int $insertId, int $affectedRows) use($callback) : void{
			$callback($insertId);
		});
	}

	public function removeCategory(Category $category) : void{
		$this->connector->executeInsert(DatabaseStmts::REMOVE_CATEGORY, ["id" => $category->getId()]);
	}

	public function removeCategoryContents(Category $category) : void{
		$this->connector->executeInsert(DatabaseStmts::REMOVE_CATEGORY_CONTENTS, ["category_id" => $category->getId()]);
	}

	public function addToCategory(Category $category, int $index, CategoryEntry $entry) : void{
		$this->connector->executeInsert(DatabaseStmts::ADD_CATEGORY_CONTENT, [
			"category_id" => $category->getId(),
			"slot" => $index,
			"item" => bin2hex(ItemSerializer::serialize($entry->getItem())),
			"price" => $entry->getPrice()
		]);
	}

	public function removeFromCategory(Category $category, int $index) : void{
		$this->connector->executeChange(DatabaseStmts::REMOVE_CATEGORY_CONTENT, [
			"category_id" => $category->getId(),
			"slot" => $index
		]);
	}

	public function close() : void{
		$this->connector->close();
	}
}