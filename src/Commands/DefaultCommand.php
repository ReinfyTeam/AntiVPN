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

namespace ReinfyTeam\AntiVPN\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use ReinfyTeam\AntiVPN\AntiProxy;
use ReinfyTeam\AntiVPN\Tasks\ProxyLookupTask;
use ReinfyTeam\AntiVPN\Utils\Language;
use function count;
use function strtolower;
use function vsprintf;

class DefaultCommand extends Command implements PluginOwned {
	public function getOwningPlugin() : AntiProxy {
		return AntiProxy::getInstance();
	}

	public function __construct() {
		parent::__construct("antivpn", "AntiVPN Administration Management", "/antivpn <help/subcommand>", ["av", "antiproxy", "ap"]);
		$this->setPermission(($this->getOwningPlugin()->getConfig()->get("command-permission") ?? "antivpn.admin.command"));
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (count($args) === 0) {
			$sender->sendMessage(Language::translateMessage("command-usage"));
			return;
		}

		switch(strtolower($args[0])) {
			case "help":
			case "commands":
				$sender->sendMessage(Language::translateMessage("command-help"));
				foreach (Language::translateMessage("command-list") as $list) {
					$sender->sendMessage($list);
				}
				$sender->sendMessage(Language::translateMessage("command-help-1"));
				break;
			case "lookup":
				if (!isset($args[1])) {
					$sender->sendMessage(Language::translateMessage("command-usage-lookup"));
					return;
				} else {
					if (($player = $this->getOwningPlugin()->getServer()->getPlayerExact($args[1])) !== null) {
						$sender->sendMessage(vsprintf(Language::translateMessage("lookup-notice"), [strtolower($args[1])]));
						$this->getOwningPlugin()->getServer()->getAsyncPool()->submitTask(new ProxyLookupTask($player->getName(), $player->getNetworkSession()->getIp()));
					} else {
						$sender->sendMessage(vsprintf(Language::translateMessage("player-not-found"), [$args[1]]));
					}
				}
				break;
			case "ui":
			case "gui":
				if ($sender instanceof Player) {
					//$this->sendForm($sender);
					$sender->sendMessage("Coming soon!");
				} else {
					$sender->sendMessage(Language::translateMessage("not-player"));
				}
				break;
			case "toggle":
			case "switch":
				if (AntiProxy::$enabled) {
					$sender->sendMessage(Language::translateMessage("enabled-check"));
					AntiProxy::$enabled = false;
				} else {
					$sender->sendMessage(Language::translateMessage("disabled-check"));
					AntiProxy::$enabled = true;
				}
				break;
			case "credits":
			case "about":
			case "info":
			case "authors":
				$sender->sendMessage(Language::translateMessage("author-info"));
				foreach ($this->plugin->getDescription()->getAuthors() as $author) {
					$sender->sendMessage("- " . T::GREEN . $author);
				}
				$sender->sendMessage(Language::translateMessage("author-thanks"));
				break;
			default:
				$sender->sendMessage(Language::translateMessage("command-usage"));
				break;
		}
	}

	public function sendForm($player) : void {
		$form = new SimpleForm(function(Player $player, $data) {
			if ($data === null) {
				return;
			}

			switch($data) {
				case 0:
					$this->lookupForm($player);
					break;
				case 1:
					$this->toggleForm($player);
			}
		});

		$form->setTitle(Language::translateMessage("gui-title"));
		$form->setDescription(Language::translateMessage("gui-description"));
		$form->addButton(Language::translateMessage("button-lookup"));
		$form->addButton(Language::translateMessage("button-toggle"));
		$form->sendForm($player);
	}

	public function lookupForm($player) : void {
		$form = new CustomForm(function(Player $player, $data) {
			if ($data === null) {
				return;
			} else {
				if (($player = $this->getOwningPlugin()->getServer()->getPlayerExact($args[0])) !== null) {
					$this->getServer()->getAsyncPool()->submitTask(new ProxyLookupTask($player));
				} else {
					$sender->sendMessage(vsprintf(Language::translateMessage("player-not-found"), [$args[0]]));
				}
			}
		});

		$form->setTitle(Language::translateMessage("gui-title"));
		$form->addInput("", Language::translateMessage("gui-input-playername"), Language::translateMessage("gui-description-lookup"));
		$form->sendForm($player);
	}
}