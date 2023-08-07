<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue\texture;

use Generator;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

final class EntityNpcDialogueTexture implements NpcDialogueTexture{

	/**
	 * @param EntityIds::*|string $entity_network_id
	 */
	public function __construct(
		readonly private string $entity_network_id
	){}

	public function apply(int $entity_runtime_id, EntityMetadataCollection $metadata, Vector3 $pos) : Generator{
		$metadata->setGenericFlag(EntityMetadataFlags::BABY, true);
		yield AddActorPacket::create(
			$entity_runtime_id,
			$entity_runtime_id,
			$this->entity_network_id,
			$pos,
			null,
			0.0,
			0.0,
			0.0,
			0.0,
			[],
			$metadata->getAll(),
			new PropertySyncData([], []),
			[]
		);
	}
}