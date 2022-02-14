<?php

declare(strict_types=1);

namespace muqsit\chestshop\ui;

use Closure;
use dktapps\pmforms\FormIcon;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use muqsit\chestshop\Loader;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ConfirmationUI{

	private string $title;
	private string $body;
	private ConfirmationUIButton $button_confirm;
	private ConfirmationUIButton $button_cancel;

	public function __construct(Loader $loader){
		$config = $loader->getConfig()->get("confirmation-ui");
		$this->title = TextFormat::colorize($config["title"]);
		$this->body = TextFormat::colorize($config["body"]);
		$this->button_confirm = ConfirmationUIButton::fromConfig($config["buttons"]["confirm"]);
		$this->button_cancel = ConfirmationUIButton::fromConfig($config["buttons"]["cancel"]);
	}

	/**
	 * @param Player $player
	 * @param string[] $wildcards
	 * @param Closure $callback
	 *
	 * @phpstan-param array<string, string> $wildcards
	 */
	public function send(Player $player, array $wildcards, Closure $callback) : void{
		$options = [new MenuOption(strtr($this->button_confirm->getText(), $wildcards), new FormIcon($this->button_confirm->getIconValue(), $this->button_confirm->getIconType()))];
		if($this->button_cancel->isValid()){
			$options[] = new MenuOption(strtr($this->button_cancel->getText(), $wildcards), new FormIcon($this->button_cancel->getIconValue(), $this->button_cancel->getIconType()));
		}
		$player->sendForm(new MenuForm(strtr($this->title, $wildcards), strtr($this->body, $wildcards), $options, $callback));
	}
}