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

namespace ReinfyTeam\AntiVPN\Tasks;

use pocketmine\scheduler\AsyncTask;
use function json_decode;
use function vsprintf;

class ProxyLookupTask extends AsyncTask {
	private $ev;

	private $provider;

	private $api_key;

	private const VPNAPI_IO_WITH_KEY = "https://vpnapi.io/api/%s?key=%s";
	private const VPNAPI_IO_WITHOUT_KEY = "https://vpnapi.io/api/%s";
	private const PROXYCHECK_IO = "https://proxycheck.io/v2/%s?vpn=1&asn=1";

	public function __construct($player) {
		$this->player = $player;
		$this->provider = AntiProxy::getInstance()->getConfig()->get("provider");
		$this->api_key = AntiProxy::getInstance()->getConfig()->get("api-key");
	}

	public function onRun() : void {
		switch($this->provider) {
			case 0:
				if ($api_key === null) {
					$json = Internet::getUrl(vsprintf(self::VPNAPI_IO_WITHOUT_KEY, [$this->ev->getIp()]), 10, [], $err);

					// SETUP VARIABLES: null
					$status = null;
					$country = null;

					if ($json !== null) {
						$result = json_decode($json->getBody(), true);
						if ($result === null) {
							return;
						} // fix null object

						if ($result["security"]["vpn"] || $result["security"]["proxy"] || $result["security"]["tor"] || $result["security"]["relay"]) {
							$status = true;
						}

						$country = $result["location"]["country"];
						$this->setResult([$status, $country, $err]);
					}
				} else {
					$json = Internet::getUrl(vsprintf(self::VPNAPI_IO_WITH_KEY, [$this->ev->getIp(), $this->api_key]), 10, [], $err);

					// SETUP VARIABLES: null
					$status = null;
					$country = null;

					if ($json !== null) {
						$result = json_decode($json->getBody(), true);
						if ($result === null) {
							return;
						} // fix null object

						if ($result["security"]["vpn"] || $result["security"]["proxy"] || $result["security"]["tor"] || $result["security"]["relay"]) {
							$status = true;
						}

						$country = $result["location"]["country"];
						$this->setResult([$status, $country, $err]);
					}
				}
				break;
			case 1:
				$json = Internet::getUrl(vsprintf(self::PROXYCHECK_IO, [$this->ev->getIP()]), 10, [], $err);

				// SETUP VARIABLES: null
				$status = null;
				$country = null;

				if ($json !== null) {
					$result = json_decode($json->getBody(), true);
					if ($result === null) {
						return;
					} // fix null object

					$status = $result["status"];
					$country = $result[$this->ev->getIP()]["country"];
					$this->setResult([$status, $country, $err]);
				}
				break;
		}
	}

	public function onCompletion() : void {
		[$status, $country, $proxy, $err] = $this->getResult();

		if ($status !== true) {
			$this->player->kick(AntiVPN::getInstance()->getConfig("kick-message"), true); // kick the player.
		}
	}
}