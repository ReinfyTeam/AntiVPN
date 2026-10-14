<?php

declare(strict_types=1);

namespace ReinfyTeam\AntiVPN;

use JsonException;

/**
 * Parses the documented VPNAPI.io response without making a security decision
 * for incomplete or malformed data.
 */
final class VpnApiResponse
{
	/**
	 * @return array{vpn: bool, proxy: bool, tor: bool, relay: bool}|null
	 */
	public static function parse(string $body) : ?array
	{
		try {
			$decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}

		if (!is_array($decoded) || !isset($decoded['security']) || !is_array($decoded['security'])) {
			return null;
		}

		$security = $decoded['security'];
		foreach (['vpn', 'proxy', 'tor', 'relay'] as $field) {
			if (!array_key_exists($field, $security) || !is_bool($security[$field])) {
				return null;
			}
		}

		return [
			'vpn' => $security['vpn'],
			'proxy' => $security['proxy'],
			'tor' => $security['tor'],
			'relay' => $security['relay'],
		];
	}

	/** @param array{vpn: bool, proxy: bool, tor: bool, relay: bool} $classification */
	public static function isBlocked(array $classification) : bool
	{
		return $classification['vpn'] || $classification['proxy'] || $classification['tor'] || $classification['relay'];
	}
}
