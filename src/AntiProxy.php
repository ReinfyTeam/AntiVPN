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

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use ReinfyTeam\AntiVPN\Commands\DefaultCommand;
use ReinfyTeam\AntiVPN\Tasks\PoggitUpdateTask;
use ReinfyTeam\AntiVPN\Utils\Language;
use function fclose;
use function file_exists;
use function mkdir;
use function rename;
use function stream_get_contents;
use function unlink;
use function yaml_parse;

class AntiProxy extends PluginBase {
	use SingletonTrait;

	public static bool $enabled = true;

	public function onLoad() : void {
		AntiProxy::$instance = $this;
		$this->checkConfig();
		$this->saveResources();
		$this->checkUpdates();
		$this->registerPermissions();
	}

	public function onEnable() : void {
		$this->registerCommands();
		$this->loadListener();
		$this->registerPermissions();
	}

	private function checkConfig() : void {
		$log = $this->getLogger();
		$pluginConfigResource = $this->getResource("config.yml");
		$lang = new Language();
		$pluginConfig = yaml_parse(stream_get_contents($pluginConfigResource));
		fclose($pluginConfigResource);
		$config = $this->getConfig();

		if ($pluginConfig == false) {
			$log->critical("Invalid Configuration Syntax, Please remove your update the plugin.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		if ($config->get("config-version") === $pluginConfig["config-version"]) {
			return;
		}

		$log->notice($lang->translateMessage("outdated-config"));
		@rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "old-config.yml");
		@unlink($this->getDataFolder() . "old-config.yml");
		$this->saveResource("config.yml");
	}

	private function checkUpdates() : void {
		$lang = new Language();
		if ((bool) ($this->getConfig()->get("check-updates") ?? true)) {
			$this->getServer()->getAsyncPool()->submitTask(new PoggitUpdateTask($this->getDescription()->getName(), $this->getDescription()->getVersion()));
		} else {
			$this->getServer()->getLogger()->warning($lang->translateMessage("new-update-prefix") . " " . $lang->translateMessage("update-warning"));
		}
	}

	private function registerPermissions() : void {
		$this->registerPermission(($this->getConfig()->get("bypass-permission") ?? "antiproxy.bypass"));
		$this->registerPermission(($this->getConfig()->get("command-permission") ?? "antiproxy.admin.command"));
	}

	private function loadListener() : void {
		$this->getServer()->getPluginManager()->registerEvents(new ProxyListener(), $this);
	}

	/**
	 * Initilize the resource in the context.
	 */
	private function saveResources() : void {
		if (!file_exists($this->getDataFolder() . "langs/")) {
			@mkdir($this->getDataFolder() . "langs/");
		}
		$this->saveResource("langs/eng.yml");
		if (!file_exists($this->getDataFolder() . "discord-webhook.yml")) {
			$this->saveResource("discord-webhook.yml");
		}

		foreach ($this->getResources() as $file) {
			$this->saveResource($file->getFilename());
		}
	}

	private function registerPermission(string $perm) : void {
		$permission = new Permission($perm);
		$permManager = PermissionManager::getInstance();
		$permManager->addPermission($permission);
		$permManager->getPermission(DefaultPermissions::ROOT_OPERATOR)->addChild($permission->getName(), true);
	}

	private function registerCommands() {
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new DefaultCommand());
	}
}