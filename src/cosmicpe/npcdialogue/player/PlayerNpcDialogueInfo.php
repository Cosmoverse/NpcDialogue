<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\player;

use cosmicpe\npcdialogue\dialogue\NpcDialogue;

final class PlayerNpcDialogueInfo{

	public const STATUS_SENT = 0;
	public const STATUS_RECEIVED = 1;
	public const STATUS_CLOSED = 2;

	/**
	 * @param int $actor_runtime_id
	 * @param NpcDialogue $dialogue
	 * @param self::STATUS_* $status
	 * @param int $tick
	 */
	public function __construct(
		readonly public int $actor_runtime_id,
		public NpcDialogue $dialogue,
		public int $status,
		public int $tick
	){}
}