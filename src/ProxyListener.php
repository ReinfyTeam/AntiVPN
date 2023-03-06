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

declare(strict_types=1);

namespace ReinfyTeam\AntiVPN;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use ReinfyTeam\AntiVPN\Tasks\ProxyCheckTask;

class ProxyListener implements Listener {
	/**
	 * The proxy logging event which checking happends.
	 * @PRIORITY HIGHEST
	 */
	public function onLogin(PlayerLoginEvent $ev) : void {
		AntiProxy::getInstance()->getServer()->getAsyncPool()->submitTask(new ProxyCheckTask($ev->getPlayer()->getName(), $ev->getPlayer()->getNetworkSession()->getIP()));  // PlayerAsyncLoginEvent from bukkit
	}
}