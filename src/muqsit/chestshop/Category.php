<?php
namespace muqsit\chestshop;

use muqsit\invmenu\InvMenu;

use pocketmine\item\Item;
use pocketmine\nbt\tag\{CompoundTag, FloatTag, ListTag, StringTag};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class Category{

	/** @var string */
	private $name;

	/** @var Item */
	private $identifier;

	/** @var Item[] */
	private $items = [];

	public function __construct(ChestShop $plugin, string $name, Item $identifier){
		$this->name = $name;
		$this->identifier = $identifier;

		$this->menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);

		$this->menu
			->readonly()
			->sessionize()
			->setName($name)
			->setListener([$plugin->getEventHandler(), "handleTransaction"])
			->setInventoryCloseListener([$plugin->getEventHandler(), "handlePageCacheRemoval"]);
	}

	public function getRealName() : string{
		return TF::clean($this->name);
	}

	public function getName() : string{
		return $this->name;
	}

	public function getIdentifier() : Item{
		$item = clone $this->identifier;
		$item->setCustomName(TF::RESET.TF::BOLD.TF::AQUA.$this->name);
		$item->setLore([TF::RESET.TF::GRAY."Click to view this category."]);
		$item->setNamedTagEntry(new StringTag("Category", $this->getRealName()));
		return $item;
	}

	public function getContents(int $page = 1) : array{
		//45 = 54 - 9. 54 is the number of slots of a double chest GUI
		//We are removing the last 9 slots to make space for the "Turn Left"
		//and "Turn Right" buttons.

		$page = min(ceil(count($this->items) / 45), max(1, $page));//page > 0 and page <= number of pages.
		$contents = array_slice($this->items, ($page - 1) * 45, 45);//get the 45 items on this page.
		$inventory = $this->menu->getInventory($player);
	}

	public function addItem(Item $item, float $cost) : void{
		$item->setNamedTagEntry(new FloatTag("ChestShop", $cost));

		$lore = $item->getLore();
		$lore[] = TF::RESET.TF::YELLOW.TF::BOLD."COST: \$".TF::RESET.TF::GOLD.sprintf("%.2f", $cost);
		$item->setLore($lore);

		$this->items[] = $item;
	}

	final private function setContents(array $items) : void{
		$this->items = $items;
	}

	public function send(Player $player, int $page = 1, bool $send = true) : int{
		if(!empty($this->items)){

			//45 = 54 - 9. 54 is the number of slots of a double chest GUI
			//We are removing the last 9 slots to make space for the "Turn Left"
			//and "Turn Right" buttons.

			$page = min(ceil(count($this->items) / 45), max(1, $page));//page > 0 and page <= number of pages.
			$contents = array_slice($this->items, ($page - 1) * 45, 45);//get the 45 items on this page.

			$contents[48] = Button::get(Button::TURN_LEFT, $this->getRealName());
			$contents[49] = Button::get(Button::CATEGORIES);
			$contents[50] = Button::get(Button::TURN_RIGHT, $this->getRealName());

			$this->menu->getInventory($player)->setContents($contents);
		}

		if($send){
			$this->menu->send($player);
		}

		$this->menu->getInventory($player)->sendContents($player);
		return $page;
	}

	public function nbtSerialize() : CompoundTag{
		$tag = new CompoundTag();
		$tag->setString("name", $this->name);
		$tag->setTag($this->identifier->nbtSerialize(-1, "identifier"));

		$items = [];
		foreach($this->items as $item){
			$items[] = $item->nbtSerialize();
		}

		$tag->setTag(new ListTag("items", $items));
		return $tag;
	}

	public static function nbtDeserialize(ChestShop $plugin, CompoundTag $tag) : Category{
		$name = $tag->getString("name");
		$identifier = Item::nbtDeserialize($tag->getCompoundTag("identifier"));

		$items = [];
		foreach($tag->getListTag("items") as $itemNBT){
			$items[] = Item::nbtDeserialize($itemNBT);
		}

		$category = new Category($plugin, $name, $identifier);
		$category->setContents($items);
		return $category;
	}
}