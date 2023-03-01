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

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_keys;
use function array_values;
use function is_bool;
use function str_replace;
use function strval;

final class PluginUtils {
	/**
	 * Colorise Messages turns & to § and etc.
	 */
	public static function colorize(string $message) : string {
		$replacements = [
			"&" => "§",
			"{BLACK}" => TextFormat::BLACK,
			"{DARK_BLUE}" => TextFormat::DARK_BLUE,
			"{DARK_GREEN}" => TextFormat::DARK_GREEN,
			"{DARK_AQUA}" => TextFormat::DARK_AQUA,
			"{DARK_RED}" => TextFormat::DARK_RED,
			"{DARK_PURPLE}" => TextFormat::DARK_PURPLE,
			"{GOLD}" => TextFormat::GOLD,
			"{GRAY}" => TextFormat::GRAY,
			"{DARK_GRAY}" => TextFormat::DARK_GRAY,
			"{BLUE}" => TextFormat::BLUE,
			"{GREEN}" => TextFormat::GREEN,
			"{AQUA}" => TextFormat::AQUA,
			"{RED}" => TextFormat::RED,
			"{LIGHT_PURPLE}" => TextFormat::LIGHT_PURPLE,
			"{YELLOW}" => TextFormat::YELLOW,
			"{WHITE}" => TextFormat::WHITE,
			"{OBFUSCATED}" => TextFormat::OBFUSCATED,
			"{BOLD}" => TextFormat::BOLD,
			"{STRIKETHROUGH}" => TextFormat::STRIKETHROUGH,
			"{UNDERLINE}" => TextFormat::UNDERLINE,
			"{ITALIC}" => TextFormat::ITALIC,
			"{RESET}" => TextFormat::RESET,
		];
		$message = str_replace(array_keys($replacements), array_values($replacements), $message);
		return $message;
	}
	/**
	 * Format Message. Dont call it directly.
	 */
	public function formatMessage(string $message, ?Player $player = null) : string {
		$message = str_replace("{type}", $this->getConfig()->get("punishment-type") . "ed", $message);

		// FOR PLAYERS
		if ($player === null) {
			return $message;
		}
		$message = str_replace("{player_name}", "Unknown", $message);
		$message = str_replace("{player_name}", $player->getName(), $message);
		$message = str_replace("{player_ping}", strval($player->getNetworkSession()->getPing()), $message);

		return $message;
	}

	public static function format(string $message, array $options, array $value) : string {
		foreach ($value as $v) {
			foreach ($options as $o) {
				$message = str_replace($o, $v, $message);
			}
		}
		return $message;
	}

	public static function assumeNotFalse(mixed $given, string $message = "This line should be not false. PLEASE REPORT THIS TO THE DEVELOPER.", bool $invert = false) {
		if (is_bool($given)) {
			if (!$given) {
				throw new \RuntimeException($message); // assume not false ;(
			}
		}
	}
}
