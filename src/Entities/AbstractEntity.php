<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Traits\EntityTrait;

/** @phpstan-consistent-constructor */
abstract class AbstractEntity
{
    use EntityTrait;

    protected const CREATE_FIELDS = [];
    protected const PATCH_FIELDS = [];
    protected const REPLACE_FIELDS = [];
}
