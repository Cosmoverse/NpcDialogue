<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue;

use Closure;
use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTexture;
use cosmicpe\npcdialogue\NpcDialogueException;
use pocketmine\player\Player;

/**
 * @template TResponseType
 */
final class AsyncNpcDialogue implements NpcDialogue{

	/**
	 * @param string $name
	 * @param string $text
	 * @param NpcDialogueTexture $texture
	 * @param list<NpcDialogueButton> $buttons
	 * @param list<TResponseType> $button_mapping
	 * @param Closure(TResponseType) : void $resolve
	 * @param Closure(NpcDialogueException) : void $reject
	 */
	public function __construct(
		readonly public string $name,
		readonly public string $text,
		readonly public NpcDialogueTexture $texture,
		readonly public array $buttons,
		readonly public array $button_mapping,
		readonly public Closure $resolve,
		readonly public Closure $reject
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
		($this->resolve)($this->button_mapping[$button]);
	}

	public function onPlayerRespondInvalid(Player $player, int $invalid_response) : void{
		($this->reject)(new NpcDialogueException("Player sent an invalid response ({$invalid_response})", NpcDialogueException::ERR_PLAYER_RESPONSE_INVALID));
	}

	public function onPlayerClose(Player $player) : void{
		($this->reject)(new NpcDialogueException("Player closed", NpcDialogueException::ERR_PLAYER_CLOSED));
	}

	public function onPlayerDisconnect(Player $player) : void{
		($this->reject)(new NpcDialogueException("Player disconnected", NpcDialogueException::ERR_PLAYER_DISCONNECTED));
	}
}