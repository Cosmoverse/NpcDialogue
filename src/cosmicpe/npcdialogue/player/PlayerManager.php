<?php

declare(strict_types=1);

namespace cosmicpe\npcdialogue\player;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\NpcRequestPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use PrefixedLogger;
use RuntimeException;
use function array_diff_key;
use function array_map;
use function count;

final class PlayerManager{

	/** @var array<int, PlayerInstance> */
	private array $players = [];

	/** @var array<int, int> */
	private array $ticking = [];

	public function __construct(){
	}

	public function init(Plugin $plugin) : void{
		$manager = Server::getInstance()->getPluginManager();

		$manager->registerEvent(PlayerLoginEvent::class, function(PlayerLoginEvent $event) use($plugin) : void{
			$player = $event->getPlayer();
			$this->players[$player->getId()] = new PlayerInstance($this, $player, new PrefixedLogger($plugin->getLogger(), $player->getName()));
		}, EventPriority::MONITOR, $plugin);
		$manager->registerEvent(PlayerQuitEvent::class, function(PlayerQuitEvent $event) : void{
			unset($this->players[$id = $event->getPlayer()->getId()], $this->ticking[$id]);
		}, EventPriority::MONITOR, $plugin);
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			foreach($this->ticking as $id){
				if(!$this->players[$id]->tick()){
					unset($this->ticking[$id]);
				}
			}
		}), 1);

		$manager->registerEvent(DataPacketReceiveEvent::class, function(DataPacketReceiveEvent $event) : void{
			$packet = $event->getPacket();
			if(!($packet instanceof NpcRequestPacket)){
				return;
			}

			$player = $event->getOrigin()->getPlayer();
			if($player === null){
				return;
			}

			$instance = $this->getPlayerNullable($player);
			if($instance === null){
				return;
			}

			if($packet->requestType === NpcRequestPacket::REQUEST_EXECUTE_ACTION){
				$dialogue = $instance->getCurrentDialogue();
				if($dialogue !== null){
					$buttons = $dialogue->getButtons();
					if(isset($buttons[$packet->actionIndex])){
						$instance->removeCurrentDialogue();
						$buttons[$packet->actionIndex]->onClick($player);
					}
				}
			}elseif($packet->requestType === NpcRequestPacket::REQUEST_EXECUTE_OPENING_COMMANDS){
				$instance->onDialogueReceive();
			}elseif($packet->requestType === NpcRequestPacket::REQUEST_EXECUTE_CLOSING_COMMANDS){
				$instance->onDialogueClose();
			}
		}, EventPriority::MONITOR, $plugin);
		$manager->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $event) : void{
			static $processing = false;
			if($processing){
				return;
			}

			$packets = $event->getPackets();
			$targets = $event->getTargets();
			$remove = [];
			foreach($packets as $packet){
				if(!($packet instanceof UpdateAbilitiesPacket)){
					continue;
				}
				foreach($targets as $id => $target){
					$player = $target->getPlayer();
					if($player === null){
						continue;
					}

					$instance = $this->getPlayerNullable($player);
					if($instance === null){
						continue;
					}

					$replacement = $instance->handleUpdateAbilities($packet);
					if($replacement === null){
						continue;
					}

					$processing = true;
					$target->sendDataPacket($replacement);
					$processing = false;
					$remove[$id] = null;
				}
			}

			if(count($remove) === 0){
				return;
			}

			$event->cancel();

			$new_targets = array_diff_key($targets, $remove);
			if(count($new_targets) > 0){
				$processing = false;
				NetworkBroadcastUtils::broadcastPackets(array_map(
					static fn(NetworkSession $session) : Player => $session->getPlayer() ?? throw new RuntimeException("Expected connected player"),
					$new_targets
				), $packets);
				$processing = true;
			}
		}, EventPriority::MONITOR, $plugin);
	}

	public function getPlayer(Player $player) : PlayerInstance{
		return $this->players[$player->getId()];
	}

	public function getPlayerNullable(Player $player) : ?PlayerInstance{
		return $this->players[$player->getId()] ?? null;
	}

	public function tick(Player $player) : void{
		if(isset($this->players[$id = $player->getId()])){
			$this->ticking[$id] = $id;
		}
	}

	public function unTick(Player $player) : void{
		unset($this->ticking[$player->getId()]);
	}
}