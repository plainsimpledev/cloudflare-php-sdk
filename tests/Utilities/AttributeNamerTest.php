<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Utilities\AttributeNamer;

final class AttributeNamerTest extends TestCase
{
    public function testResolvesGetterSetterAndBooleanGetterNames(): void
    {
        $this->assertSame('getUseAccountCustomNsByDefault', AttributeNamer::getGetterName(
            'use_account_custom_ns_by_default',
        ));
        $this->assertSame('isUseAccountCustomNsByDefault', AttributeNamer::getBooleanGetterName(
            'use_account_custom_ns_by_default',
        ));
        $this->assertSame('setUseAccountCustomNsByDefault', AttributeNamer::getSetterName(
            'use_account_custom_ns_by_default',
        ));
    }
}
