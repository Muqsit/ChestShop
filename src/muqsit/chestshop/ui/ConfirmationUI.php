<?php

declare(strict_types=1);

namespace muqsit\chestshop\ui;

use Closure;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\chestshop\Loader;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

final class ConfirmationUI{

	/** @var string */
	private $title;

	/** @var string */
	private $body;

	/** @var ConfirmationUIButton */
	private $button_confirm;

	/** @var ConfirmationUIButton */
	private $button_cancel;

	public function __construct(Loader $loader){
		$config = $loader->getConfig()->get("confirmation-ui");
		$this->title = TextFormat::colorize($config["title"]);
		$this->body = TextFormat::colorize($config["body"]);
		$this->button_confirm = ConfirmationUIButton::fromConfig($config["buttons"]["confirm"]);
		$this->button_cancel = ConfirmationUIButton::fromConfig($config["buttons"]["cancel"]);
	}

	public function send(Player $player, array $wildcards, Closure $callback) : void{
		$form = new SimpleForm($callback);
		$form->setTitle(strtr($this->title, $wildcards));
		$form->setContent(strtr($this->body, $wildcards));
		$form->addButton(strtr($this->button_confirm->getText(), $wildcards), $this->button_confirm->getIconType(), $this->button_confirm->getIconValue());
		if($this->button_cancel->isValid()){
			$form->addButton(strtr($this->button_cancel->getText(), $wildcards), $this->button_cancel->getIconType(), $this->button_cancel->getIconValue());
		}
		$player->sendForm($form);
	}
}