<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Utilities\PathSegment;

class AccountReference extends AbstractEntity
{
    protected const CREATE_FIELDS = ['id'];
    protected const PATCH_FIELDS = [];
    protected const REPLACE_FIELDS = ['id'];

    private string $id;

    private string $name;

    public static function forId(string $id): self
    {
        $account = new self();
        $account->setId($id);

        return $account;
    }

    public function getId(): string
    {
        $this->getAttribute('id');

        return $this->id;
    }

    public function setId(string $id): void
    {
        PathSegment::encode($id, 'Account ID');
        $this->id = $id;
        $this->setAttribute('id', $id);
    }

    public function getName(): string
    {
        $this->getAttribute('name');

        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->setAttribute('name', $name);
    }
}
