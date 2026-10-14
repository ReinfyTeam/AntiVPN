<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/VpnApiResponse.php';

use ReinfyTeam\AntiVPN\VpnApiResponse;

function expect(bool $condition, string $message) : void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$classification = VpnApiResponse::parse(json_encode([
	'security' => ['vpn' => false, 'proxy' => true, 'tor' => false, 'relay' => false],
], JSON_THROW_ON_ERROR));
expect($classification !== null, 'Documented security response was rejected');
expect(VpnApiResponse::isBlocked($classification), 'Proxy response was not blocked');

expect(VpnApiResponse::parse('{broken') === null, 'Malformed JSON was accepted');
expect(VpnApiResponse::parse('{"security":{"vpn":true}}') === null, 'Incomplete security object was accepted');
expect(VpnApiResponse::parse('{"security":{"vpn":"true","proxy":false,"tor":false,"relay":false}}') === null, 'Non-boolean security field was accepted');
expect(!VpnApiResponse::isBlocked(['vpn' => false, 'proxy' => false, 'tor' => false, 'relay' => false]), 'Clean response was blocked');

echo "PASS: VPNAPI.io response contract and fail-safe parser\n";
