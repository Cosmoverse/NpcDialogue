<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue;

use Closure;
use cosmicpe\npcdialogue\dialogue\NpcDialogue;
use cosmicpe\npcdialogue\dialogue\NpcDialogueButton;
use cosmicpe\npcdialogue\dialogue\SimpleNpcDialogue;
use cosmicpe\npcdialogue\dialogue\SimpleNpcDialogueButton;
use cosmicpe\npcdialogue\dialogue\texture\DefaultNpcDialogueTexture;
use cosmicpe\npcdialogue\dialogue\texture\EntityNpcDialogueTexture;
use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTexture;
use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTextureOffset;
use cosmicpe\npcdialogue\dialogue\texture\PlayerNpcDialogueTexture;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

final class NpcDialogueBuilder{

	public static function create() : self{
		return new self("Default name", "Default text", new DefaultNpcDialogueTexture(DefaultNpcDialogueTexture::TEXTURE_NPC_10), [], null);
	}

	/**
	 * @param string $name
	 * @param string $text
	 * @param NpcDialogueTexture $texture
	 * @param list<NpcDialogueButton> $buttons
	 * @param (Closure(Player) : void)|null $on_close
	 */
	private function __construct(
		public string $name,
		public string $text,
		public NpcDialogueTexture $texture,
		public array $buttons,
		public ?Closure $on_close
	){}

	public function setName(string $name) : self{
		$this->name = $name;
		return $this;
	}

	public function setText(string $text) : self{
		$this->text = $text;
		return $this;
	}

	public function setTexture(NpcDialogueTexture $texture) : self{
		$this->texture = $texture;
		return $this;
	}

	/**
	 * @param DefaultNpcDialogueTexture::TEXTURE_* $id
	 * @return self
	 */
	public function setDefaultNpcTexture(int $id) : self{
		return $this->setTexture(new DefaultNpcDialogueTexture($id));
	}

	/**
	 * @param EntityIds::*|string $entity_id
	 * @return self
	 */
	public function setEntityNpcTexture(string $entity_id) : self{
		return $this->setTexture(new EntityNpcDialogueTexture($entity_id));
	}

	public function setSkinNpcTexture(Skin $skin, ?NpcDialogueTextureOffset $picker_offset = null, ?NpcDialogueTextureOffset $portrait_offset = null) : self{
		return $this->setTexture(new PlayerNpcDialogueTexture($skin, $picker_offset, $portrait_offset));
	}

	/**
	 * @param list<NpcDialogueButton> $buttons
	 * @return self
	 */
	public function setButtons(array $buttons) : self{
		$this->buttons = $buttons;
		return $this;
	}

	/**
	 * @param string $name
	 * @param (Closure(Player) : void)|null $on_click
	 * @return self
	 */
	public function addSimpleButton(string $name, Closure $on_click = null) : self{
		$this->buttons[] = SimpleNpcDialogueButton::simple($name, $on_click ?? function(Player $player) : void{});
		return $this;
	}

	/**
	 * @param (Closure(Player) : void)|null $on_close
	 * @return self
	 */
	public function setCloseListener(?Closure $on_close) : self{
		$this->on_close = $on_close;
		return $this;
	}

	public function build() : NpcDialogue{
		return new SimpleNpcDialogue($this->name, $this->text, $this->texture, $this->buttons, $this->on_close);
	}
}