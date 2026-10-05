<?php

declare(strict_types=1);

namespace Patterns;

use JsonSerializable;

/**
 * IUnitSchema - the DNA of a unit: identity that is *named and versioned*.
 *
 * This is the single thing that separates a Unit from a ValueObject:
 *
 *   ValueObject - an anonymous value; the value IS the identity, and two equal
 *                 ones are interchangeable.
 *   Unit        - a named identity; the interesting question is which one it
 *                 is, and which version of it.
 *
 * `id()` is a stable name, never a class name: it survives renames and
 * refactors, works across processes and languages, and can tag stored data.
 *
 * ROUND-TRIP IS THE POINT. DNA is meant to be persisted alongside the data it
 * describes, so that any row, event or log line can answer: which unit, at
 * which version, produced this? That only works if the schema can be written
 * out and read back, which is why toArray() and create() belong to the
 * contract, and why the array shape is fixed here: a generic consumer must be
 * able to read any unit's provenance without knowing its class.
 */
interface IUnitSchema extends JsonSerializable
{
    /**
     * Stable name, e.g. "supplier-ranking". Never a class name.
     */
    public function id(): string;

    /**
     * Semantic version of that name, e.g. "1.1.0".
     */
    public function version(): string;

    /**
     * Human label, e.g. "SupplierRanking v1.1.0".
     */
    public function label(): string;

    /**
     * One sentence on what this unit is for.
     */
    public function description(): string;

    /**
     * Version ledger, oldest first. Recorded versions must have been released -
     * the ledger is what lets a stored record say which version produced it, so
     * it must not claim versions that never shipped.
     *
     * @return list<array{version: string, date?: string, notes?: string}>
     */
    public function versions(): array;

    /**
     * The persisted shape - fixed by this contract so a generic consumer can
     * read any unit's provenance.
     *
     * @return array{id: string, version: string, label: string, description: string, versions: list<array<string, string>>}
     */
    public function toArray(): array;

    /**
     * The entry point. Read back what toArray() wrote, or declare the identity
     * for the first time.
     *
     * @param array<string, mixed> $props
     */
    public static function create(array $props): self;
}
