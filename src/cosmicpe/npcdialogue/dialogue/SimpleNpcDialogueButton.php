<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue;

use Closure;
use pocketmine\player\Player;

final class SimpleNpcDialogueButton implements NpcDialogueButton{

	/**
	 * @param string $name
	 * @param Closure(Player) : void $on_click
	 * @return self
	 */
	public static function simple(string $name, Closure $on_click) : self{
		return new self($name, "", null, 0, 1, $on_click);
	}

	/**
	 * @param string $name
	 * @param string $text
	 * @param string|null $data
	 * @param int $mode
	 * @param int $type
	 * @param Closure(Player) : void $on_click
	 */
	private function __construct(
		private string $name,
		private string $text,
		private ?string $data,
		private int $mode,
		private int $type,
		private Closure $on_click
	){}

	public function getName() : string{
		return $this->name;
	}

	public function getText() : string{
		return $this->text;
	}

	public function getData() : ?string{
		return $this->data;
	}

	public function getMode() : int{
		return $this->mode;
	}

	public function getType() : int{
		return $this->type;
	}

	public function onClick(Player $player) : void{
		($this->on_click)($player);
	}
}