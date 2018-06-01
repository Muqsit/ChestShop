<?php
namespace muqsit\chestshop;

use onebone\economyapi\EconomyAPI;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class EventHandler implements Listener{

	/** @var ChestShop */
	private $plugin;

	/** @var EconomyAPI */
	private $economy;

	/** @var int[]|null */
	private $doubleclicks;

	/** @var int[] */
	private $currentpage = [];

	public function __construct(ChestShop $plugin, bool $enableDoubleClicks = false){
		$this->plugin = $plugin;
		$this->economy = EconomyAPI::getInstance();

		if($enableDoubleClicks){
			$this->doubleclicks = [];
		}
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function handlePlayerQuit(PlayerQuitEvent $event) : void{
		$playerId = $event->getPlayer()->getId();
		unset($this->doubleclicks[$playerId], $this->currentpage[$playerId]);
	}

	public function handlePageCacheRemoval(Player $player) : bool{
		unset($this->currentpage[$player->getId()]);
		return true;
	}

	public function handleTransaction(Player $player, Item $itemClicked, Item $itemClickedUsing, SlotChangeAction $inventoryAction) : bool{
		$nbt = $itemClicked->getNamedTag();

		if($nbt->hasTag("Button")){
			$currentpage = $this->currentpage[$playerId = $player->getId()] ?? 1;
			switch($nbt->getByte("Button")){
				case Button::TURN_LEFT:
					$this->currentpage[$playerId] = $this->plugin->sendCategory($player, $category = $nbt->getString("Category"), --$currentpage, false);
					if($this->currentpage[$playerId] === false){
						$player->removeWindow($inventoryAction->getInventory());
						$player->sendMessage(TF::RED."Could not find category '".$category."', perhaps it has been removed.");
					}
					break;
				case Button::TURN_RIGHT:
					$this->currentpage[$playerId] = $this->plugin->sendCategory($player, $category = $nbt->getString("Category"), ++$currentpage, false);
					if($this->currentpage[$playerId] === false){
						$player->removeWindow($inventoryAction->getInventory());
						$player->sendMessage(TF::RED."Could not find category '".$category."', perhaps it has been removed.");
					}
					break;
				case Button::CATEGORIES:
					$player->removeWindow($inventoryAction->getInventory());
					$this->plugin->send($player, 5);
					break;
			}
			return true;
		}

		if($nbt->hasTag("ChestShop")){
			$cost = $nbt->getFloat("ChestShop");

			if($this->economy->myMoney($player) < $cost){
				$player->sendMessage(TF::RED."You do not have enough money to purchase ".$itemClicked->getName().".");
				$player->removeWindow($inventoryAction->getInventory());
				return true;
			}

			if($this->doubleclicks !== null){
				if(
					!isset($this->doubleclicks[$playerId = $player->getId()]) ||
					$player->ticksLived - $this->doubleclicks[$playerId] > 15//15 tick delay for double taps, probably make this a configurable number
				){
					//maybe add a popup or a message here regarding double tapping?
					$this->doubleclicks[$playerId] = $player->ticksLived;
					return true;
				}
				unset($this->doubleclicks[$playerId]);
			}

			ChestShop::toOriginalItem($itemClicked);

			$level = $player->getLevel();
			foreach($player->getInventory()->addItem($itemClicked) as $item){
				$level->dropItem($player, $item);
			}

			$player->sendMessage(TF::YELLOW."Purchased ".$itemClicked->getName()." (x".$itemClicked->getCount().")".TF::RESET.TF::YELLOW." for \$".TF::RESET.TF::GOLD.$cost);
			$this->economy->reduceMoney($player, $cost);
		}

		return true;
	}

	public function handleCategoryChoosing(Player $player, Item $itemClicked, Item $itemClickedUsing, SlotChangeAction $inventoryAction) : bool{
		$nbt = $itemClicked->getNamedTag();

		if($nbt->hasTag("Category")){
			$player->removeWindow($inventoryAction->getInventory());
			if(!$this->plugin->sendCategory($player, $category = $nbt->getString("Category"))){
				$player->sendMessage(TF::RED."Could not find category '".$category."', perhaps it has been removed.");
			}
		}
		return true;
	}
}