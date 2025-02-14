# Cloudflare PHP SDK

A PHP library for interacting with [Cloudflare API v4](https://developers.cloudflare.com/api/). This SDK simplifies making requests to Cloudflare’s API, managing zones, DNS records, and other resources.

## Supported endpoints
- Zones
- Rulesets
- Rules
- Turnstiles
- Accounts

## Requirements
- PHP >= 8.3
- [ext-json](https://www.php.net/manual/en/book.json.php)
- [Guzzle](https://github.com/guzzle/guzzle) for HTTP requests

## Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require your-vendor/cloudflare-php-sdk
```

## Usage

### Initialize the Client

```php
use YourVendor\Cloudflare\Client;

$client = new Client('your-cloudflare-api-token');
```

### List Zones

```php
$zones = $client->zones()->list();

foreach ($zones as $zone) {
    echo $zone['name'] . PHP_EOL;
}
```

### Create a DNS Record

```php
$dnsRecord = $client->dns()->create('zone-id', [
    'type' => 'A',
    'name' => 'example.com',
    'content' => '192.0.2.1',
    'ttl' => 3600,
]);

print_r($dnsRecord);
```

### Purge Cache

```php
$response = $client->cache()->purge('zone-id');
print_r($response);
```

## Configuration

The client uses `Guzzle` under the hood. You can customize the HTTP client if needed:

```php
use GuzzleHttp\Client as GuzzleClient;
use YourVendor\Cloudflare\Client;

$guzzle = new GuzzleClient(['timeout' => 5]);
$client = new Client('your-cloudflare-api-token', $guzzle);
```

## Testing

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add some feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

