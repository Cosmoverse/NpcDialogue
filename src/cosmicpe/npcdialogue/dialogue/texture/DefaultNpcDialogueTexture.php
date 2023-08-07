<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue\texture;

use Generator;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

final class DefaultNpcDialogueTexture implements NpcDialogueTexture{

	public const TEXTURE_NPC_1 = 0;
	public const TEXTURE_NPC_2 = 1;
	public const TEXTURE_NPC_3 = 2;
	public const TEXTURE_NPC_4 = 3;
	public const TEXTURE_NPC_5 = 4;
	public const TEXTURE_NPC_6 = 5;
	public const TEXTURE_NPC_7 = 6;
	public const TEXTURE_NPC_8 = 7;
	public const TEXTURE_NPC_9 = 8;
	public const TEXTURE_NPC_10 = 9;
	public const TEXTURE_SCIENTIST_1 = 10;
	public const TEXTURE_SCIENTIST_2 = 11;
	public const TEXTURE_SCIENTIST_3 = 12;
	public const TEXTURE_SCIENTIST_4 = 13;
	public const TEXTURE_SCIENTIST_5 = 14;
	public const TEXTURE_SCIENTIST_6 = 15;
	public const TEXTURE_SCIENTIST_7 = 16;
	public const TEXTURE_SCIENTIST_8 = 17;
	public const TEXTURE_SCIENTIST_9 = 18;
	public const TEXTURE_SCIENTIST_10 = 19;

	readonly private EntityNpcDialogueTexture $inner;

	public function __construct(
		readonly private int $texture
	){
		$this->inner = new EntityNpcDialogueTexture(EntityIds::NPC);
	}

	public function apply(int $entity_runtime_id, EntityMetadataCollection $metadata, Vector3 $pos) : Generator{
		$metadata->setInt(EntityMetadataProperties::VARIANT, $this->texture);
		yield from $this->inner->apply($entity_runtime_id, $metadata, $pos);
	}
}