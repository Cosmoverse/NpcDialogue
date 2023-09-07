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
	 * @param (Closure(Player, int) : void)|null $on_respond
	 * @param (Closure(Player) : void)|null $on_close
	 * @param (Closure(Player, int) : void)|null $on_response_invalid
	 * @param (Closure(Player) : void)|null $on_disconnect
	 */
	public function __construct(
		readonly private string $name,
		readonly private string $text,
		readonly private NpcDialogueTexture $texture,
		readonly private array $buttons,
		readonly private ?Closure $on_respond = null,
		readonly private ?Closure $on_close = null,
		readonly private ?Closure $on_response_invalid = null,
		readonly private ?Closure $on_disconnect = null
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

	public function onPlayerRespond(Player $player, int $button) : void{
		$this->buttons[$button]->onClick($player);
		if($this->on_respond !== null){
			($this->on_respond)($player, $button);
		}
	}

	public function onPlayerRespondInvalid(Player $player, int $invalid_response) : void{
		if($this->on_response_invalid !== null){
			($this->on_response_invalid)($player, $invalid_response);
		}
	}

	public function onPlayerClose(Player $player) : void{
		if($this->on_close !== null){
			($this->on_close)($player);
		}
	}

	public function onPlayerDisconnect(Player $player) : void{
		if($this->on_disconnect !== null){
			($this->on_disconnect)($player);
		}
	}
}