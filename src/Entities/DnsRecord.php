<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use PlainSimple\Cloudflare\Enums\DnsRecordType;

final class DnsRecord extends AbstractEntity
{
    private const array STRUCTURED_TYPES = [
        'CAA',
        'CERT',
        'DNSKEY',
        'DS',
        'HTTPS',
        'LOC',
        'NAPTR',
        'SMIMEA',
        'SRV',
        'SSHFP',
        'SVCB',
        'TLSA',
        'URI',
    ];

    protected const CREATE_FIELDS = [
        'name',
        'ttl',
        'type',
        'comment',
        'content',
        'data',
        'priority',
        'private_routing',
        'proxied',
        'settings',
        'tags',
    ];
    protected const PATCH_FIELDS = self::CREATE_FIELDS;
    protected const REPLACE_FIELDS = self::CREATE_FIELDS;

    private string $id;
    private string $name;
    private DnsRecordType|string $type;
    private int $ttl;
    private ?string $content = null;

    /** @var array<array-key, mixed> */
    private array $data = [];

    private ?int $priority = null;
    private ?string $comment = null;

    /** @var list<string> */
    private array $tags = [];

    private ?bool $proxied = null;
    private ?bool $proxiable = null;
    private ?bool $private_routing = null;

    /** @var array<array-key, mixed> */
    private array $settings = [];

    /** @var array<array-key, mixed> */
    private array $meta = [];

    private ?DateTimeImmutable $created_on = null;
    private ?DateTimeImmutable $modified_on = null;
    private ?DateTimeImmutable $comment_modified_on = null;
    private ?DateTimeImmutable $tags_modified_on = null;

    /** @param string|array<array-key, mixed> $contentOrData */
    public static function forCreate(
        DnsRecordType|string $type,
        string $name,
        string|array $contentOrData,
        int $ttl = 1,
    ): self {
        $record = new self();
        $record->setType($type);
        $record->setName($name);
        $record->setTtl($ttl);

        if (is_array($contentOrData)) {
            $record->setData($contentOrData);
        } else {
            $record->setContent($contentOrData);
        }

        return $record;
    }

    /** @return array<string, mixed> */
    public function toCreatePayload(): array
    {
        $type = $this->validateWritableRecord();

        return $this->filterTypeSpecificFields(parent::toCreatePayload(), $type);
    }

    /** @return array<string, mixed> */
    public function toPatchPayload(): array
    {
        $type = $this->validateWritableRecord();
        $dirtyFields = array_intersect($this->getDirtyFields(), self::CREATE_FIELDS);
        if ($dirtyFields === []) {
            throw new InvalidArgumentException('DNS record update requires a dirty writable field.');
        }

        $payload = [
            'name' => $this->getName(),
            'ttl' => $this->getTtl(),
            'type' => $type->value,
            ...parent::toPatchPayload(),
        ];

        return $this->filterTypeSpecificFields($payload, $type);
    }

    /** @return array<string, mixed> */
    public function toReplacePayload(): array
    {
        $type = $this->validateWritableRecord();

        return $this->filterTypeSpecificFields(parent::toReplacePayload(), $type);
    }

    public function getId(): string
    {
        $this->getAttribute('id');

        return $this->id;
    }

    public function setId(string $id): void
    {
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

    public function getType(): DnsRecordType|string
    {
        $this->getAttribute('type');

        return $this->type;
    }

    public function setType(DnsRecordType|string $type): void
    {
        if (is_string($type)) {
            $type = DnsRecordType::fromCloudflare($type);
        }

        $this->type = $type;
        $this->setAttribute('type', $type);
    }

    public function getTtl(): int
    {
        $this->getAttribute('ttl');

        return $this->ttl;
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
        $this->setAttribute('ttl', $ttl);
    }

    public function getContent(): ?string
    {
        $this->getAttribute('content');

        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
        $this->setAttribute('content', $content);
    }

    /** @return array<array-key, mixed> */
    public function getData(): array
    {
        $this->getAttribute('data');

        return $this->data;
    }

    /** @param array<array-key, mixed> $data */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->setAttribute('data', $data);
    }

    public function getPriority(): ?int
    {
        $this->getAttribute('priority');

        return $this->priority;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
        $this->setAttribute('priority', $priority);
    }

    public function getComment(): ?string
    {
        $this->getAttribute('comment');

        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
        $this->setAttribute('comment', $comment);
    }

    /** @return list<string> */
    public function getTags(): array
    {
        $this->getAttribute('tags');

        return $this->tags;
    }

