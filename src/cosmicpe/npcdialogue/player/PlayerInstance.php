<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\player;

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

	private static function getNpcActorRuntimeId() : int{
		static $npc_actor_runtime_id = null;
		return $npc_actor_runtime_id ??= Entity::nextRuntimeId();
	}

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
			$this->next_dialogue = new PlayerNpcDialogueInfo($dialogue, PlayerNpcDialogueInfo::STATUS_SENT, 0);
		}else{
			$this->current_dialogue = new PlayerNpcDialogueInfo($dialogue, PlayerNpcDialogueInfo::STATUS_SENT, 0);
			$this->sendDialogueInternal($dialogue);
		}
	}

	private function sendDialogueInternal(NpcDialogue $dialogue) : void{
		$this->logger->debug("Attempting to send dialogue");
		$npc_actor_runtime_id = self::getNpcActorRuntimeId();
		$session = $this->player->getNetworkSession();
		$session->sendDataPacket(RemoveActorPacket::create($npc_actor_runtime_id));

		$texture = $dialogue->getTexture();

		$metadata = new EntityMetadataCollection();
		$metadata->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
		$metadata->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
		foreach($texture->apply($npc_actor_runtime_id, $metadata, new Vector3(0.0, -2.0, 0.0)) as $packet){
			$session->sendDataPacket($packet);
		}

		$is_op = $this->player->hasPermission(DefaultPermissions::ROOT_OPERATOR);
		if($is_op){
			$this->modify_abilities = true;
			$session->syncAbilities($this->player);
		}

		$session->sendDataPacket(NpcDialoguePacket::create(
			$npc_actor_runtime_id,
			NpcDialoguePacket::ACTION_OPEN,
			$dialogue->getText(),
			"sceneName",
			$dialogue->getName(),
			json_encode(array_map(static fn(NpcDialogueButton $button) : array => [
				"button_name" => $button->getName(),
				"text" => $button->getText(),
				"data" => $button->getData(),
				"mode" => $button->getMode(),
				"type" => $button->getType()
			], $dialogue->getButtons()), JSON_THROW_ON_ERROR)
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
				$this->sendDialogueInternal($this->current_dialogue->dialogue);
			}
		}
	}

	public function getCurrentDialogue() : ?NpcDialogue{
		$dialogue = $this->current_dialogue?->dialogue;
		return $dialogue !== NullNpcDialogue::instance() ? $dialogue : null;
	}

	public function removeCurrentDialogue() : ?PlayerNpcDialogueInfo{
		if($this->current_dialogue === null || $this->current_dialogue->dialogue === NullNpcDialogue::instance()){
			return null;
		}

		$this->logger->debug("Closed dialogue");
		$current_dialogue = $this->current_dialogue;
		$current_dialogue->dialogue->onClose($this->player);
		$current_dialogue->dialogue = NullNpcDialogue::instance();

		$npc_actor_runtime_id = self::getNpcActorRuntimeId();
		$session = $this->player->getNetworkSession();
		$session->sendDataPacket(NpcDialoguePacket::create($npc_actor_runtime_id, NpcDialoguePacket::ACTION_CLOSE, "", "", "", ""));
		$session->sendDataPacket(RemoveActorPacket::create($npc_actor_runtime_id));
		$this->manager->tick($this->player);
		return $current_dialogue;
	}
}