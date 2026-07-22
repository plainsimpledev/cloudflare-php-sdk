<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

class AccountManagedBy extends AbstractEntity
{
    protected const CREATE_FIELDS = [];
    protected const PATCH_FIELDS = [];
    protected const REPLACE_FIELDS = [];

    private ?string $parent_org_id = null;

    private ?string $parent_org_name = null;

    public function getParentOrgId(): ?string
    {
        $this->getAttribute('parent_org_id');

        return $this->parent_org_id;
    }

    public function setParentOrgId(?string $parent_org_id): void
    {
        $this->parent_org_id = $parent_org_id;
        $this->setAttribute('parent_org_id', $parent_org_id);
    }

    public function getParentOrgName(): ?string
    {
        $this->getAttribute('parent_org_name');

        return $this->parent_org_name;
    }

    public function setParentOrgName(?string $parent_org_name): void
    {
        $this->parent_org_name = $parent_org_name;
        $this->setAttribute('parent_org_name', $parent_org_name);
    }
}
