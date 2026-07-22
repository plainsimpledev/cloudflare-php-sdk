<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Enums\DefaultNameservers;
use PlainSimple\Cloudflare\Exceptions\AttributeDoesNotExistsException;

final class EntityInfrastructureTest extends TestCase
{
    public function testAbsentAndExplicitNullHaveDifferentState(): void
    {
        $entity = new EntityInfrastructureFixture();

        $this->assertFalse($entity->hasAttribute('nullable'));
        $this->assertFalse($entity->__isset('nullable'));

        $entity->setNullable(null);

        $this->assertTrue($entity->hasAttribute('nullable'));
        $this->assertNull($entity->getNullable());
        $this->assertFalse($entity->__isset('nullable'));
        $this->assertSame(['nullable'], $entity->getDirtyFields());
        $this->assertSame(['nullable' => null], $entity->toCreatePayload());
        $this->assertSame(['nullable' => null], $entity->toPatchPayload());
    }

    public function testMissingKnownGetterThrowsEntityException(): void
    {
        $account = new Account();

        $this->expectException(AttributeDoesNotExistsException::class);
        $this->expectExceptionMessage(Account::class);

        $account->getId();
    }

    public function testHydrationUsesSettersPreservesUnknownFieldsAndMarksClean(): void
    {
        $entity = EntityInfrastructureFixture::makeFromCloudflareData([
            'name' => 'hydrated',
            'enabled' => true,
            'future_field' => null,
        ]);

        $this->assertSame('HYDRATED', $entity->getName());
        $this->assertTrue($entity->__get('enabled'));
        $this->assertSame([], $entity->getDirtyFields());
        $this->assertTrue($entity->hasAttribute('future_field'));
        $this->assertSame(['future_field' => null], $entity->getAdditionalAttributes());
        $this->assertNull($entity->getAdditionalAttribute('future_field', 'fallback'));
        $this->assertSame('fallback', $entity->getAdditionalAttribute('missing', 'fallback'));
        $this->assertNull($entity->__get('future_field'));
        $this->assertFalse($entity->__isset('future_field'));
    }

    public function testHydrationPreservesInfrastructureMethodCollisionKeys(): void
    {
        $entity = EntityInfrastructureFixture::makeFromCloudflareData([
            'attribute' => 'setAttribute collision',
            'additional_attribute' => 'getAdditionalAttribute collision',
        ]);

        $this->assertSame([
            'attribute' => 'setAttribute collision',
            'additional_attribute' => 'getAdditionalAttribute collision',
        ], $entity->getAdditionalAttributes());
        $this->assertSame('setAttribute collision', $entity->__get('attribute'));
        $this->assertSame('getAdditionalAttribute collision', $entity->__get('additional_attribute'));
        $this->assertSame([], $entity->getDirtyFields());
    }

    public function testMagicSetterDoesNotDispatchProtectedInfrastructureMethod(): void
    {
        $entity = new EntityInfrastructureFixture();

        $this->expectException(AttributeDoesNotExistsException::class);

        $entity->__set('attribute', 'value');
    }

    public function testIssetResolvesGetterAliasesAndNulls(): void
    {
        $present = Account::makeFromCloudflareData([
            'created_on' => '2026-07-22T12:34:56Z',
        ]);
        $null = Account::makeFromCloudflareData(['created_on' => null]);

        $this->assertTrue($present->__isset('createdOn'));
        $this->assertFalse($null->__isset('createdOn'));
        $this->assertFalse((new Account())->__isset('createdOn'));
    }

    public function testSettersTrackDirtyFieldsAndPayloadsFilterByOperation(): void
    {
        $entity = new EntityInfrastructureFixture();
        $entity->setName('first');
        $entity->setNullable(null);
        $entity->setServerOnly('response metadata');

        $this->assertSame(['name', 'nullable', 'server_only'], $entity->getDirtyFields());
        $this->assertSame([
            'name' => 'FIRST',
            'nullable' => null,
        ], $entity->toCreatePayload());
        $this->assertSame([
            'name' => 'FIRST',
            'nullable' => null,
        ], $entity->toPatchPayload());
        $this->assertSame([
            'name' => 'FIRST',
            'nullable' => null,
        ], $entity->toReplacePayload());

        $entity->markClean();
        $entity->setName('second');

        $this->assertSame(['name'], $entity->getDirtyFields());
        $this->assertSame(['name' => 'SECOND'], $entity->toPatchPayload());
        $this->assertSame([
            'name' => 'SECOND',
            'nullable' => null,
        ], $entity->toReplacePayload());
    }

    public function testUnknownHydrationFieldsNeverWriteBack(): void
    {
        $entity = EntityInfrastructureFixture::makeFromCloudflareData([
            'name' => 'known',
            'server_only' => 'server',
            'future_field' => 'future',
        ]);

        $this->assertSame([
            'name' => 'KNOWN',
            'server_only' => 'server',
            'future_field' => 'future',
        ], $entity->toCloudflareData());
        $this->assertSame(['name' => 'KNOWN'], $entity->toCreatePayload());
        $this->assertSame([], $entity->toPatchPayload());
        $this->assertSame(['name' => 'KNOWN'], $entity->toReplacePayload());
    }

