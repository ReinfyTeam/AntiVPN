<?php

declare(strict_types=1);

use pocketmine\permission\Permission;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use ReinfyTeam\AntiVPN\AntiProxy;

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../src/Curl.php";
require __DIR__ . "/../src/AntiProxy.php";

final class AntiVPNTestPlayer extends Player{
	public function __construct(private bool $bypassed){
	}

	public function hasPermission(Permission|string $name) : bool{
		return $this->bypassed && $name === "antivpn.bypass";
	}

	public function getName() : string{
		return "PermissionPlayer";
	}

	public function __destruct(){
	}
}

final class AntiVPNTestServer extends Server{
	public function __construct(private ?Player $player){
	}

	public function isOp(string $name) : bool{
		return false;
	}

	public function getPlayerExact(string $name) : ?Player{
		return $this->player;
	}
}

function expectBypass(bool $condition, string $message) : void{
	if(!$condition){
		throw new RuntimeException($message);
	}
}

$configPath = tempnam(sys_get_temp_dir(), "antivpn-config-");
if($configPath === false){
	throw new RuntimeException("Unable to create temporary AntiVPN config");
}
file_put_contents($configPath, "bypass-permission: antivpn.bypass\nbypass-players: []\nbypass-ip: []\n");

$plugin = (new ReflectionClass(AntiProxy::class))->newInstanceWithoutConstructor();
$serverProperty = new ReflectionProperty(\pocketmine\plugin\PluginBase::class, "server");
$serverProperty->setValue($plugin, new AntiVPNTestServer(new AntiVPNTestPlayer(true)));
$configProperty = new ReflectionProperty(\pocketmine\plugin\PluginBase::class, "config");
$configProperty->setValue($plugin, new Config($configPath, Config::YAML));
$bypassMethod = new ReflectionMethod(AntiProxy::class, "isBypassed");
$bypassMethod->setAccessible(true);

expectBypass($bypassMethod->invoke($plugin, "PermissionPlayer", "198.51.100.10") === true, "Configured bypass permission was not honored");

$serverProperty->setValue($plugin, new AntiVPNTestServer(new AntiVPNTestPlayer(false)));
expectBypass($bypassMethod->invoke($plugin, "PermissionPlayer", "198.51.100.10") === false, "Unpermitted player was incorrectly bypassed");

@unlink($configPath);
echo "PASS: AntiVPN bypass permission is honored only for permitted online players\n";
