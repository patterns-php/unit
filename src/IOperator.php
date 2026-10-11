<?php

declare(strict_types=1);

namespace Patterns;

/**
 * IOperator - a set of tools, and one way in.
 *
 * Not a Unit, and not a ValueObject - a third shape. It has no identity and no
 * version because it is not a thing: it is a *way of reaching* other things.
 * What it owns is a catalogue of named tools and a dispatch to them.
 *
 * Standing alone rather than extending IUnit is the point:
 *
 *   IUnit       a named, versioned identity - what something IS.
 *   IOperator   a dispatch - what can be REACHED, and under what name.
 *
 * A unit can hold an operator, be wrapped by one, or be reached through one, and
 * none of that gives the operator an identity of its own. One that wants
 * provenance may implement IUnit as well - two interfaces, one class, no base.
 *
 * Nothing here is AI-specific, and that is deliberate. A model is one caller; a
 * CLI, an HTTP route, a queue worker, a cron job and another unit are others.
 * The same shape serves all of them, and a tool written for one is available to
 * all, because the catalogue is data and the dispatch is a name.
 */
interface IOperator
{
    /**
     * The catalogue: everything this operator can dispatch, keyed by the name a
     * caller would use.
     *
     * This is the primitive, and `help()` is one rendering of it - not the other
     * way round. Whoever wires an operator to a model needs the parameters as
     * well as the prose, and parsing help text to get them would be absurd.
     *
     * @return array<string, array{name: string, description: string, parameters: array<string, mixed>}>
     */
    public function tools(): array;

    /**
     * Do the named thing.
     *
     * @param array<string, mixed> $arguments
     *
     * @throws \InvalidArgumentException when there is no such tool
     */
    public function execute(string $tool, array $arguments = []): mixed;

    /**
     * The catalogue, rendered for a reader - a human, or a model that was handed
     * this as a tool of its own.
     */
    public function help(): string;
}
