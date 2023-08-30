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
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\permission\Permission;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\InternetRequestResult;
use pocketmine\scheduler\ClosureTask;
use ReinfyTeam\AntiVPN\Curl;

class AntiProxy extends PluginBase implements Listener {
    
    use SingletonTrait;
    
    public function onLoad() : void{
        self::setInstance($this);
        Curl::register($this);
    }  
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $perm = new Permission($this->getConfig()->get("bypass-permission"), "AntiProxy Bypass", []);
        $p = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
		$p->addChild($perm->getName(), true);
    }
    
    public function onPreProxyCheckLogin(PlayerPreLoginEvent $event) : void{
        $this->checkVPN($event->getUsername(), $event->getIp());
    }
    
    public function checkVPN(string $username, string $address) : void{
		if($this->getServer()->isOp($username)) return;
        if(($key = $this->getConfig()->get("api-key")) === ""){
            $url = "https://vpnapi.io/api/$address";
        } else {
            $url = "https://vpnapi.io/api/$address&key=$key";
        }
		Curl::getRequest($url, 10, ["Content-Type: application/json"], function(?InternetRequestResult $result) use ($username) : void {
			if($result !== null){
				if(($response = json_decode($result->getBody(), true)) !== null){

                    if(in_array($address, $this->getConfig()->get("bypass-ip"), true)) return;
                    
					if(isset($response["message"]) && $response["message"] !== ""){
                        $this->getLogger()->notice(TF::RED . "Unable to check ip: " . TF::AQUA . $address . TF::RED . " Error: " . TF::DARK_RED . $response["message"]);
						$this->checkVPN($username, $address); // continuous executions
						return;
					}

					if(isset($response["security"]["vpn"]) && isset($response["security"]["proxy"]) && isset($response["security"]["tor"]) && isset($response["security"]["relay"])){
						if($response["security"]["vpn"] === true || $response["security"]["proxy"] === true || $response["security"]["tor"] === true || $response["security"]["relay"] === true){
							if(($player = Core::getInstance()->getServer()->getPlayerExact($username)) !== null && $player->isOnline() && $player->spawned){
								$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player){
									$player->kick(TF::clean(TF::colorize($this->getConfig()->get("kick-message", "Proxy/VPN is not allowed in our server."))), "", TF::colorize($this->getConfig()->get("kick-message", "&cProxy/VPN is not allowed in our server.")));
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