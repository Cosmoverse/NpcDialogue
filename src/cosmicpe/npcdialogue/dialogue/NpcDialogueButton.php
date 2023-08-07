<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue;

use pocketmine\player\Player;

interface NpcDialogueButton{

	public function getName() : string;

	public function getText() : string;

	public function getData() : ?string;

	public function getMode() : int;

	public function getType() : int;

	public function onClick(Player $player) : void;
}