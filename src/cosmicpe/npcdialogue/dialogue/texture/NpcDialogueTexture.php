<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\dialogue\texture;

use Generator;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;

interface NpcDialogueTexture{

	/**
	 * @param int $entity_runtime_id
	 * @param EntityMetadataCollection $metadata
	 * @param Vector3 $pos
	 * @return Generator<ClientboundPacket>
	 */
	public function apply(int $entity_runtime_id, EntityMetadataCollection $metadata, Vector3 $pos) : Generator;
}