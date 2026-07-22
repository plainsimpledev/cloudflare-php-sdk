# Repository Guide

## Ground Truth

- PHP library, not a workspace: `PlainSimple\Cloudflare\` maps to `src/`; tests are global-namespace classes loaded through Composer classmap.
- Runtime minimum and Composer platform are PHP 8.3; CI executes on PHP 8.3 and 8.4.
- Executable `src/` signatures are truth. `Client` accepts `AdapterInterface`; `Client::withApiToken()` builds `GuzzleAdapter`. Accessors: `accounts()`, `zones()`, `dnsRecords()`, `zoneSettings()`, `rulesets()`.
- Endpoint results are wrappers: one entity via `getEntity()`, lists via `getItems()`, actions via `getResult()`, raw export via `getBody()`. Wrappers are not iterable.

## Entity Mapper

- `makeFromCloudflareData()` records present fields, normalizes known fields through setters, preserves unknown response fields as additional attributes, then marks the entity clean.
- Setters mark fields present and dirty. `markClean()` clears dirty state only. Nested dirty entities are detected for PATCH payloads.
- `toCreatePayload()` emits present `CREATE_FIELDS`; `toPatchPayload()` emits dirty `PATCH_FIELDS`; `toReplacePayload()` emits all present `REPLACE_FIELDS`. Enums, dates, nested entities, and arrays normalize recursively.
- Factories establish valid write intent: `Account::forCreate`, `Zone::forCreate`, `DnsRecord::forCreate`, `Ruleset::forCreate`, `ZoneSetting::forUpdate`, `ZoneSetting::forEnabledUpdate`, `AccountReference::forId`.
- Endpoint create/update responses hydrate new clean entities. Continue lifecycle with returned entity, mutate through setters, then call update/delete.
- Resource rules override generic semantics: Accounts PUT present replacement fields; Zones PATCH exactly one dirty writable field; DNS PATCH adds required `name`/`ttl`/`type`; Zone Settings PATCH exactly one non-null `value` or `enabled`.

## Transport

- `AdapterInterface` exposes GET, POST, PUT, PATCH, DELETE, and multipart POST. GET data is query; non-null data for other ordinary verbs is JSON.
- `GuzzleAdapter` accepts `AuthInterface`, base URI, then Guzzle options. It rejects absolute/scheme-relative endpoint URLs and preserves required auth plus `Accept` headers against case-insensitive overrides.
- Endpoints encode every external path segment. Keep URLs relative and do not concatenate unencoded IDs.
- `AbstractEndpoint` accepts only 2xx responses with `success: true` for JSON envelopes. HTTP or envelope failures throw `ErrorResponseException`; malformed successful JSON throws `JsonException`.
- Parsing rewinds seekable streams and replaces consumed non-seekable streams, so wrapper `getOriginalResponse()` retains readable body content.
- JSON envelope parsing permits empty successful bodies only for action responses. Raw DNS export bypasses envelope parsing.

## Resource Hazards

- DNS export requests `text/plain`; import uses multipart. Scan may return empty action bodies, and delete may return a sparse 2xx envelope without `success`. `include_shadow_metadata` for create/update/overwrite/batch is placed in URL because adapter query arguments exist only on GET.
- DNS simple records write `content`; structured records write `data`. PATCH requires a dirty writable field and still sends `name`, `ttl`, `type`; PUT sends all present writable fields. Batch patches/puts require entity IDs.
- Every Rulesets call requires `RulesetScope::zone()` or `RulesetScope::account()`. Scope availability still depends on Cloudflare phase/product rules.
- Ruleset and entrypoint replacement is PUT. It sends explicitly present non-null `description`/`rules`; hydrate current state first when preserving rules matters.
- Rule PATCH uses all present writable definition fields, not dirty-only fields. Definition changes require `action` and `expression`; position-only changes use an empty `Rule` plus `RulePosition`.
- Rule create/update/delete responses contain the resulting `Ruleset`, not a standalone `Rule`.

## Commands

- Install exactly as CI: `composer install --prefer-dist --no-progress`.
- CI order: `composer validate --strict`, install, `composer lint`, `composer analyse`, `composer test`. `composer check` runs only the final three, in that order, and stops at first failure.
- Apply configured style: `composer lint-fix`; PHP CS Fixer scans only `src/` and `tests/`.
- Focus endpoint tests: `./vendor/bin/phpunit --no-coverage tests/Endpoints`.
- Focus one file: `./vendor/bin/phpunit --no-coverage tests/Endpoints/RulesetsTest.php`.
- Focus one method: `./vendor/bin/phpunit --no-coverage --filter testCreatesRulesetWithCreatePayload tests/Endpoints/RulesetsTest.php`.
- Tests use mocked transports and require no network. `composer test` requests text coverage and needs a coverage driver.
- Devcontainer defaults to `XDEBUG_MODE=debug`, not coverage. Run full tests there with `XDEBUG_MODE=coverage composer test`, or from host with `docker compose -f .devcontainer/compose.yml run --rm -e XDEBUG_MODE=coverage app composer test`.
- Devcontainer runs as root and bind-mounts the repo; it can leave `vendor/` root-owned. If host Composer gets permission errors, run Composer through the devcontainer instead of partially updating `vendor/` on host.

## Change Conventions

- New PHP files use `declare(strict_types=1)` per `CONTRIBUTING.md`; a few legacy omissions are not precedent.
- Entity/list fixtures need `success: true` and correctly shaped `result`; action results may be absent. `ListResponse` accepts page pagination, cursor pagination, or no `result_info`.
- Add writable API fields to entity properties/setters and correct create/patch/replace allowlists. Read-only or unknown hydrated fields must not leak into writes.
- Record user-visible changes under `CHANGELOG.md`'s `[Unreleased]` section.
