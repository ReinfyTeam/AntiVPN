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

use function in_array;

class ProxyUtils {
	public const PROXYDETECTOR = "https://proxydetector.io/";

	public static function bypassIP(string $ip) : bool {
		return in_array($ip, AntiProxy::getInstance()->getConfig()->get("bypass-ip"), true);
	}

	public static function bypassPlayer(string $username) : bool {
		return in_array($ip, AntiProxy::getInstance()->getConfig()->get("bypass-ip"), true);
	}

	public static function getReason() : string {
		return PluginUtils::colorize(AntiProxy::getInstance()->getConfig()->get("kick-message"));
	}
}