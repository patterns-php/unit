<?php

declare(strict_types=1);

namespace Patterns\Tests\Fakes;

use Patterns\IUnitSchema;

/**
 * Minimal schema: array-backed, no base class, no validation.
 *
 * Exists to prove two things about the contract - that it can be satisfied
 * without extending anything, and that it round-trips. That is the whole
 * promise of the pattern: implement it however your codebase needs.
 */
final class TestUnitSchema implements IUnitSchema
{
    /**
     * @param array<string, mixed> $props
     */
    private function __construct(private readonly array $props)
    {
    }

    /**
     * @param array<string, mixed> $props
     */
    public static function create(array $props): self
    {
        return new self([
            'id'          => (string) ($props['id'] ?? ''),
            'version'     => (string) ($props['version'] ?? ''),
            'label'       => (string) ($props['label'] ?? ''),
            'description' => (string) ($props['description'] ?? ''),
            'versions'    => array_values((array) ($props['versions'] ?? [])),
        ]);
    }

    public function id(): string
    {
        return $this->props['id'];
    }

    public function version(): string
    {
        return $this->props['version'];
    }

    public function label(): string
    {
        return $this->props['label'];
    }

    public function description(): string
    {
        return $this->props['description'];
    }

    /**
     * @return list<array<string, string>>
     */
    public function versions(): array
    {
        return $this->props['versions'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->props;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->props;
    }
}
