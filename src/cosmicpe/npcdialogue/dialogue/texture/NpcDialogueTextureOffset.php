<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue\texture;

use JsonSerializable;

final class NpcDialogueTextureOffset implements JsonSerializable{

	public static function defaultPicker() : self{
		return new self(0, 0, 0, 0, 0, 0);
	}

	public static function defaultPortrait() : self{
		return new self(1, 1, 1, 0, 0, 0);
	}

	public static function defaultPlayerPortrait() : self{
		/**
		 * portraitOffset->translate_y is used to set the offset of dialogue picker
		 * This only need when you want to spawn Human NPC. (the NPC goes off when you open dialogue)
		 * @author Tobias Grether ({@link https://github.com/TobiasGrether})
		 * {@link https://github.com/refteams/libNpcDialogue/commit/521bf630a1e4efc70f8c410a0f51f61950381e62}
		 */
		$parent = self::defaultPortrait();
		return new self($parent->scale_x, $parent->scale_y, $parent->scale_z, $parent->translate_x, -50, $parent->translate_z);
	}

	public function __construct(
		readonly public int|float $scale_x,
		readonly public int|float $scale_y,
		readonly public int|float $scale_z,
		readonly public int|float $translate_x,
		readonly public int|float $translate_y,
		readonly public int|float $translate_z
	){}

	/**
	 * @return array{scale: array{int|float, int|float, int|float}, translate: array{int|float, int|float, int|float}}
	 */
	public function jsonSerialize() : array{
		return [
			"scale" => [$this->scale_x, $this->scale_y, $this->scale_z],
			"translate" => [$this->translate_x, $this->translate_y, $this->translate_z]
		];
	}
}