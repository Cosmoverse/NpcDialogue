<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\player;

use BadMethodCallException;
use Logger;
use cosmicpe\npcdialogue\dialogue\NpcDialogue;
use cosmicpe\npcdialogue\dialogue\NpcDialogueButton;
use cosmicpe\npcdialogue\dialogue\NullNpcDialogue;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use function array_map;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class PlayerInstance{

	private bool $modify_abilities = false;
	private ?PlayerNpcDialogueInfo $current_dialogue = null;
	private ?PlayerNpcDialogueInfo $next_dialogue = null;

	public function __construct(
		readonly private PlayerManager $manager,
		readonly private Player $player,
		readonly private Logger $logger
	){}

	public function tick() : bool{
		if($this->current_dialogue === null || $this->current_dialogue->status === PlayerNpcDialogueInfo::STATUS_CLOSED || $this->current_dialogue->dialogue !== NullNpcDialogue::instance()){
			return false;
		}
		if(++$this->current_dialogue->tick >= 8){
			$this->onDialogueClose();
		}
		return true;
	}

	public function handleUpdateAbilities(UpdateAbilitiesPacket $packet) : ?UpdateAbilitiesPacket{
		if(!$this->modify_abilities){
			return null;
		}

		$data = $packet->getData();
		$ability_layers = $data->getAbilityLayers();
		foreach($ability_layers as $index => $layer){
			$abilities = $layer->getBoolAbilities();
			if(isset($abilities[AbilitiesLayer::ABILITY_OPERATOR])){
				$abilities[AbilitiesLayer::ABILITY_OPERATOR] = false;
				$ability_layers[$index] = new AbilitiesLayer($layer->getLayerId(), $abilities, $layer->getFlySpeed(), $layer->getWalkSpeed());
			}
		}

		return UpdateAbilitiesPacket::create(new AbilitiesData(
			$data->getCommandPermission(),
			$data->getPlayerPermission(),
			$data->getTargetActorUniqueId(),
			$ability_layers
		));
	}

	public function sendDialogue(NpcDialogue $dialogue) : void{
		$this->removeCurrentDialogue();
		if($this->current_dialogue !== null && $this->current_dialogue->status !== PlayerNpcDialogueInfo::STATUS_CLOSED){
			$this->next_dialogue = new PlayerNpcDialogueInfo(Entity::nextRuntimeId(), $dialogue, PlayerNpcDialogueInfo::STATUS_SENT, 0);
		}else{
			$this->current_dialogue = new PlayerNpcDialogueInfo(Entity::nextRuntimeId(), $dialogue, PlayerNpcDialogueInfo::STATUS_SENT, 0);
			$this->sendDialogueInternal($this->current_dialogue);
		}
	}

	public function updateDialogue(NpcDialogue $dialogue) : void{
		$this->current_dialogue !== null || throw new BadMethodCallException("Player is not viewing a dialogue");
		$this->current_dialogue = new PlayerNpcDialogueInfo($this->current_dialogue->actor_runtime_id, $dialogue, PlayerNpcDialogueInfo::STATUS_SENT, 0);
		$this->sendDialogueWindow($this->current_dialogue);
	}

	private function sendDialogueInternal(PlayerNpcDialogueInfo $info) : void{
		$this->logger->debug("Attempting to send dialogue");
		$session = $this->player->getNetworkSession();
		$texture = $info->dialogue->getTexture();
		$metadata = new EntityMetadataCollection();
		$metadata->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
		$metadata->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
		foreach($texture->apply($info->actor_runtime_id, $metadata, new Vector3(0.0, -2.0, 0.0)) as $packet){
			$session->sendDataPacket($packet);
		}
		$this->sendDialogueWindow($info);
	}

	private function sendDialogueWindow(PlayerNpcDialogueInfo $info) : void{
		$session = $this->player->getNetworkSession();
		$is_op = $this->player->hasPermission(DefaultPermissions::ROOT_OPERATOR);
		if($is_op){
			$this->modify_abilities = true;
			$session->syncAbilities($this->player);
		}
		$session->sendDataPacket(NpcDialoguePacket::create(
			$info->actor_runtime_id,
			NpcDialoguePacket::ACTION_OPEN,
			$info->dialogue->getText(),
			(string) $info->actor_runtime_id,
			$info->dialogue->getName(),
			json_encode(array_map(static fn(NpcDialogueButton $button) : array => [
				"button_name" => $button->getName(),
				"text" => $button->getText(),
				"data" => $button->getData(),
				"mode" => $button->getMode(),
				"type" => $button->getType()
			], $info->dialogue->getButtons()), JSON_THROW_ON_ERROR)
		));
		if($is_op){
			$this->modify_abilities = false;
			$session->syncAbilities($this->player);
		}
	}

	public function onDialogueReceive() : void{
		if($this->current_dialogue !== null && $this->current_dialogue->dialogue !== NullNpcDialogue::instance()){
			$this->current_dialogue->status = PlayerNpcDialogueInfo::STATUS_RECEIVED;
		}
	}

	public function onDialogueClose() : void{
		if($this->current_dialogue !== null && $this->current_dialogue->dialogue === NullNpcDialogue::instance()){
			$this->current_dialogue->status = PlayerNpcDialogueInfo::STATUS_CLOSED;
			if($this->next_dialogue !== null){
				$this->current_dialogue = $this->next_dialogue;
				$this->next_dialogue = null;
				$this->sendDialogueInternal($this->current_dialogue);
			}
		}
	}

	public function getCurrentDialogueInfo() : ?PlayerNpcDialogueInfo{
		$dialogue = $this->current_dialogue?->dialogue;
		return $dialogue !== NullNpcDialogue::instance() ? $this->current_dialogue : null;
	}

	public function getCurrentDialogue() : ?NpcDialogue{
		return $this->getCurrentDialogueInfo()->dialogue;
	}

	public function onDialogueRespond(string $scene_name, int $index) : void{
		$info = $this->getCurrentDialogueInfo();
		if($info !== null && (int) $scene_name === $info->actor_runtime_id){
			if(isset($info->dialogue->getButtons()[$index])){
				$info->dialogue->onPlayerRespond($this->player, $index);
			}else{
				$info->dialogue->onPlayerRespondInvalid($this->player, $index);
			}
		}
	}

	public function removeCurrentDialogue() : ?PlayerNpcDialogueInfo{
		if($this->current_dialogue === null || $this->current_dialogue->dialogue === NullNpcDialogue::instance()){
			return null;
		}

		$this->logger->debug("Closed dialogue");
		$current_dialogue = $this->current_dialogue;
		$current_dialogue->dialogue->onPlayerDisconnect($this->player);
		$current_dialogue->dialogue = NullNpcDialogue::instance();

		$session = $this->player->getNetworkSession();
		$session->sendDataPacket(NpcDialoguePacket::create($current_dialogue->actor_runtime_id, NpcDialoguePacket::ACTION_CLOSE, "", "", "", ""));
		$session->sendDataPacket(RemoveActorPacket::create($current_dialogue->actor_runtime_id));
		$this->manager->tick($this->player);
		return $current_dialogue;
	}
}