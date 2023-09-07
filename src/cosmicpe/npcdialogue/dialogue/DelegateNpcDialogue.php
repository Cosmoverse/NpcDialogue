<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue;

use Closure;
use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTexture;
use pocketmine\player\Player;

final class DelegateNpcDialogue implements NpcDialogue{

	/**
	 * @param NpcDialogue $inner
	 * @param (Closure(Player, int) : void)|null $on_respond
	 * @param (Closure(Player) : void)|null $on_close
	 */
	public function __construct(
		readonly private NpcDialogue $inner,
		readonly private ?Closure $on_respond = null,
		readonly private ?Closure $on_close = null
	){}

	public function getName() : string{
		return $this->inner->getName();
	}

	public function getText() : string{
		return $this->inner->getText();
	}

	public function getTexture() : NpcDialogueTexture{
		return $this->inner->getTexture();
	}

	public function getButtons() : array{
		return $this->inner->getButtons();
	}

	public function onPlayerRespond(Player $player, int $button) : void{
		$this->inner->onPlayerRespond($player, $button);
		if($this->on_respond !== null){
			($this->on_respond)($player, $button);
		}
	}

	public function onPlayerRespondInvalid(Player $player, int $invalid_response) : void{
		$this->inner->onPlayerRespondInvalid($player, $invalid_response);
	}

	public function onPlayerClose(Player $player) : void{
		$this->inner->onPlayerClose($player);
		if($this->on_close !== null){
			($this->on_close)($player);
		}
	}

	public function onPlayerDisconnect(Player $player) : void{
		$this->inner->onPlayerDisconnect($player);
		if($this->on_close !== null){
			($this->on_close)($player);
		}
	}
}