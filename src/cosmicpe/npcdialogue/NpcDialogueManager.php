<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue;

use BadMethodCallException;
use Closure;
use cosmicpe\npcdialogue\dialogue\AsyncNpcDialogue;
use cosmicpe\npcdialogue\dialogue\NpcDialogue;
use cosmicpe\npcdialogue\dialogue\NpcDialogueButton;
use cosmicpe\npcdialogue\dialogue\texture\DefaultNpcDialogueTexture;
use cosmicpe\npcdialogue\dialogue\texture\NpcDialogueTexture;
use cosmicpe\npcdialogue\player\PlayerManager;
use Generator;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use SOFe\AwaitGenerator\Await;
use function array_keys;
use function array_values;

final class NpcDialogueManager{

	private static ?PlayerManager $manager = null;

	public static function isRegistered() : bool{
		return self::$manager !== null;
	}

	public static function register(Plugin $plugin) : void{
		self::$manager === null || throw new BadMethodCallException("NpcDialog is already registered");
		self::$manager = new PlayerManager();
		self::$manager->init($plugin);
	}

	public static function send(Player $player, NpcDialogue $dialogue) : void{
		self::$manager !== null || throw new BadMethodCallException("NpcDialog is not registered");
		self::$manager->getPlayer($player)->sendDialogue($dialogue);
	}

	public static function update(Player $player, NpcDialogue $dialogue) : void{
		self::$manager !== null || throw new BadMethodCallException("NpcDialog is not registered");
		self::$manager->getPlayer($player)->updateDialogue($dialogue);
	}

	/**
	 * @template TKey of string|int
	 * @template TResponseType of mixed
	 * @param Player $player
	 * @param string $name
	 * @param string $text
	 * @param NpcDialogueTexture|null $texture
	 * @param array<TKey, NpcDialogueButton> $buttons
	 * @param list<TResponseType>|null $button_mapping
	 * @return Generator<mixed, Await::RESOLVE|Await::REJECT, mixed, ($button_mapping is null ? TKey : TResponseType)>
	 */
	public static function request(Player $player, string $name, string $text, ?NpcDialogueTexture $texture = null, array $buttons = [], ?array $button_mapping = null) : Generator{
		self::$manager !== null || throw new BadMethodCallException("NpcDialog is not registered");
		$instance = self::$manager->getPlayerNullable($player) ?? throw new NpcDialogueException("Player is not connected", NpcDialogueException::ERR_PLAYER_DISCONNECTED);
		$texture ??= new DefaultNpcDialogueTexture(DefaultNpcDialogueTexture::TEXTURE_NPC_10);
		$button_mapping ??= array_keys($buttons);
		return yield from Await::promise(static fn(Closure $resolve, Closure $reject) => $instance->sendDialogue(new AsyncNpcDialogue($name, $text, $texture, array_values($buttons), $button_mapping, $resolve, $reject)));
	}

	/**
	 * @template TKey of string|int
	 * @template TResponseType of mixed
	 * @param Player $player
	 * @param string|null $name
	 * @param string|null $text
	 * @param NpcDialogueTexture|null $texture
	 * @param array<TKey, NpcDialogueButton>|null $buttons
	 * @param list<TResponseType>|null $button_mapping
	 */
	public static function updateRequest(Player $player, ?string $name = null, ?string $text = null, ?NpcDialogueTexture $texture = null, ?array $buttons = null, ?array $button_mapping = null) : void{
		self::$manager !== null || throw new BadMethodCallException("NpcDialog is not registered");
		$instance = self::$manager->getPlayer($player);
		$current = $instance->getCurrentDialogue();
		$current instanceof AsyncNpcDialogue || throw new BadMethodCallException("Player is not viewing an asynchronous dialogue");
		if($buttons !== null){
			$buttons = array_values($buttons);
			$button_mapping ??= array_keys($buttons);
		}else{
			$buttons = $current->buttons;
			$button_mapping = $current->button_mapping;
		}
		$instance->updateDialogue(new AsyncNpcDialogue($name ?? $current->name, $text ?? $current->text, $texture ?? $current->texture, $buttons, $button_mapping, $current->resolve, $current->reject));
	}

	public static function remove(Player $player) : ?NpcDialogue{
		self::$manager !== null || throw new BadMethodCallException("NpcDialog is not registered");
		return self::$manager->getPlayer($player)->removeCurrentDialogue()?->dialogue;
	}
}