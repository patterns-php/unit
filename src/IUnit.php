<?php

declare(strict_types=1);

namespace Patterns;

/**
 * IUnit - a named, versioned identity.
 *
 * Three methods, and nothing else:
 *
 *   create()   the single entry point (create, never construct)
 *   dna()      what it is: a stable name and a version
 *   whoami()   how it introduces itself
 *
 * A Unit is whatever your codebase needs it to be: a strongly typed class with
 * readonly props, an array-backed record, a database row, a remote proxy, an
 * enum case. The pattern only says that it can name and version itself - which
 * is what lets every record it produces say where it came from.
 *
 * Deliberately NOT in this contract:
 *
 *   - props() / toArray()   raw state is the implementation's business; a
 *                           consumer that needs generic access can ask for it
 *                           separately.
 *   - params()              describing what a unit accepts is discoverability,
 *                           a different axis from identity.
 *   - help(), capabilities, teach/learn, execute, events
 *                           those are behaviours, and they differ wildly
 *                           between units. Compose them where you need them.
 *
 * Depend on IUnit, never on a concrete Unit. The pattern stays out of every
 * signature, so implementations stay replaceable and units stay mockable.
 */
interface IUnit
{
    /**
     * The entry point - create, never construct.
     *
     * Typing a static factory in an interface is something PHP can express and
     * TypeScript cannot, and it means generic code can build any unit from its
     * persisted array without knowing the class.
     *
     * Declared as `self` (an IUnit) so an implementation may declare either
     * `self` or `static` - direct callers still get the concrete type.
     *
     * @param array<string, mixed> $props
     */
    public static function create(array $props): self;

    /**
     * Immutable identity.
     */
    public function dna(): IUnitSchema;

    /**
     * Human-readable self-presentation.
     *
     * Derivable from `dna()->label()`, and still here on purpose: a Unit has a
     * voice. Saying its own name is what makes it an actor rather than a
     * record. Implementations inherit the label by default and override only to
     * speak differently.
     */
    public function whoami(): string;
}
