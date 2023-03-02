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
use pocketmine\utils\Internet;
use ReinfyTeam\AntiVPN\AntiProxy;
use ReinfyTeam\AntiVPN\Utils\Language;
use function json_decode;
use function vsprintf;

class ProxyLoginTask extends AsyncTask {
	private $username;

	private $ip;

	private $provider;

	private $api_key;

	private const VPNAPI_IO_WITH_KEY = "https://vpnapi.io/api/%s?key=%s";
	private const VPNAPI_IO_WITHOUT_KEY = "https://vpnapi.io/api/%s";
	private const PROXYCHECK_IO = "https://proxycheck.io/v2/%s?vpn=1&asn=1";

	public function __construct($username, $ip) {
		$this->username = $username;
		$this->ip = $ip;
		$this->provider = AntiProxy::getInstance()->getConfig()->get("provider");
		$this->api_key = AntiProxy::getInstance()->getConfig()->get("api-key");
	}

	public function onRun() : void {
		$status = null;
		$country = null;
		$err = null;

		if (AntiProxy::$enabled === false) {
			return;
		} // do not check until admin enabled it.

		switch($this->provider) {
			case 0:
				if ($api_key === null) {
					$json = Internet::getUrl(vsprintf(self::VPNAPI_IO_WITHOUT_KEY, [$this->ip]), 10, [], $err);

					if ($json !== null) {
						$result = json_decode($json->getBody(), true);

						if (empty($result)) {
							AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("check-error"), [$this->username, $this->ip, ($err ?? "Unknown error.")]));
							$this->cancelRun();
							return;
						} // fix null object


						if ($result["security"][0]["vpn"] || $result["security"][0]["proxy"] || $result["security"][0]["tor"] || $result["security"][0]["relay"]) {
							$status = true;
						}

						if (!empty($result["message"]) || !empty($result["msg"])) {
							$status = "error";
						}

						$country = ($result["location"][0]["country"] ?? "Unknown Country");
						$this->setResult([$status, $country, $err]);
					}
				} else {
					$json = Internet::getUrl(vsprintf(self::VPNAPI_IO_WITH_KEY, [$this->ip, $this->api_key]), 10, [], $err);

					if ($json !== null) {
						$result = json_decode($json->getBody(), true);

						if (empty($result)) {
							AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("check-error"), [$this->username, $this->ip, ($err ?? "Unknown error.")]));
							return;
						} // fix null object

						if ($result["security"][0]["vpn"] || $result["security"][0]["proxy"] || $result["security"][0]["tor"] || $result["security"][0]["relay"]) {
							$status = true;
						}

						if (!empty($result["message"]) || !empty($result["msg"])) {
							$status = "error";
							$err = $result["message"];
						}

						$country = ($result["location"][0]["country"] ?? "Unknown Country");
						$this->setResult([$status, $country, $err]);
					}
				}
				break;
			case 1:
				$json = Internet::getUrl(vsprintf(self::PROXYCHECK_IO, [$this->ip]), 10, [], $err);

				// SETUP VARIABLES: null
				$status = null;
				$country = null;

				if ($json !== null) {
					$result = json_decode($json->getBody(), true);

					if ($result === null) {
						AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("check-error"), [$this->username, $this->ip, ($err ?? "Unknown error.")]));
						$this->cancelRun();
						return;
					} // fix null object

					if (!empty($result["message"]) || !empty($result["msg"])) {
						$status = "error";
						$err = $result["message"];
					}

					$status = $result["status"];
					$country = ($result[$this->ip]["country"] ?? "Unknown Country");
					$this->setResult([$status, $country, $err]);
				}
				break;
		}
	}

	public function onCompletion() : void {
		[$status, $country, $err] = $this->getResult();
		
		
		if(!empty($err)){
			AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("check-error"), [$this->username, $this->ip, $err]));
			$this->cancelRun();
			return;
		}
		
		if ($status === true) {
			$player = AntiProxy::getInstance()->getServer()->getPlayerExact($this->username);
			$player->kick(AntiProxy::getInstance()->getConfig()->get("kick-message"), null); // kick the player.
			AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("player-kicked-console"), [$this->username]));
			$this->cancelRun();
		} elseif ($status === false) {
			AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("not-using-proxy"), [$this->username, $this->ip]));
			$this->cancelRun();
		} elseif ($status === "error") {
			AntiProxy::getInstance()->getServer()->getLogger()->notice(Language::translateMessage("security-prefix") . " " . vsprintf(Language::translateMessage("check-error"), [$this->username, $this->ip, ($err ?? "Unknown error.")]));
			$this->cancelRun();
		}
	}
}