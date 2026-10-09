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
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\permission\Permission;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\SingletonTrait;
use pocketmine\scheduler\ClosureTask;

class AntiProxy extends PluginBase implements Listener {
    
    use SingletonTrait;
    
    public function onLoad() : void{
        self::setInstance($this);
        Curl::register($this);
    }  
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $permissionName = $this->getConfig()->get("bypass-permission");
        if (!is_string($permissionName)) {
            return;
        }
        $perm = new Permission($permissionName, "AntiProxy Bypass", []);
        $p = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
		if ($p !== null) {
			$p->addChild($perm->getName(), true);
		}
    }
    
    public function onPreProxyCheckLogin(PlayerPreLoginEvent $event) : void{
        $this->checkVPN($event->getPlayerInfo()->getUsername(), $event->getIp());
    }
    
    public function checkVPN(string $username, string $address) : void{
		if($this->getServer()->isOp($username)) return;
        $key = $this->getConfig()->get("api-key", "");
        if (!is_string($key)) {
            $key = "";
        }
        if($key === ""){
            $url = "https://vpnapi.io/api/$address";
        } else {
            $url = "https://vpnapi.io/api/$address&key=$key";
        }
		Curl::getRequest($url, 10, ["Content-Type: application/json"], function(?InternetRequestResult $result) use ($username, $address) : void {
			if($result !== null){
				$response = json_decode($result->getBody(), true);
				if(is_array($response)){

                    $bypassIps = $this->getConfig()->get("bypass-ip", []);
                    if (is_array($bypassIps)) {
                        foreach ($bypassIps as $bypassIp) {
                            if (is_string($bypassIp) && $bypassIp === $address) {
                                return;
                            }
                        }
                    }
                    
					if(isset($response["message"]) && is_string($response["message"]) && $response["message"] !== ""){
                        $this->getLogger()->notice(TF::RED . "Unable to check ip: " . TF::AQUA . $address . TF::RED . " Error: " . TF::DARK_RED . $response["message"]);
						$this->checkVPN($username, $address); // continuous executions
						return;
					}

					$security = $response["security"] ?? null;
					if(is_array($security) && isset($security["vpn"]) && isset($security["proxy"]) && isset($security["tor"]) && isset($security["relay"])){
						if($security["vpn"] === true || $security["proxy"] === true || $security["tor"] === true || $security["relay"] === true){
							if(($player = $this->getServer()->getPlayerExact($username)) !== null && $player->isOnline() && $player->spawned){
								$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player){
									$kickMessage = $this->getConfig()->get("kick-message", "&cProxy/VPN is not allowed in our server.");
									if (!is_string($kickMessage)) {
										$kickMessage = "&cProxy/VPN is not allowed in our server.";
									}
									$player->kick(TF::clean(TF::colorize($kickMessage)), "", TF::colorize($kickMessage));
								}), 2);
								return;
							}
						}
					}
				}
			}
			$this->checkVPN($username, $address); // continuous excecution
		});
	}
}
