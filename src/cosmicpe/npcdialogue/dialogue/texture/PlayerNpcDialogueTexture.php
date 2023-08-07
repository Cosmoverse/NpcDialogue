<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue\texture;

use Generator;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use Ramsey\Uuid\Uuid;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class PlayerNpcDialogueTexture implements NpcDialogueTexture{

	readonly private SkinData $skin_data;
	readonly private string $skin_index;

	public function __construct(Skin $skin, ?NpcDialogueTextureOffset $picker_offset = null, ?NpcDialogueTextureOffset $portrait_offset = null){
		$this->skin_data = TypeConverter::getInstance()->getSkinAdapter()->toSkinData($skin);
		$this->skin_index = json_encode([
			"picker_offsets" => $picker_offset ?? NpcDialogueTextureOffset::defaultPicker(),
			"portrait_offsets" => $portrait_offset ?? NpcDialogueTextureOffset::defaultPlayerPortrait()
		], JSON_THROW_ON_ERROR);
	}

	public function apply(int $entity_runtime_id, EntityMetadataCollection $metadata, Vector3 $pos) : Generator{
		$metadata->setString(EntityMetadataProperties::NPC_SKIN_INDEX, $this->skin_index);
		$uuid = Uuid::uuid4();
		yield AddPlayerPacket::create(
			$uuid,
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
			UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $entity_runtime_id, [])),
			[],
			"",
			DeviceOS::UNKNOWN
		);
		yield PlayerSkinPacket::create($uuid, "a", "b", $this->skin_data);
	}
}