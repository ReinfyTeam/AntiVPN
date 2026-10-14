<?php

declare(strict_types=1);

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use ReinfyTeam\AntiVPN\AntiProxy;

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../src/Curl.php";
require __DIR__ . "/../src/AntiProxy.php";

final class AntiVPNConfigurationTestServer extends Server{
	public function __construct(){
	}

	public function isOp(string $name) : bool{
		return false;
	}

	public function getPlayerExact(string $name) : ?Player{
		return null;
	}
}

$configPath = tempnam(sys_get_temp_dir(), "antivpn-config-");
if($configPath === false){
	throw new RuntimeException("Unable to create temporary AntiVPN config");
}

file_put_contents($configPath, "api-key: \"\"\nbypass-players: []\nbypass-ip: []\n");

$plugin = (new ReflectionClass(AntiProxy::class))->newInstanceWithoutConstructor();
$serverProperty = new ReflectionProperty(\pocketmine\plugin\PluginBase::class, "server");
$serverProperty->setValue($plugin, new AntiVPNConfigurationTestServer());
$configProperty = new ReflectionProperty(\pocketmine\plugin\PluginBase::class, "config");
$configProperty->setValue($plugin, new Config($configPath, Config::YAML));

// A missing key must return before Curl is used; Curl is intentionally not registered in this unit test.
$plugin->checkVPN("MissingKeyPlayer", "198.51.100.10");

@unlink($configPath);
echo "PASS: AntiVPN skips classification when the VPNAPI.io API key is missing\n";
