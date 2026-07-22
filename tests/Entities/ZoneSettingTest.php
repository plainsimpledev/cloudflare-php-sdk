<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\ZoneSetting;

final class ZoneSettingTest extends TestCase
{
    /** @return iterable<string, array{mixed}> */
    public static function valueProvider(): iterable
    {
        yield 'scalar' => ['strict'];
        yield 'list' => [['example.com', 'www.example.com']];
        yield 'object' => [['mode' => 'strict', 'min_tls' => '1.2']];
        yield 'null' => [null];
    }

    #[DataProvider('valueProvider')]
    public function testPreservesMixedValues(mixed $value): void
    {
        $setting = ZoneSetting::makeFromCloudflareData([
            'id' => 'mixed-setting',
            'value' => $value,
        ]);

        $this->assertSame($value, $setting->getValue());
        $this->assertSame([], $setting->getDirtyFields());
    }

    public function testHydratesKnownAndUnknownFieldsCleanly(): void
    {
        $setting = ZoneSetting::makeFromCloudflareData([
            'id' => 'development_mode',
            'value' => 'off',
            'editable' => true,
            'modified_on' => '2026-07-22T12:34:56Z',
            'time_remaining' => false,
            'future_metadata' => ['source' => 'edge'],
        ]);

        $this->assertSame('development_mode', $setting->getId());
        $this->assertSame('off', $setting->getValue());
        $this->assertTrue($setting->isEditable());
        $this->assertSame('2026-07-22T12:34:56+00:00', $setting->getModifiedOn()?->format(DATE_ATOM));
        $this->assertFalse($setting->getTimeRemaining());
        $this->assertSame(['future_metadata' => ['source' => 'edge']], $setting->getAdditionalAttributes());
        $this->assertSame([], $setting->getDirtyFields());
    }

    public function testHydratesEnabledOnlySslRecommender(): void
    {
        $setting = ZoneSetting::makeFromCloudflareData([
            'id' => 'ssl_recommender',
            'enabled' => true,
        ]);

        $this->assertTrue($setting->isEnabled());
        $this->assertFalse($setting->hasAttribute('value'));
        $this->assertSame([], $setting->getDirtyFields());
    }

    public function testOnlyValueAndEnabledAreWritable(): void
    {
        $setting = ZoneSetting::makeFromCloudflareData([
            'id' => 'setting-id',
            'value' => 'old',
            'enabled' => false,
            'editable' => true,
            'modified_on' => '2026-07-22T12:34:56Z',
            'time_remaining' => false,
            'future_metadata' => 'preserved',
        ]);
        $setting->setValue(['mode' => 'new']);
        $setting->setEnabled(true);
        $setting->setEditable(false);
        $setting->setModifiedOn(null);
        $setting->setTimeRemaining(30);

        $this->assertSame([
            'value' => ['mode' => 'new'],
            'enabled' => true,
        ], $setting->toPatchPayload());
        $this->assertSame([
            'value' => ['mode' => 'new'],
            'enabled' => true,
        ], $setting->toReplacePayload());
    }

    public function testUpdateFactoriesLeaveOnlyWritableFieldDirty(): void
    {
        $valueSetting = ZoneSetting::forUpdate('ssl', 'full');
        $enabledSetting = ZoneSetting::forEnabledUpdate('ssl_recommender', false);

        $this->assertSame(['value'], $valueSetting->getDirtyFields());
        $this->assertSame(['value' => 'full'], $valueSetting->toPatchPayload());
        $this->assertSame(['enabled'], $enabledSetting->getDirtyFields());
        $this->assertSame(['enabled' => false], $enabledSetting->toPatchPayload());
    }
}
