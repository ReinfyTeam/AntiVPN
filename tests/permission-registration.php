<?php

declare(strict_types=1);

use pocketmine\permission\PermissionManager;
use pocketmine\utils\Config;
use ReinfyTeam\AntiVPN\AntiProxy;

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../src/Curl.php";
require __DIR__ . "/../src/AntiProxy.php";

function expectPermissionRegistration(bool $condition, string $message) : void{
	if(!$condition){
		throw new RuntimeException($message);
	}
}

$configPath = tempnam(sys_get_temp_dir(), "antivpn-config-");
if($configPath === false){
	throw new RuntimeException("Unable to create temporary AntiVPN config");
}

file_put_contents($configPath, "bypass-permission: antivpn.test-bypass\n");

$plugin = (new ReflectionClass(AntiProxy::class))->newInstanceWithoutConstructor();
$configProperty = new ReflectionProperty(\pocketmine\plugin\PluginBase::class, "config");
$configProperty->setValue($plugin, new Config($configPath, Config::YAML));
$registerMethod = new ReflectionMethod(AntiProxy::class, "registerBypassPermission");
$registerMethod->setAccessible(true);
$permissionManager = PermissionManager::getInstance();
$permissionManager->removePermission("antivpn.test-bypass");

$registerMethod->invoke($plugin);

expectPermissionRegistration($permissionManager->getPermission("antivpn.test-bypass") !== null, "Configured bypass permission was not registered");

$permissionManager->removePermission("antivpn.test-bypass");
@unlink($configPath);
echo "PASS: AntiVPN registers the configured bypass permission\n";
