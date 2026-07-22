# Cloudflare PHP SDK

[![CI](https://github.com/plainsimple/cloudflare-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/plainsimple/cloudflare-php-sdk/actions)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-777BB4.svg)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

PHP 8.3+ client for the [Cloudflare API v4](https://developers.cloudflare.com/api/).

## Supported API

- **Accounts:** `list`, `get`, `create`, `update`, `delete`.
- **Zones:** `list`, `get`, `create`, `update`, `delete`, `rerunActivationCheck`.
- **DNS Records:** `list`, `get`, `create`, `update` (PATCH), `overwrite` (PUT), `delete`, `export`, `import`, legacy `scan`, `triggerScan`, `listScanned`, `reviewScan`, `batch`.
- **Zone Settings:** `list`, `get`, `update`, `updateMany`.
- **Rulesets:** account and zone scopes; `list`, `get`, `create`, `replace`, `delete`; `getEntrypoint`, `replaceEntrypoint`; `listEntrypointVersions`, `getEntrypointVersion`; `listVersions`, `getVersion`, `getVersionByTag`, `deleteVersion`; `createRule`, `updateRule`, `deleteRule`.

## Requirements

- PHP >= 8.3
- `ext-json`
- Guzzle 7.15.1+

## Installation

```bash
composer require plainsimple/cloudflare-sdk-php
```

## Client

Use the token factory for the standard transport:

```php
use PlainSimple\Cloudflare\Client;

$client = Client::withApiToken($_ENV['CLOUDFLARE_API_TOKEN']);
```

The factory also accepts a base URI and Guzzle client options:

```php
$client = Client::withApiToken(
    $_ENV['CLOUDFLARE_API_TOKEN'],
    'https://api.cloudflare.com/client/v4',
    ['timeout' => 5.0],
);
```

Inject the bundled adapter, or any `AdapterInterface` implementation, when transport construction belongs to the application:

```php
use PlainSimple\Cloudflare\Adapters\GuzzleAdapter;
use PlainSimple\Cloudflare\Auth\ApiToken;
use PlainSimple\Cloudflare\Client;

$adapter = new GuzzleAdapter(
    new ApiToken($_ENV['CLOUDFLARE_API_TOKEN']),
    'https://api.cloudflare.com/client/v4',
    ['timeout' => 5.0],
);
$client = new Client($adapter);
```

## Responses And Entities

Endpoints return response wrappers. Read one entity with `getEntity()`, lists with `getItems()`, action data with `getResult()`, and raw export text with `getBody()`. Wrappers are not iterable.

```php
$account = $client->accounts()->get('account-id')->getEntity();

foreach ($client->accounts()->list()->getItems() as $listedAccount) {
    echo $listedAccount->getName() . PHP_EOL;
}
```

The SDK uses a Data Mapper design. Create-purpose entity factories set writable create fields. API responses hydrate new clean entities. Setters mark fields dirty; endpoints serialize each resource's patch or replacement contract.

```php
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Enums\AccountType;

$draft = Account::forCreate('Example Account', AccountType::Standard);
$account = $client->accounts()->create($draft)->getEntity();

$account->setName('Renamed Account');
$account = $client->accounts()->update($account)->getEntity();

$client->accounts()->delete($account);
```

Use `forCreate()` on `Account`, `Zone`, `DnsRecord`, and `Ruleset`. Zone settings provide `forUpdate()` and `forEnabledUpdate()`. Prefer returned entities after create/update calls: they contain server IDs and are clean for later mutation.

## Accounts

```php
$accounts = $client->accounts()->list(
    name: 'Example Account',
    page: 1,
    perPage: 20,
)->getItems();

$account = $client->accounts()->get('account-id')->getEntity();
echo $account->getName();
```

`update()` performs a PUT from present replacement fields. `delete()` accepts an `Account` or account ID and returns an `ActionResponse`.

## Zones

```php
use PlainSimple\Cloudflare\Entities\Zone;
use PlainSimple\Cloudflare\Enums\ZoneStatus;
use PlainSimple\Cloudflare\Enums\ZoneType;
use PlainSimple\Cloudflare\ValueObjects\ZoneListQuery;

$zones = $client->zones()->list(new ZoneListQuery(
    accountId: 'account-id',
    status: ZoneStatus::Active,
    types: [ZoneType::Full],
))->getItems();

$zone = $client->zones()->create(
    Zone::forCreate('example.com', 'account-id', ZoneType::Full),
)->getEntity();

$zone->setPaused(true);
$zone = $client->zones()->update($zone)->getEntity();
$client->zones()->rerunActivationCheck($zone);
```

A zone update requires exactly one dirty writable field: `paused`, `type`, or `vanity_name_servers`.

## DNS Records

```php
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Enums\DnsRecordType;
use PlainSimple\Cloudflare\ValueObjects\DnsRecordListQuery;

$records = $client->dnsRecords()->list('zone-id', new DnsRecordListQuery(
    name: ['exact' => 'www.example.com'],
    type: DnsRecordType::A,
))->getItems();

$draft = DnsRecord::forCreate(
    DnsRecordType::A,
    'www.example.com',
    '192.0.2.1',
    300,
);
$draft->setProxied(true);
$record = $client->dnsRecords()->create('zone-id', $draft)->getEntity();

$record->setContent('192.0.2.2');
$record = $client->dnsRecords()->update('zone-id', $record)->getEntity();
$client->dnsRecords()->delete('zone-id', $record);
```

`update()` sends dirty writable fields plus required `name`, `ttl`, and `type`. `overwrite()` sends all present writable fields. Simple record types use `content`; structured types use `data`.

Import, scan review, and batch operations use dedicated value objects. Export returns a raw response body:

```php
use PlainSimple\Cloudflare\ValueObjects\DnsBatch;
use PlainSimple\Cloudflare\ValueObjects\DnsImport;
use PlainSimple\Cloudflare\ValueObjects\DnsScanReview;

$bindText = $client->dnsRecords()->export('zone-id')->getBody();
$importResult = $client->dnsRecords()->import(
    'zone-id',
    new DnsImport($bindText, 'example.com.bind', proxied: false),
)->getResult();

$client->dnsRecords()->triggerScan('zone-id');
$scanned = $client->dnsRecords()->listScanned('zone-id')->getItems();
$client->dnsRecords()->reviewScan('zone-id', new DnsScanReview(
    accepts: $scanned,
    rejects: ['rejected-record-id'],
));

$batchResult = $client->dnsRecords()->batch(
    'zone-id',
    new DnsBatch(
        deletes: ['old-record-id'],
        posts: [DnsRecord::forCreate('A', 'new.example.com', '192.0.2.10')],
    ),
)->getResult();
```

## Zone Settings

```php
use PlainSimple\Cloudflare\Entities\ZoneSetting;

$settings = $client->zoneSettings()->list('zone-id')->getItems();

$ssl = $client->zoneSettings()->get('zone-id', 'ssl')->getEntity();
$ssl->setValue('full');
$ssl = $client->zoneSettings()->update('zone-id', $ssl)->getEntity();

$client->zoneSettings()->updateMany('zone-id', [
    ZoneSetting::forUpdate('always_use_https', 'on'),
    ZoneSetting::forEnabledUpdate('ssl_recommender', true),
]);
```

Each update requires exactly one non-null dirty field. Use `enabled` only for `ssl_recommender`; all other settings use `value`.

## Rulesets

Every operation takes an explicit account or zone `RulesetScope`:

```php
use PlainSimple\Cloudflare\ValueObjects\RulesetListQuery;
use PlainSimple\Cloudflare\ValueObjects\RulesetScope;

$zoneScope = RulesetScope::zone('zone-id');
$accountScope = RulesetScope::account('account-id');

$zoneRulesets = $client->rulesets()->list($zoneScope)->getItems();
$accountRulesets = $client->rulesets()->list(
    $accountScope,
    new RulesetListQuery(perPage: 25),
)->getItems();
```

Create a zone custom WAF ruleset with an embedded rule:

```php
use PlainSimple\Cloudflare\Entities\Rule;
use PlainSimple\Cloudflare\Entities\Ruleset;
use PlainSimple\Cloudflare\Enums\RuleAction;
use PlainSimple\Cloudflare\Enums\RulesetKind;
use PlainSimple\Cloudflare\Enums\RulesetPhase;

$wafRule = new Rule();
$wafRule->setRef('block-admin');
$wafRule->setDescription('Block the admin path');
$wafRule->setAction(RuleAction::Block);
$wafRule->setExpression('(http.request.uri.path eq "/admin")');
$wafRule->setEnabled(true);

$wafRuleset = Ruleset::forCreate(
    'Custom WAF',
    RulesetKind::Custom,
    RulesetPhase::HttpRequestFirewallCustom,
    'Application firewall rules',
    [$wafRule],
);
$wafRuleset = $client->rulesets()->create($zoneScope, $wafRuleset)->getEntity();
```

Add a cache rule to a zone cache entrypoint:

```php
$cacheRule = new Rule();
$cacheRule->setRef('cache-static-assets');
$cacheRule->setDescription('Cache static assets for one day');
$cacheRule->setAction(RuleAction::SetCacheSettings);
$cacheRule->setExpression('(http.request.uri.path.extension in {"css" "js"})');
$cacheRule->setActionParameters([
    'cache' => true,
    'edge_ttl' => [
        'mode' => 'override_origin',
        'default' => 86400,
    ],
]);
$cacheRule->setEnabled(true);

$cacheEntrypoint = $client->rulesets()->getEntrypoint(
    $zoneScope,
    RulesetPhase::HttpRequestCacheSettings,
)->getEntity();
$cacheEntrypoint = $client->rulesets()->createRule(
    $zoneScope,
    $cacheEntrypoint->getId(),
    $cacheRule,
)->getEntity();
```

`replace()` and `replaceEntrypoint()` use PUT with explicitly present non-null `description` and `rules`. A rule definition PATCH serializes all present writable rule fields, not only dirty fields, and requires `action` plus `expression`. For position-only updates, pass an empty `Rule` with a `RulePosition`.

## Development

Install exactly as CI:

```bash
composer install --prefer-dist --no-progress
```

Current Composer scripts:

```bash
composer lint          # php-cs-fixer fix --dry-run --diff
composer lint-fix      # php-cs-fixer fix
composer analyse       # phpstan analyse --memory-limit=512M
composer test          # phpunit --coverage-text
composer test-coverage # phpunit --coverage-html coverage
composer docs          # phpdoc
composer check         # lint, analyse, test
```

CI runs `composer validate --strict`, installation, `composer lint`, `composer analyse`, then `composer test` on PHP 8.3 and 8.4. Tests use mocked transports and require no network access.

The devcontainer defaults to `XDEBUG_MODE=debug`. Enable coverage for coverage-producing scripts:

```bash
XDEBUG_MODE=coverage composer test
docker compose -f .devcontainer/compose.yml run --rm -e XDEBUG_MODE=coverage app composer test
```

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md), then run `composer check` before opening a pull request. See [CHANGELOG.md](CHANGELOG.md) for release history and [AGENTS.md](AGENTS.md) for repository-specific implementation notes.

## License

Licensed under the [MIT License](LICENSE).
