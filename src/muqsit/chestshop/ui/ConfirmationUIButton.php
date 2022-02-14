<?php

declare(strict_types=1);

namespace muqsit\chestshop\ui;

use InvalidArgumentException;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\utils\TextFormat;
use function strtolower;

final class ConfirmationUIButton{

	/**
	 * @param array $data
	 * @return self
	 *
	 * @phpstan-param array<string, mixed> $data
	 */
	public static function fromConfig(array $data) : self{
		return new self(TextFormat::colorize($data["text"]), $data["icon"]["type"] ?? null, $data["icon"]["value"] ?? null);
	}

	private string $text;
	private ?int $icon_type;
	private ?string $icon_value;

	public function __construct(string $text, ?string $icon_type, ?string $icon_value){
		$this->text = $text;
		$this->icon_value = $icon_value;
		$this->icon_type = match(strtolower($icon_type)){
			"path" => SimpleForm::IMAGE_TYPE_PATH,
			"url" => SimpleForm::IMAGE_TYPE_URL,
			default => throw new InvalidArgumentException("Invalid icon type: {$icon_type}")
		};
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