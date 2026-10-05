<?php

declare(strict_types=1);

namespace Patterns\Tests\Fakes;

use Patterns\IUnit;
use Patterns\IUnitSchema;

/**
 * Minimal unit: a schema plus its own presentation, and no base class.
 */
final class TestUnit implements IUnit
{
    private function __construct(
        private readonly TestUnitSchema $dna,
        private readonly string $voice,
    ) {
    }

    /**
     * @param array<string, mixed> $props
     */
    public static function create(array $props): self
    {
        $dna = TestUnitSchema::create((array) ($props['dna'] ?? []));

        return new self($dna, (string) ($props['whoami'] ?? $dna->label()));
    }

    public function dna(): IUnitSchema
    {
        return $this->dna;
    }

    public function whoami(): string
    {
        return $this->voice;
    }
}
