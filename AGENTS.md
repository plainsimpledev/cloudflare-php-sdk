# Agent Guidelines for Cloudflare PHP SDK

This document provides comprehensive guidelines for AI agents working with this project.

## Project Overview

**Name**: Cloudflare PHP SDK  
**Purpose**: A PHP SDK for interacting with Cloudflare API v4  
**Language**: PHP 8.3+  
**Type**: Library/SDK  
**Namespace**: `PlainSimple\Cloudflare\`  
**PSR-4 Autoloading**: `src/` → `PlainSimple\Cloudflare\`

## Architecture Patterns

### 1. Adapter Pattern (HTTP Client)

The SDK uses the Adapter pattern to abstract HTTP client implementations:

- **Interface**: `AdapterInterface`
- **Implementation**: `GuzzleAdapter` (default)
- **Purpose**: Allow different HTTP clients to be used interchangeably

```php
// Example: Custom adapter implementation
class MyAdapter implements AdapterInterface {
    public function request(string $method, string $uri, array $headers = [], ?string $body = null): ResponseInterface {
        // Your implementation
    }
}
```

### 2. Repository Pattern (Endpoints)

Each API resource has an endpoint class acting as a repository:

- **Base Class**: `AbstractEndpoint`
- **Current Implementations**: `Accounts`
- **Pattern**: Each endpoint handles CRUD operations for its resource

```php
// Adding a new endpoint
class Zones extends AbstractEndpoint {
    public function list(): ListResponse { /* ... */ }
    public function get(string $id): EntityResponse { /* ... */ }
    public function create(array $data): EntityResponse { /* ... */ }
    public function update(string $id, array $data): EntityResponse { /* ... */ }
    public function delete(string $id): bool { /* ... */ }
}
```

### 3. Factory Pattern (Entities)

Entities use a static factory method to create instances from API data:

```php
class Account extends AbstractEntity {
    public static function makeFromCloudflareData(array $data): static {
        $instance = new self();
        $instance->id = $data['id'];
        $instance->name = $data['name'];
        // ... map other fields
        return $instance;
    }
}
```

### 4. Entity Pattern

Entities represent API resources with magic property access:

- **Base Class**: `AbstractEntity`
- **Trait**: `EntityTrait` provides `__get`, `__set`, `__isset`
- **Naming**: Converts snake_case API fields to camelCase PHP properties

## Code Conventions

### PHP Version Requirements
- **Minimum**: PHP 8.3
- **Type Declarations**: Strict mode required (`declare(strict_types=1)`)
- **Readonly Classes**: Use where appropriate for value objects
- **Union Types**: Use when applicable

### Naming Conventions
- **Classes**: PascalCase (e.g., `Account`, `ApiToken`)
- **Methods**: camelCase (e.g., `makeFromCloudflareData`)
- **Properties**: camelCase in code, snake_case in API
- **Constants**: UPPER_SNAKE_CASE
- **Files**: Match class name exactly

### File Organization
```
src/
├── Adapters/          # HTTP client adapters
├── Auth/              # Authentication methods
├── Endpoints/         # API endpoint implementations
├── Entities/          # Data entities
├── Enums/             # PHP enums
├── Exceptions/        # Custom exceptions
├── Responses/         # Response wrappers
├── Traits/            # Reusable traits
├── Utilities/         # Helper classes
└── Client.php         # Main client class

tests/
├── Adapters/          # Adapter tests
├── Auth/              # Authentication tests
└── Endpoints/         # Endpoint tests
```

### Documentation Standards
- **All public methods** must have PHPDoc blocks
- **Class-level** documentation required
- **Type hints** required for all parameters and return types
- **@throws** tags for exceptions

## How to Add New Endpoints

### Step 1: Create Entity

```php
// src/Entities/Zone.php
<?php
declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

readonly class Zone extends AbstractEntity {
    public string $id;
    public string $name;
    public string $status;
    
    public static function makeFromCloudflareData(array $data): static {
        $instance = new self();
        $instance->id = $data['id'];
        $instance->name = $data['name'];
        $instance->status = $data['status'];
        return $instance;
    }
}
```

### Step 2: Create Endpoint

```php
// src/Endpoints/Zones.php
<?php
declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use PlainSimple\Cloudflare\Entities\Zone;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;

readonly class Zones extends AbstractEndpoint {
    
    public function list(): ListResponse {
        $response = $this->adapter->request(
            'GET',
            $this->baseUri . '/zones',
            $this->getAuthHeaders()
        );
        
        return new ListResponse($response, Zone::class);
    }
    
    public function get(string $id): EntityResponse {
        $response = $this->adapter->request(
            'GET',
            $this->baseUri . '/zones/' . $id,
            $this->getAuthHeaders()
        );
        
        return new EntityResponse($response, Zone::class);
    }
    
    public function create(array $data): EntityResponse {
        $response = $this->adapter->request(
            'POST',
            $this->baseUri . '/zones',
            $this->getAuthHeaders(),
            json_encode($data)
        );
        
        return new EntityResponse($response, Zone::class);
    }
    
    public function update(string $id, array $data): EntityResponse {
        $response = $this->adapter->request(
            'PATCH',
            $this->baseUri . '/zones/' . $id,
            $this->getAuthHeaders(),
            json_encode($data)
        );
        
        return new EntityResponse($response, Zone::class);
    }
    
    public function delete(string $id): bool {
        $response = $this->adapter->request(
            'DELETE',
            $this->baseUri . '/zones/' . $id,
            $this->getAuthHeaders()
        );
        
        return $response->getStatusCode() === 200;
    }
}
```

### Step 3: Add to Client

```php
// src/Client.php
public function zones(): Endpoints\Zones {
    return new Endpoints\Zones($this->adapter, $this->auth);
}
```

### Step 4: Create Tests

```php
// tests/Endpoints/ZonesTest.php
<?php
declare(strict_types=1);

