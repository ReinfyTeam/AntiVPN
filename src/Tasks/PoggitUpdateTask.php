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
use ReinfyTeam\AntiVPN\AntiProxy;
use ReinfyTeam\AntiVPN\Utils\Language;
use function json_decode;
use function version_compare;
use function vsprintf;

class PoggitUpdateTask extends AsyncTask {
	private const POGGIT_RELEASES_URL = "https://poggit.pmmp.io/releases.min.json?name=";

	public function __construct(private string $pluginName, private string $pluginVersion) {
		//NOOP
	}

	public function onRun() : void {
		$json = Internet::getURL(self::POGGIT_RELEASES_URL . $this->pluginName, 10, [], $err);
		$highestVersion = $this->pluginVersion;
		$artifactUrl = "";
		$api = "";
		if ($json !== null) {
			$releases = json_decode($json->getBody(), true);
			if ($releases === null) {
				return;
			}
			foreach ($releases as $release) {
				if (version_compare($highestVersion, $release["version"], ">=")) {
					continue;
				}
				$highestVersion = $release["version"];
				$artifactUrl = $release["artifact_url"];
				$api_from = $release["api_from"];
				$api_to = $release["api_to"];
			}
		}

		$this->setResult([$highestVersion, $artifactUrl, $api, $err]);
	}

	public function onCompletion() : void {
		$plugin = Server::getInstance()->getPluginManager()->getPlugin($this->pluginName);
		if ($plugin === null) {
			return;
		}
		[$highestVersion, $artifactUrl, $api, $err] = $this->getResult();
		if ($highestVersion === null || $artifactUrl === null || $api === null) {
			Server::getInstance()->getLogger()->critical(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("update-error"), ["Trying to update on github..."]));
			$plugin->getServer()->getAsyncPool()->submitTask(new GithubUpdateTask(AntiProxy::getInstance()->getDescription()->getName(), AntiProxy::getInstance()->getDescription()->getVersion()));
			$this->cancelRun();
			return;
		}
		if ($err !== null) {
			Server::getInstance()->getLogger()->critical(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("update-error"), [$err]));
			Server::getInstance()->getLogger()->notice(Language::translateMessage("new-update-prefix") . " " . Language::translateMessage("update-retry"));
			$plugin->getServer()->getAsyncPool()->submitTask(new GithubUpdateTask(AntiProxy::getInstance()->getDescription()->getName(), AntiProxy::getInstance()->getDescription()->getVersion()));
			$this->cancelRun();
			return;
		}

		if ($highestVersion !== $this->pluginVersion) {
			Server::getInstance()->getLogger()->warning(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("new-update-found"), [$highestVersion, $api]));
			Server::getInstance()->getLogger()->warning(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("new-update-details"), [$api_from, $api_to]));
			Server::getInstance()->getLogger()->warning(Language::translateMessage("new-update-prefix") . " " . vsprintf(Language::translateMessage("new-update-download"), [$artifactUrl]));
			$this->cancelRun();
		} else {
			Server::getInstance()->getLogger()->notice(Language::translateMessage("new-update-prefix") . " " . Language::translateMessage("no-updates-found"));
			$this->cancelRun();
		}
	}
}