    public function testRecursiveNormalizationHandlesNestedEntitiesEnumsDatesAndArrays(): void
    {
        $date = new DateTimeImmutable('2026-07-22T12:34:56+00:00');
        $nested = EntityInfrastructureNestedFixture::makeFromCloudflareData([
            'label' => 'nested',
            'future_nested' => 'read only',
        ]);
        $entity = new EntityInfrastructureFixture();
        $entity->setOccurredOn($date);
        $entity->setNameservers(DefaultNameservers::CustomAccount);
        $entity->setNested($nested);
        $entity->setItems([
            'date' => $date,
            'enum' => DefaultNameservers::CloudflareStandard,
            'entity' => $nested,
        ]);

        $this->assertSame([
            'occurred_on' => '2026-07-22T12:34:56+00:00',
            'nameservers' => 'custom.account',
            'nested' => [
                'label' => 'nested',
                'future_nested' => 'read only',
            ],
            'items' => [
                'date' => '2026-07-22T12:34:56+00:00',
                'enum' => 'cloudflare.standard',
                'entity' => [
                    'label' => 'nested',
                    'future_nested' => 'read only',
                ],
            ],
        ], $entity->toCloudflareData());

        $this->assertSame([
            'nested' => ['label' => 'nested'],
            'occurred_on' => '2026-07-22T12:34:56+00:00',
            'nameservers' => 'custom.account',
            'items' => [
                'date' => '2026-07-22T12:34:56+00:00',
                'enum' => 'cloudflare.standard',
                'entity' => ['label' => 'nested'],
            ],
        ], $entity->toCreatePayload());
    }

    public function testNestedDirtyEntityMakesHydratedParentPatchable(): void
    {
        $entity = EntityInfrastructureFixture::makeFromCloudflareData([
            'nested' => ['label' => 'original'],
        ]);

        $entity->getNested()->setLabel('changed');

        $this->assertSame([], $entity->getDirtyFields());
        $this->assertSame([
            'nested' => ['label' => 'changed'],
        ], $entity->toPatchPayload());
    }

    public function testDirtyEntityInsideNestedArraysMakesHydratedParentPatchable(): void
    {
        $nested = EntityInfrastructureNestedFixture::makeFromCloudflareData([
            'label' => 'original',
        ]);
        $entity = EntityInfrastructureFixture::makeFromCloudflareData([
            'items' => ['deep' => [[$nested]]],
        ]);

        $nested->setLabel('changed');

        $this->assertSame([], $entity->getDirtyFields());
        $this->assertSame([
            'items' => ['deep' => [[['label' => 'changed']]]],
        ], $entity->toPatchPayload());
    }

    public function testUnknownExternalWriteReportsConcreteClass(): void
    {
        $account = new Account();

        $this->expectException(AttributeDoesNotExistsException::class);
        $this->expectExceptionMessage("Attribute 'future_field' does not exist in class '" . Account::class . "'");

        $account->__set('future_field', 'value');
    }
}

final class EntityInfrastructureFixture extends AbstractEntity
{
    protected const CREATE_FIELDS = [
        'name',
        'nullable',
        'enabled',
        'nested',
        'occurred_on',
        'nameservers',
        'items',
    ];
    protected const PATCH_FIELDS = self::CREATE_FIELDS;
    protected const REPLACE_FIELDS = self::CREATE_FIELDS;

    public function getName(): string
    {
        $name = $this->getAttribute('name');
        if (!is_string($name)) {
            throw new LogicException('Name must be a string.');
        }

        return $name;
    }

    public function setName(string $name): void
    {
        $this->setAttribute('name', strtoupper($name));
    }

    public function getNullable(): ?string
    {
        $nullable = $this->getAttribute('nullable');
        if (!is_string($nullable) && $nullable !== null) {
            throw new LogicException('Nullable must be a string or null.');
        }

        return $nullable;
    }

    public function setNullable(?string $nullable): void
    {
        $this->setAttribute('nullable', $nullable);
    }

    public function isEnabled(): bool
    {
        $enabled = $this->getAttribute('enabled');
        if (!is_bool($enabled)) {
            throw new LogicException('Enabled must be a boolean.');
        }

        return $enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->setAttribute('enabled', $enabled);
    }

    public function getNested(): EntityInfrastructureNestedFixture
    {
        $nested = $this->getAttribute('nested');
        if (!$nested instanceof EntityInfrastructureNestedFixture) {
            throw new LogicException('Nested must be an entity.');
        }

        return $nested;
    }

    /** @param EntityInfrastructureNestedFixture|array<string, mixed> $nested */
    public function setNested(EntityInfrastructureNestedFixture|array $nested): void
    {
        if (is_array($nested)) {
            $nested = EntityInfrastructureNestedFixture::makeFromCloudflareData($nested);
        }

        $this->setAttribute('nested', $nested);
    }

    public function setOccurredOn(DateTimeInterface $occurred_on): void
    {
        $this->setAttribute('occurred_on', $occurred_on);
    }

    public function setNameservers(DefaultNameservers $nameservers): void
    {
        $this->setAttribute('nameservers', $nameservers);
    }

    /** @param array<mixed> $items */
    public function setItems(array $items): void
    {
        $this->setAttribute('items', $items);
    }

    public function setServerOnly(string $server_only): void
    {
        $this->setAttribute('server_only', $server_only);
    }
}

final class EntityInfrastructureNestedFixture extends AbstractEntity
{
    protected const CREATE_FIELDS = ['label'];
    protected const PATCH_FIELDS = ['label'];
    protected const REPLACE_FIELDS = ['label'];

    public function setLabel(string $label): void
    {
        $this->setAttribute('label', $label);
    }
}
