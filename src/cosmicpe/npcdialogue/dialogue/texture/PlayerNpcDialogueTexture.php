<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue\texture;

use Generator;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class PlayerNpcDialogueTexture implements NpcDialogueTexture{

	readonly public SkinData $skin_data;
	readonly public string $skin_index;
	readonly public UuidInterface $uuid;

	public function __construct(Skin $skin, ?NpcDialogueTextureOffset $picker_offset = null, ?NpcDialogueTextureOffset $portrait_offset = null){
		$this->uuid = Uuid::uuid4();
		$this->skin_data = TypeConverter::getInstance()->getSkinAdapter()->toSkinData($skin);
		$this->skin_index = json_encode([
			"picker_offsets" => $picker_offset ?? NpcDialogueTextureOffset::defaultPicker(),
			"portrait_offsets" => $portrait_offset ?? NpcDialogueTextureOffset::defaultPlayerPortrait()
		], JSON_THROW_ON_ERROR);
	}

	public function apply(int $entity_runtime_id, EntityMetadataCollection $metadata, Vector3 $pos) : Generator{
		yield PlayerListPacket::create([
			PlayerListEntry::createAdditionEntry($this->uuid, $entity_runtime_id, "", $this->skin_data)
		]);

		$metadata->setString(EntityMetadataProperties::NPC_SKIN_INDEX, $this->skin_index);
		yield AddPlayerPacket::create(
			$this->uuid,
			"",
			$entity_runtime_id,
			"",
			$pos,
			null,
			0.0,
			0.0,
			0.0,
			ItemStackWrapper::legacy(ItemStack::null()),
			GameMode::SURVIVAL,
			$metadata->getAll(),
			new PropertySyncData([], []),
			new AbilitiesData(CommandPermissions::NORMAL, PlayerPermissions::VISITOR, $entity_runtime_id, []),
			[],
			null,
			"",
			DeviceOS::UNKNOWN
		);

		yield PlayerListPacket::create([PlayerListEntry::createRemovalEntry($this->uuid)]);
	}
}