<?php


/*
 *
 *  ____           _            __           _____
 * |  _ \    ___  (_)  _ __    / _|  _   _  |_   _|   ___    __ _   _ __ ___
 * | |_) |  / _ \ | | | '_ \  | |_  | | | |   | |    / _ \  / _` | | '_ ` _ \
 * |  _ <  |  __/ | | | | | | |  _| | |_| |   | |   |  __/ | (_| | | | | | | |
 * |_| \_\  \___| |_| |_| |_| |_|    \__, |   |_|    \___|  \__,_| |_| |_| |_|
 *                                   |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author ReinfyTeam
 * @link https://github.com/ReinfyTeam/
 *
 *
 */
 
namespace ReinfyTeam\AntiVPN;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\player\Player;
use pocketmine\permission\Permission;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\SingletonTrait;
use pocketmine\scheduler\ClosureTask;

class AntiProxy extends PluginBase implements Listener {

	private const MAX_RETRIES = 3;
	private const RETRY_DELAY_TICKS = 20;

	/** @var array<string, true> */
	private array $pendingKicks = [];
    
    use SingletonTrait;
    
    public function onLoad() : void{
        self::setInstance($this);
        Curl::register($this);
    }  
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $apiKey = $this->getConfig()->get("api-key", "");
        if (!is_string($apiKey) || trim($apiKey) === "") {
            $this->getLogger()->warning("No VPNAPI.io API key is configured; AntiVPN will skip IP classification.");
        }
        $this->registerBypassPermission();
    }

    private function registerBypassPermission() : void{
        $permissionName = $this->getConfig()->get("bypass-permission");
        if (!is_string($permissionName) || $permissionName === "") {
            return;
        }

        $perm = new Permission($permissionName, "AntiProxy Bypass", []);
        $permissionManager = PermissionManager::getInstance();
        $permissionManager->addPermission($perm);
        $operatorPermission = $permissionManager->getPermission(DefaultPermissions::ROOT_OPERATOR);
		if ($operatorPermission !== null) {
			$operatorPermission->addChild($perm->getName(), true);
		}
    }
    
    public function onPreProxyCheckLogin(PlayerPreLoginEvent $event) : void{
        $this->checkVPN($event->getPlayerInfo()->getUsername(), $event->getIp());
    }

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$key = $this->pendingKickKey($player->getName(), $player->getNetworkSession()->getIp());
		if (!isset($this->pendingKicks[$key])) {
			return;
		}

		unset($this->pendingKicks[$key]);
		if ($this->isBypassed($player->getName(), $player->getNetworkSession()->getIp())) {
			return;
		}
		$this->scheduleKick($player);
	}
    
	public function checkVPN(string $username, string $address, int $attempt = 0) : void{
		if ($this->isBypassed($username, $address)) {
			return;
		}

		$retry = function () use ($username, $address, $attempt) : void {
			if ($attempt >= self::MAX_RETRIES) {
				$this->getLogger()->warning("VPNAPI.io did not return a usable result after " . (self::MAX_RETRIES + 1) . " attempts for " . $address . ".");
				return;
			}

			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($username, $address, $attempt) : void {
				$this->checkVPN($username, $address, $attempt + 1);
			}), self::RETRY_DELAY_TICKS * ($attempt + 1));
		};

		$key = $this->getConfig()->get("api-key", "");
		if (!is_string($key) || trim($key) === "") {
			return;
		}
		$url = "https://vpnapi.io/api/$address?key=" . rawurlencode(trim($key));
		Curl::getRequest($url, 10, ["Content-Type: application/json"], function(?InternetRequestResult $result) use ($username, $address, $retry) : void {
			if ($result === null || $result->getCode() < 200 || $result->getCode() >= 300) {
				$retry();
				return;
			}

			$classification = VpnApiResponse::parse($result->getBody());
			if ($classification === null) {
				$retry();
				return;
			}

			if (VpnApiResponse::isBlocked($classification)) {
				if ($this->isBypassed($username, $address)) {
					return;
				}

				if (($player = $this->getServer()->getPlayerExact($username)) !== null && $player->isOnline() && $player->spawned) {
					$this->scheduleKick($player);
					return;
				}

				$this->pendingKicks[$this->pendingKickKey($username, $address)] = true;
			}
		});
	}

	private function isBypassed(string $username, string $address) : bool{
		if ($this->getServer()->isOp($username)) {
			return true;
		}

		$permissionName = $this->getConfig()->get("bypass-permission");
		$player = $this->getServer()->getPlayerExact($username);
		if ($player !== null && is_string($permissionName) && $permissionName !== "" && $player->hasPermission($permissionName)) {
			return true;
		}

		$bypassPlayers = $this->getConfig()->get("bypass-players", []);
		if (is_array($bypassPlayers) && in_array($username, $bypassPlayers, true)) {
			return true;
		}
		$bypassIps = $this->getConfig()->get("bypass-ip", []);
		if (is_array($bypassIps)) {
			foreach ($bypassIps as $bypassIp) {
				if (is_string($bypassIp) && $bypassIp === $address) {
					return true;
				}
			}
		}
		return false;
	}

	private function pendingKickKey(string $username, string $address) : string{
		return $username . "\0" . $address;
	}

	private function scheduleKick(Player $player) : void{
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) : void{
			if (!$player->isOnline()) {
				return;
			}

			$kickMessage = $this->getConfig()->get("kick-message", "&cProxy/VPN is not allowed in our server.");
			if (!is_string($kickMessage)) {
				$kickMessage = "&cProxy/VPN is not allowed in our server.";
			}
			$player->kick(TF::clean(TF::colorize($kickMessage)), "", TF::colorize($kickMessage));
		}), 2);
	}
}