    /** @param list<string> $tags */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
        $this->setAttribute('tags', $tags);
    }

    public function isProxied(): ?bool
    {
        $this->getAttribute('proxied');

        return $this->proxied;
    }

    public function setProxied(?bool $proxied): void
    {
        $this->proxied = $proxied;
        $this->setAttribute('proxied', $proxied);
    }

    public function isProxiable(): ?bool
    {
        $this->getAttribute('proxiable');

        return $this->proxiable;
    }

    public function setProxiable(?bool $proxiable): void
    {
        $this->proxiable = $proxiable;
        $this->setAttribute('proxiable', $proxiable);
    }

    public function isPrivateRouting(): ?bool
    {
        $this->getAttribute('private_routing');

        return $this->private_routing;
    }

    public function setPrivateRouting(?bool $private_routing): void
    {
        $this->private_routing = $private_routing;
        $this->setAttribute('private_routing', $private_routing);
    }

    /** @return array<array-key, mixed> */
    public function getSettings(): array
    {
        $this->getAttribute('settings');

        return $this->settings;
    }

    /** @param array<array-key, mixed> $settings */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
        $this->setAttribute('settings', $settings);
    }

    /** @return array<array-key, mixed> */
    public function getMeta(): array
    {
        $this->getAttribute('meta');

        return $this->meta;
    }

    /** @param array<array-key, mixed> $meta */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
        $this->setAttribute('meta', $meta);
    }

    public function getCreatedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('created_on');

        return $this->created_on;
    }

    public function setCreatedOn(DateTimeInterface|string|null $created_on): void
    {
        $created_on = $this->normalizeDate($created_on);
        $this->created_on = $created_on;
        $this->setAttribute('created_on', $created_on);
    }

    public function getModifiedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('modified_on');

        return $this->modified_on;
    }

    public function setModifiedOn(DateTimeInterface|string|null $modified_on): void
    {
        $modified_on = $this->normalizeDate($modified_on);
        $this->modified_on = $modified_on;
        $this->setAttribute('modified_on', $modified_on);
    }

    public function getCommentModifiedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('comment_modified_on');

        return $this->comment_modified_on;
    }

    public function setCommentModifiedOn(DateTimeInterface|string|null $comment_modified_on): void
    {
        $comment_modified_on = $this->normalizeDate($comment_modified_on);
        $this->comment_modified_on = $comment_modified_on;
        $this->setAttribute('comment_modified_on', $comment_modified_on);
    }

    public function getTagsModifiedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('tags_modified_on');

        return $this->tags_modified_on;
    }

    public function setTagsModifiedOn(DateTimeInterface|string|null $tags_modified_on): void
    {
        $tags_modified_on = $this->normalizeDate($tags_modified_on);
        $this->tags_modified_on = $tags_modified_on;
        $this->setAttribute('tags_modified_on', $tags_modified_on);
    }

    private function validateWritableRecord(): DnsRecordType
    {
        foreach (['name', 'ttl', 'type'] as $field) {
            if (!$this->hasAttribute($field)) {
                throw new InvalidArgumentException('DNS record requires ' . $field . '.');
            }
        }

        if (trim($this->getName()) === '') {
            throw new InvalidArgumentException('DNS record name must not be empty.');
        }

        $rawType = $this->getType();
        $type = $rawType instanceof DnsRecordType ? $rawType : DnsRecordType::tryFrom($rawType);
        if ($type === null) {
            throw new InvalidArgumentException('DNS record type is not supported for writes.');
        }

        $ttl = $this->getTtl();
        if ($ttl !== 1 && ($ttl < 30 || $ttl > 86400)) {
            throw new InvalidArgumentException('DNS record TTL must be 1 or between 30 and 86400.');
        }

        $structured = in_array($type->value, self::STRUCTURED_TYPES, true);
        if ($structured) {
            if (!$this->hasAttribute('data') || $this->getData() === []) {
                throw new InvalidArgumentException('Structured DNS record requires non-empty data.');
            }
        } else {
            $content = $this->hasAttribute('content') ? $this->getContent() : null;
            if ($content === null || trim($content) === '') {
                throw new InvalidArgumentException('Simple DNS record requires non-empty content.');
            }
        }

        $dirty = array_flip($this->getDirtyFields());
        if ($structured && isset($dirty['content'])) {
            throw new InvalidArgumentException('Structured DNS records must write data, not content.');
        }
        if (!$structured && isset($dirty['data'])) {
            throw new InvalidArgumentException('Simple DNS records must write content, not data.');
        }
        if (!in_array($type, [DnsRecordType::A, DnsRecordType::AAAA], true) && isset($dirty['private_routing'])) {
            throw new InvalidArgumentException('Private routing is supported only for A and AAAA records.');
        }
        if (!in_array($type, [DnsRecordType::MX, DnsRecordType::URI], true) && isset($dirty['priority'])) {
            throw new InvalidArgumentException('Top-level priority is supported only for MX and URI records.');
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterTypeSpecificFields(array $payload, DnsRecordType $type): array
    {
        if (in_array($type->value, self::STRUCTURED_TYPES, true)) {
            unset($payload['content']);
        } else {
            unset($payload['data']);
        }

        if (!in_array($type, [DnsRecordType::A, DnsRecordType::AAAA], true)) {
            unset($payload['private_routing']);
        }
        if (!in_array($type, [DnsRecordType::MX, DnsRecordType::URI], true)) {
            unset($payload['priority']);
        }

        return $payload;
    }

    private function normalizeDate(DateTimeInterface|string|null $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return is_string($value) ? new DateTimeImmutable($value) : null;
    }
}
