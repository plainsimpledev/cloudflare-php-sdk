<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\ZoneSettings;
use PlainSimple\Cloudflare\Entities\ZoneSetting;
use Psr\Http\Message\ResponseInterface;

final class ZoneSettingsTest extends TestCase
{
    private AdapterInterface&MockObject $adapter;
    private ZoneSettings $zoneSettings;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->zoneSettings = new ZoneSettings($this->adapter);
    }

    public function testListsUnpaginatedMixedSettingsUsingRealResponse(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone%2Fid/settings')
            ->willReturn($this->response([
                ['id' => 'ssl', 'value' => 'full', 'editable' => true],
                ['id' => 'list_setting', 'value' => ['one', 'two']],
                ['id' => 'object_setting', 'value' => ['mode' => 'strict']],
                ['id' => 'nullable_setting', 'value' => null],
                ['id' => 'ssl_recommender', 'enabled' => true],
                ['id' => 'development_mode', 'value' => 'off', 'time_remaining' => false],
            ]));

        $response = $this->zoneSettings->list('zone/id');
        $items = $response->getItems();

        $this->assertCount(6, $items);
        $this->assertSame('full', $items[0]->getValue());
        $this->assertSame(['one', 'two'], $items[1]->getValue());
        $this->assertSame(['mode' => 'strict'], $items[2]->getValue());
        $this->assertNull($items[3]->getValue());
        $this->assertTrue($items[4]->isEnabled());
        $this->assertFalse($items[5]->getTimeRemaining());
        $this->assertNull($response->getPage());
        $this->assertNull($response->getPerPage());
        foreach ($items as $item) {
            $this->assertSame([], $item->getDirtyFields());
        }
    }

    public function testGetsSetting(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone%20id/settings/cache%2Fkey')
            ->willReturn($this->response(['id' => 'cache/key', 'value' => 'strict']));

        $setting = $this->zoneSettings->get('zone id', 'cache/key')->getEntity();

        $this->assertInstanceOf(ZoneSetting::class, $setting);
        $this->assertSame('strict', $setting->getValue());
        $this->assertSame([], $setting->getDirtyFields());
    }

    public function testUpdatesExactlyOneValueField(): void
    {
        $setting = ZoneSetting::forUpdate('cache/key', ['mode' => 'strict']);
        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/zones/zone%2Fid/settings/cache%2Fkey', ['value' => ['mode' => 'strict']])
            ->willReturn($this->response(['id' => 'cache/key', 'value' => ['mode' => 'strict']]));

        $updated = $this->zoneSettings->update('zone/id', $setting)->getEntity();

        $this->assertInstanceOf(ZoneSetting::class, $updated);
        $this->assertSame(['mode' => 'strict'], $updated->getValue());
        $this->assertSame([], $updated->getDirtyFields());
    }

    public function testUpdatesEnabledOnlyVariant(): void
    {
        $setting = ZoneSetting::forEnabledUpdate('ssl_recommender', false);
        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/zones/zone%20id/settings/ssl_recommender', ['enabled' => false])
            ->willReturn($this->response(['id' => 'ssl_recommender', 'enabled' => false]));

        $updated = $this->zoneSettings->update('zone id', $setting)->getEntity();

        $this->assertInstanceOf(ZoneSetting::class, $updated);
        $this->assertFalse($updated->isEnabled());
        $this->assertSame([], $updated->getDirtyFields());
    }

    public function testBulkUpdateSendsExactTopLevelList(): void
    {
        $ssl = ZoneSetting::forUpdate('cache/key', 'full');
        $recommender = ZoneSetting::forEnabledUpdate('ssl_recommender', true);

        $payload = [
            ['id' => 'cache/key', 'value' => 'full'],
            ['id' => 'ssl_recommender', 'enabled' => true],
        ];
        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/zones/zone%2Fid/settings', $this->identicalTo($payload))
            ->willReturn($this->response($payload));

        $response = $this->zoneSettings->updateMany('zone/id', [$ssl, $recommender]);

        $this->assertCount(2, $response->getItems());
        foreach ($response->getItems() as $item) {
            $this->assertSame([], $item->getDirtyFields());
        }
    }

    public function testUpdateRequiresId(): void
    {
        $setting = new ZoneSetting();
        $setting->setValue('full');
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone setting ID is required.');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testUpdateRejectsBothWritableFields(): void
    {
        $setting = ZoneSetting::forUpdate('ssl', 'full');
        $setting->setEnabled(true);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one of value or enabled');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testUpdateRejectsCleanSetting(): void
    {
        $setting = ZoneSetting::makeFromCloudflareData(['id' => 'ssl', 'value' => 'full']);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one of value or enabled');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testBulkUpdateRequiresSettings(): void
    {
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one setting');

        $this->zoneSettings->updateMany('zone-id', []);
    }

    public function testBulkUpdateRequiresEachId(): void
    {
        $setting = new ZoneSetting();
        $setting->setEnabled(true);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone setting ID is required.');

        $this->zoneSettings->updateMany('zone-id', [$setting]);
    }

    public function testBulkUpdateRequiresEachPatch(): void
    {
        $setting = ZoneSetting::makeFromCloudflareData(['id' => 'ssl', 'value' => 'full']);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one of value or enabled');

        $this->zoneSettings->updateMany('zone-id', [$setting]);
    }

    public function testRejectsEmptyZoneId(): void
    {
        $this->adapter->expects($this->never())->method('get');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone ID must not be empty.');

        $this->zoneSettings->list(' ');
    }

    public function testRejectsEmptySettingPathId(): void
    {
        $this->adapter->expects($this->never())->method('get');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone setting ID must not be empty.');

        $this->zoneSettings->get('zone-id', '');
    }

    public function testUpdateRejectsEmptyEntityId(): void
    {
        $setting = ZoneSetting::forUpdate(' ', 'full');
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone setting ID must not be empty.');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testUpdateRejectsEnabledForOrdinarySetting(): void
    {
        $setting = ZoneSetting::forEnabledUpdate('ssl', true);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('all other zone setting updates must use value');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testUpdateRejectsValueForSslRecommender(): void
    {
        $setting = ZoneSetting::forUpdate('ssl_recommender', true);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ssl_recommender updates must use enabled');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testUpdateRejectsNullValue(): void
    {
        $setting = ZoneSetting::forUpdate('ssl', null);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be null');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testUpdateRejectsNullEnabled(): void
    {
        $setting = new ZoneSetting();
        $setting->setId('ssl_recommender');
        $setting->markClean();
        $setting->setEnabled(null);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be null');

        $this->zoneSettings->update('zone-id', $setting);
    }

    public function testBulkUpdateRejectsBothFieldsPerItem(): void
    {
        $setting = ZoneSetting::forUpdate('ssl', 'full');
        $setting->setEnabled(true);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one of value or enabled');

        $this->zoneSettings->updateMany('zone-id', [$setting]);
    }

    public function testBulkUpdateRejectsWrongVariantPerItem(): void
    {
        $setting = ZoneSetting::forEnabledUpdate('ssl', true);
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('all other zone setting updates must use value');

        $this->zoneSettings->updateMany('zone-id', [$setting]);
    }

    /** @param array<array-key, mixed> $result */
    private function response(array $result): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => $result,
        ], JSON_THROW_ON_ERROR));
    }
}
