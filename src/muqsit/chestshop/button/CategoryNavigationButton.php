<?php

declare(strict_types=1);

namespace muqsit\chestshop\button;

use muqsit\chestshop\category\Category;
use pocketmine\player\Player;

interface CategoryNavigationButton{

	public function navigate(Player $player, Category $category, int $current_page) : void;
}