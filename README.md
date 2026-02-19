# Cloudflare PHP SDK

[![CI](https://github.com/plainsimple/cloudflare-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/plainsimple/cloudflare-php-sdk/actions)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-777BB4.svg)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

A PHP library for interacting with [Cloudflare API v4](https://developers.cloudflare.com/api/). This SDK simplifies making requests to Cloudflare's API, managing zones, DNS records, and other resources.

## Supported Endpoints

- ✅ **Accounts** - Full CRUD operations
- 🔄 **Zones** - Coming soon
- 🔄 **Rulesets** - Coming soon
- 🔄 **Rules** - Coming soon
- 🔄 **Turnstiles** - Coming soon

## Requirements

- PHP >= 8.3
- [ext-json](https://www.php.net/manual/en/book.json.php)
- [Guzzle](https://github.com/guzzle/guzzle) for HTTP requests

## Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require plainsimple/cloudflare-sdk-php
```

## Usage

### Initialize the Client

```php
use PlainSimple\Cloudflare\Client;

$client = new Client('your-cloudflare-api-token');
```

### List Accounts

```php
$accounts = $client->accounts()->list();

foreach ($accounts as $account) {
    echo $account->name . PHP_EOL;
}
```

### Create an Account

```php
$account = $client->accounts()->create([
    'name' => 'My Account',
    'type' => 'standard',
]);

echo $account->id;
```

### Get Account by ID

```php
$account = $client->accounts()->get('account-id');
echo $account->name;
```

### Update Account

```php
$account = $client->accounts()->update('account-id', [
    'name' => 'Updated Name',
]);
```

### Delete Account

```bash
$client->accounts()->delete('account-id');
```

## Configuration

The client uses `Guzzle` under the hood. You can customize the HTTP client if needed:

```php
use PlainSimple\Cloudflare\Adapters\GuzzleAdapter;
use PlainSimple\Cloudflare\Client;

$adapter = new GuzzleAdapter(['timeout' => 5]);
$client = new Client('your-cloudflare-api-token', $adapter);
```

## Development

### Setup

```bash
git clone https://github.com/plainsimple/cloudflare-php-sdk.git
cd cloudflare-php-sdk
composer install
```

### Available Commands

```bash
# Run all checks (lint, analyse, test)
composer check

# Run tests only
composer test

# Run tests with HTML coverage report
composer test-coverage

# Check code style
composer lint

# Fix code style automatically
composer lint-fix

# Run static analysis
composer analyse

# Generate API documentation
composer docs
```

### Code Quality

This project maintains 100% code coverage and uses:
- **PHPStan** (level 7) for static analysis
- **PHP CS Fixer** for code style enforcement
- **PHPUnit** for testing

## Architecture

This SDK follows several design patterns:

- **Adapter Pattern** - HTTP client abstraction (GuzzleAdapter implements AdapterInterface)
- **Repository Pattern** - Endpoints act as repositories (Accounts, Zones, etc.)
- **Factory Pattern** - Entities use `makeFromCloudflareData()` to hydrate from API responses
- **Entity Pattern** - Magic property access with camelCase to snake_case conversion

See [AGENTS.md](AGENTS.md) for detailed guidelines on adding new endpoints.

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run checks: `composer check`
4. Commit your changes (`git commit -m 'Add some amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

