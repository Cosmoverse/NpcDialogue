<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue;

use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTexture;
use pocketmine\player\Player;
use RuntimeException;

final class NullNpcDialogue implements NpcDialogue{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getName() : string{
		throw new RuntimeException("Cannot get name of null dialogue");
	}

	public function getText() : string{
		throw new RuntimeException("Cannot get text of null dialogue");
	}

	public function getTexture() : NpcDialogueTexture{
		throw new RuntimeException("Cannot get texture of null dialogue");
	}

	public function getButtons() : array{
		throw new RuntimeException("Cannot get buttons of null dialogue");
	}

	public function onClose(Player $player) : void{
		throw new RuntimeException("Cannot close a null dialogue");
	}
}