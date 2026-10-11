# AntiVPN

AntiVPN is a PocketMine-MP plugin that checks player connections for proxy, VPN,
Tor and relay use through the configured online IP reputation service.

## Features

- Starts an IP reputation check when a player begins logging in.
- Kicks a player identified as proxy, VPN, Tor or relay traffic once the
  asynchronous result is available and the player session can be addressed.
- Supports a configurable bypass IP list.
- Uses asynchronous HTTP requests so the check does not block the main server
  thread.

## Requirements

- PocketMine-MP API 5.0.0 or a compatible API 5 server.
- Outbound HTTPS access to [VPNAPI.io](https://vpnapi.io/).

AntiVPN depends on an external service. Its result, availability and request
limits are controlled by that service; it is not an offline IP database.
Transient failures and unusable responses are retried with increasing delays,
up to three retries after the initial request. If the service remains
unavailable, AntiVPN records the failure and does not classify that connection.
Because the service request is asynchronous, a blocking result may be applied
just after login rather than through the synchronous pre-login event. A result
received before the player session is available is retained until the player
joins, so it is not silently discarded.

## Installation

1. Download the plugin PHAR from a project release when one is available.
2. Copy `AntiVPN.phar` into the server's `plugins` directory.
3. Start the server once so it creates `plugin_data/AntiVPN/config.yml`.
4. Review the API key, bypass IPs and kick message before enabling the check for
   players.

The configuration file is the source of truth for the available options. Keep
API keys private and avoid committing a configured copy of the file.

## Configuration

The generated configuration supports:

- `api-key`: optional VPNAPI.io key.
- `kick-message`: message sent when a connection is classified as proxy, VPN,
  Tor or relay traffic.
- `bypass-ip`: IP addresses that should not be checked.
- `bypass-players`: player names that should not be checked.

## Development

The project uses [DevTools](https://github.com/NhanAZ/DevTools) to prepare and
validate the plugin PHAR. Static analysis is run with PHPStan at its maximum
configured level. To reproduce the repository checks, install the Composer
development dependencies and run:

```text
composer install
composer analyse
```

## License

AntiVPN is distributed under the [LGPL-2.1-only](LICENSE) license.

Copyright ReinfyTeam.
