<?php

declare(strict_types=1);

namespace muqsit\chestshop\util;

use pocketmine\player\Player;
use Ramsey\Uuid\UuidInterface;

final class PlayerIdentity{

	public static function fromPlayer(Player $player) : self{
		return new self($player->getUniqueId(), $player->getName());
	}

	public function __construct(
		private UuidInterface $uuid,
		private string $gamertag
	){}

	public function getUuid() : UuidInterface{
		return $this->uuid;
	}

	public function getGamertag() : string{
		return $this->gamertag;
	}
}