namespace PlainSimple\Cloudflare\Tests\Endpoints;

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Auth\AuthInterface;
use PlainSimple\Cloudflare\Endpoints\Zones;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ZonesTest extends TestCase {
    private Zones $endpoint;
    private AdapterInterface $adapter;
    private AuthInterface $auth;
    
    protected function setUp(): void {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->auth = $this->createMock(AuthInterface::class);
        $this->auth->method('getHeaders')->willReturn(['Authorization' => 'Bearer test']);
        $this->endpoint = new Zones($this->adapter, $this->auth);
    }
    
    public function testList(): void {
        // Arrange
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn(json_encode([
            'result' => [['id' => '1', 'name' => 'test.com']],
            'result_info' => ['page' => 1, 'per_page' => 20, 'total_pages' => 1]
        ]));
        $response->method('getBody')->willReturn($body);
        $response->method('getStatusCode')->willReturn(200);
        
        $this->adapter->method('request')->willReturn($response);
        
        // Act
        $result = $this->endpoint->list();
        
        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('test.com', $result[0]->name);
    }
    
    // ... more tests for get, create, update, delete
}
```

## Testing Requirements

### Target: 100% Code Coverage

### Test Structure
- **Unit Tests**: Test each class in isolation
- **Mocking**: Use PHPUnit mocks for dependencies
- **Naming**: `ClassNameTest.php` for `ClassName.php`
- **Methods**: `testMethodName()` or `@test` annotation

### Running Tests
```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
./vendor/bin/phpunit tests/Endpoints/AccountsTest.php
```

### Coverage Requirements
- All public methods must have tests
- All exception paths must be tested
- All entity factory methods must be tested

## Quality Assurance

### Code Style
```bash
# Check code style
composer lint

# Fix code style automatically
composer lint-fix
```

### Static Analysis
```bash
# Run PHPStan (level 7)
composer analyse
```

### Full Check Suite
```bash
# Run lint, analyse, and test
composer check
```

### Pre-Commit Checklist
Before committing, ensure:
- [ ] All tests pass (`composer test`)
- [ ] Code style is valid (`composer lint`)
- [ ] Static analysis passes (`composer analyse`)
- [ ] Coverage is maintained at 100%
- [ ] PHPDoc is complete for new code
- [ ] No secrets or credentials in code

## Common Tasks

### Adding a New Entity
1. Create file in `src/Entities/`
2. Extend `AbstractEntity`
3. Define public readonly properties
4. Implement `makeFromCloudflareData()`
5. Create corresponding test in `tests/Entities/`

### Adding a New Endpoint
1. Create file in `src/Endpoints/`
2. Extend `AbstractEndpoint`
3. Implement CRUD methods (list, get, create, update, delete)
4. Use `ListResponse` for lists, `EntityResponse` for single items
5. Add method to `Client.php`
6. Create comprehensive tests in `tests/Endpoints/`

### Adding a New Exception
1. Create file in `src/Exceptions/`
2. Extend appropriate base exception
3. Document when it's thrown
4. Add tests if custom logic

### Updating Dependencies
```bash
# Update all dependencies
composer update

# Update specific package
composer update package/name

# Check for outdated packages
composer outdated
```

## API Reference

### Response Wrappers

**ListResponse**
- Constructor: `new ListResponse(ResponseInterface $response, string $entityClass)`
- Implements: `ArrayAccess`, `Countable`, `Iterator`
- Usage: `foreach ($response as $entity)`

**EntityResponse**
- Constructor: `new EntityResponse(ResponseInterface $response, string $entityClass)`
- Properties: `entity` (the hydrated entity)

### Authentication Methods

**ApiToken** (Recommended)
```php
$auth = new ApiToken('your-api-token');
```

**ApiKeyAndEmail** (Legacy)
```php
$auth = new ApiKeyAndEmail('api-key', 'email@example.com');
```

**Unauthorized** (For testing)
```php
$auth = new Unauthorized();
```

### HTTP Methods

Use constants from `Fig\Http\Message\StatusCodeInterface`:
- `StatusCodeInterface::STATUS_OK` (200)
- `StatusCodeInterface::STATUS_CREATED` (201)
- `StatusCodeInterface::STATUS_NO_CONTENT` (204)
- `StatusCodeInterface::STATUS_BAD_REQUEST` (400)
- `StatusCodeInterface::STATUS_UNAUTHORIZED` (401)
- `StatusCodeInterface::STATUS_NOT_FOUND` (404)

## Debugging Tips

### Enable Debug Output
```php
$adapter = new GuzzleAdapter(['debug' => true]);
```

### Check Raw Response
```php
$response = $adapter->request('GET', 'https://api.cloudflare.com/client/v4/zones');
echo $response->getBody()->getContents();
```

### Xdebug Configuration
If using devcontainer, Xdebug is pre-configured. Set breakpoints and use VS Code's PHP Debug extension.

## Important Notes

1. **Never commit secrets**: API tokens, keys, or credentials
2. **Always use strict types**: `declare(strict_types=1)` at file start
3. **Always add tests**: 100% coverage is the goal
4. **Follow existing patterns**: Look at `Accounts` endpoint as reference
5. **Document everything**: PHPDoc for all public APIs
6. **Check CI status**: Ensure GitHub Actions pass before merging
7. **Update CHANGELOG**: Document all changes in keepachangelog.com format

## Resources

- [Cloudflare API Documentation](https://developers.cloudflare.com/api/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHP-CS-Fixer Documentation](https://cs.symfony.com/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [phpDocumentor Documentation](https://docs.phpdoc.org/)

---

Last Updated: 2026-02-19
