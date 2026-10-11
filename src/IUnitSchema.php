<?php

declare(strict_types=1);

namespace Patterns;

use JsonSerializable;

/**
 * IUnitSchema - the DNA contract (Doctrine #7: every unit has DNA).
 *
 * Identity that is *named and versioned*. That is the single thing separating
 * a Unit from a ValueObject:
 *
 *   ValueObject - an anonymous value; the value IS the identity.
 *   Unit        - a named identity; the name is the identity, and it is versioned.
 *
 * There is deliberately NO version ledger here. A changelog of released versions
 * is git's job, and copying it into every persisted row is payload nobody reads:
 * a record needs to say which version produced it, not every version that ever
 * existed. A unit that must juggle several versions at once is a composition
 * problem - pins ACROSS units, not the history of one - and that belongs in a
 * specific unit's own extended contract, not in the general shape.
 *
 * `id()` is a stable name, never a class name: it survives refactors, works
 * across processes and languages, and can tag the wire.
 *
 * ROUND-TRIP IS THE POINT. A schema is meant to be persisted *alongside the
 * data it describes*, so that any row, event or document can answer: which
 * unit, at which version, produced this? That only works if the schema can be
 * written out and read back - which is why toArray()/fromArray() belong to the
 * contract rather than to an implementation, and why the array shape is fixed
 * here: a generic consumer must be able to read any unit's provenance without
 * knowing its class.
 */
interface IUnitSchema extends JsonSerializable
{
        /**
     * The entry point - Doctrine #4: create, never construct.
     *
     * Typing a static factory on an interface is the one thing PHP can express
     * and TypeScript cannot. It means every schema is built the same way, so
     * generic code can rebuild ANY schema from the array toArray() wrote
     * without knowing the class - which is what makes persisted provenance
     * readable years later.
     *
     * Declared as `self` (an IUnitSchema) so an implementation may return
     * `self` or `static`.
     *
     * @param array<string, mixed> $props
     */
    public static function create(array $props): self;
    
    /**
     * Stable name, e.g. "ScarcityAware". Never a class name.
     */
    public function id(): string;

    /**
     * Semantic version of that name, e.g. "1.1.0".
     */
    public function version(): string;

    /**
     * Human label, e.g. "ScarcityAware v1.1.0".
     */
    public function label(): string;

    /**
     * One sentence on what this unit is for.
     */
    public function description(): string;

    /**
     * The persisted shape - fixed by this contract so a generic consumer can
     * read any unit's provenance.
     *
     * @return array{id: string, version: string, label: string, description: string}
     */
    public function toArray(): array;


}
