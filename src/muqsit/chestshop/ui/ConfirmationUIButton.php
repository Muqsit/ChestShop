<?php

declare(strict_types=1);

namespace muqsit\chestshop\ui;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\utils\TextFormat;

final class ConfirmationUIButton{

	public static function fromConfig(array $data) : self{
		return new self(TextFormat::colorize($data["text"]), $data["icon"]["type"] ?? null, $data["icon"]["value"] ?? null);
	}

	/** @var string */
	private $text;

	/** @var int|null */
	private $icon_type;

	/** @var string|null */
	private $icon_value;

	public function __construct(string $text, ?string $icon_type, ?string $icon_value){
		$this->text = $text;
		$this->icon_value = $icon_value;
		switch(strtolower($icon_type)){
			case "path":
				$this->icon_type = SimpleForm::IMAGE_TYPE_PATH;
				break;
			case "url":
				$this->icon_type = SimpleForm::IMAGE_TYPE_URL;
				break;
		}
	}

	public function isValid() : bool{
		return $this->text !== "";
	}

	public function getText() : string{
		return $this->text;
	}

	public function getIconType() : ?int{
		return $this->icon_type;
	}

	public function getIconValue() : ?string{
		return $this->icon_value;
	}
}