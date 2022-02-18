<?php

declare(strict_types=1);

namespace muqsit\chestshop\util;

use pocketmine\player\Player;
use pocketmine\Server;
use Ramsey\Uuid\UuidInterface;
use function spl_object_id;

final class WeakPlayer{

	public static function fromPlayer(Player $player) : self{
		return new self($player->getUniqueId(), spl_object_id($player));
	}

	public function __construct(
		private UuidInterface $uuid,
		private ?int $object_id = null
	){}

	public function get() : ?Player{
		$player = Server::getInstance()->getPlayerByUUID($this->uuid);
		return $player !== null && $player->isConnected() && ($this->object_id === null || spl_object_id($player) === $this->object_id) ? $player : null;
	}
}