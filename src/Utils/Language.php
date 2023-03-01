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

namespace ReinfyTeam\AntiVPN\Utils;

use pocketmine\utils\Config;
use ReinfyTeam\AntiVPN\AntiProxy;
use function file_exists;

class Language {
	public static function getLanguage() : Config {
		return new Config(AntiProxy::getInstance()->getDataFolder() . "langs/" . Language::getSelectedLanguage() . ".yml");
	}

	public static function getSelectedLanguage() : string {
		return AntiProxy::getInstance()->getConfig()->get("lang");
	}

	/**
	 * Translate Message from Language Configuration
	 * Do not call it directly.
	 */
	public static function translateMessage(mixed $option) : mixed {
		$lang = Language::getLanguage();
		$plugin = AntiProxy::getInstance();
		/** Check if selected language is missing. **/
		if (!file_exists($plugin->getDataFolder() . "langs/" . Language::getSelectedLanguage() . ".yml")) {
			throw new \Exception("Missing file in " . $plugin->getDataFolder() . "langs/" . Language::getSelectedLanguage() . ".yml");
		}

		/** Check if option is exist. **/
		if ($lang->get($option) === false) {
			throw new \Exception("Trying to access on null.");
		}

		return PluginUtils::colorize($lang->get($option));
	}

	public static function init() : void {
		if (!file_exists(AntiProxy::getInstance()->getDataFolder() . "language/" . Language::getSelectedLanguage() . ".yml")) {
			AntiProxy::getInstance()->saveResource("languages/" . $this->getSelectedLanguage() . ".yml");
		}
	}
}
