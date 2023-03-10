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
use pocketmine\Server;
use pocketmine\utils\Internet;
use ReinfyTeam\AntiVPN\Utils\Language;
use function json_decode;
use function vsprintf;

class GithubUpdateTask extends AsyncTask {
	private const GIT_URL = "https://raw.githubusercontent.com/ReinfyTeam/AntiVPN/main/build_info.json";

	public function __construct(private string $pluginName, private string $pluginVersion) {
		//NOOP
	}

	public function onRun() : void {
		$json = Internet::getURL(self::GIT_URL, 10, [], $err);
		$highestVersion = "";
		$artifactUrl = "";
		$api_to = "";
		$api_from = "";
		if ($err === null) {
			$releases = json_decode($json->getBody(), true);
			$highestVersion = $releases["version"];
			$artifactUrl = $releases["artifactUrl"];
			$api_to = $releases["api_to"];
			$api_from = $releases["api_from"];
		}

		$this->setResult([$highestVersion, $artifactUrl, $api_to, $err, $api_from]);
	}

	public function onCompletion() : void {
		$lang = new Language();
		[$highestVersion, $artifactUrl, $api_to, $err, $api_from] = $this->getResult();
		$plugin = Server::getInstance()->getPluginManager()->getPlugin($this->pluginName);
		if ($plugin === null) {
			return;
		}

		if ($err !== null) {
			Server::getInstance()->getLogger()->critical(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("update-error"), [$err]));
			Server::getInstance()->getLogger()->notice(Language::translateMessage("new-update-prefix") . " " . Language::translateMessage("update-retry-failed"));
			$this->cancelRun();
			return;
		}

		if ($highestVersion !== $this->pluginVersion) {
			Server::getInstance()->getLogger()->warning(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("new-update-found"), [$highestVersion, $api_from]));
			Server::getInstance()->getLogger()->warning(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("new-update-details"), [$api_from, $api_to]));
			Server::getInstance()->getLogger()->warning(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("new-update-download"), [$artifactUrl]));
			$this->cancelRun();
		} else {
			Server::getInstance()->getLogger()->notice(Language::translateMessage("new-update-prefix") . " " . Language::translateMessage("no-updates-found"));
			$this->cancelRun();
		}
	}
}
