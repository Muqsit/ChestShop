<?php
namespace ChestShop;

use pocketmine\{Player, Server};
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat as TF;

class RemoveByIdTask extends AsyncTask{

	/*
	 * $data = [
	 * 	(string) (playername)
	 * 	(int) (itemid)
	 * 	(int) (itemdamage)
	 * 	(int) (assoc array)
	 * ];
	 */

	/** @var Volatile */
	private $data;

	public function __construct(array $data){
		$this->data = $data;
	}

	public function onRun() : void{
		$res = [];
		foreach($this->data[3] as $k => $v){
			if($v[0] == $this->data[1] && $v[1] == $this->data[2]){
				$res[] = $k;
			}
		}
		$this->setResult($res);
	}

	public function onCompletion(Server $server) : void{
		$res = $this->getResult();
		if(($player = $server->getPlayerExact($this->data[0])) instanceof Player){
			$player->sendMessage(Main::PREFIX.TF::YELLOW.count($res).' items were removed off auction house (ID: '.$this->data[1].', DAMAGE: '.$this->data[2].').');
		}
		$server->getPluginManager()->getPlugin("ChestShop")->removeItemsByKey(...$res);
	}
}