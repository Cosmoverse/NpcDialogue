<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue;

use Closure;
use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTexture;
use pocketmine\player\Player;

final class SimpleNpcDialogue implements NpcDialogue{

	/**
	 * @param string $name
	 * @param string $text
	 * @param NpcDialogueTexture $texture
	 * @param list<NpcDialogueButton> $buttons
	 * @param (Closure(Player) : void)|null $on_close
	 */
	public function __construct(
		readonly private string $name,
		readonly private string $text,
		readonly private NpcDialogueTexture $texture,
		readonly private array $buttons,
		readonly private ?Closure $on_close = null
	){}

	public function getName() : string{
		return $this->name;
	}

	public function getText() : string{
		return $this->text;
	}

	public function getTexture() : NpcDialogueTexture{
		return $this->texture;
	}

	public function getButtons() : array{
		return $this->buttons;
	}

	public function onClose(Player $player) : void{
		if($this->on_close !== null){
			($this->on_close)($player);
		}
	}
}