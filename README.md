# Patterns — Unit

**A named, versioned identity for the code that produces your data.**

Two interfaces turn any piece of code that writes something — a score, a price, a
report row, a log line — into something whose output can be attributed later: by
you, by a cron job, by an auditor, months after the code that produced it has
been refactored twice.

Part of the **Patterns** collection: small, rock-solid, dependency-free building
blocks. One pattern, one package.

- Package: `patterns/unit`
- Contracts: `Patterns\IUnit`, `Patterns\IUnitSchema`
- Dependencies: **none**
- PHP: `>=8.1`

---

## Install

```bash
composer require patterns/unit
```

Contracts only — no implementation ships. Whatever a unit is in your codebase,
it only has to be able to name and version itself.

## The problem

Something in production computes a number, and later that number is wrong. The
first question is always the same:

> Which code produced this, and which version of it was running?

In most PHP codebases the answer lives in a `git blame` over a class that may
have been renamed since, on a server whose checkout you are not sure about. The
data itself knows nothing about where it came from. So you cannot tell whether a
figure is stale because the input changed or because the formula changed — and
you cannot safely re-run anything, because you do not know what produced what.

## The pattern

```php
namespace Patterns;

interface IUnit
{
    public static function create(array $props): self;
    public function dna(): IUnitSchema;
    public function whoami(): string;
}

interface IUnitSchema extends JsonSerializable
{
    public function id(): string;                       // stable name, never a class name
    public function version(): string;                  // semantic version of that name
    public function label(): string;                    // "SupplierRanking v1.1.0"
    public function description(): string;              // one sentence on what it is for
    public function versions(): array;                  // version ledger, oldest first
    public function toArray(): array;                   // the persisted shape
    public static function create(array $props): self;  // read back what toArray() wrote
}
```

Three methods for a unit, and its DNA. Nothing else.

`create()` is the only way in — never construct. `dna()` is the identity.
`whoami()` is the voice.

## Unit vs ValueObject

These are the two categories, and they are not alternatives — a unit often
contains value objects.

| | **ValueObject** | **Unit** |
|---|---|---|
| Identity | its value | its name — `id()` + `version()` |
| Two equal ones | interchangeable | still two versions of the same named thing |
| Serialization | the whole object | the DNA; the rest is the implementation's business |
| Lifetime | created, compared, discarded | created, versioned, deployed, **referenced by stored data** |
| Version history | none — a value has no history | part of the contract (`versions()`) |
| Answers | *is this a valid value?* | *which code produced this, and which version?* |

A `Money(1000, 'EUR')` **is** its data: two of them with the same amount and
currency are the same thing, and it would be strange to ask which one was made
first.

A ranking unit **has** a name. Two runs of the same unit producing different
numbers is not a contradiction — it is the interesting case, and you need to know
which version of the ranking you are looking at.

If you cannot say which version produced a number, it is not a value — it is an
unattributed claim.

## Boilerplate

Two files per unit. Copy them.

### 1. The DNA

```php
<?php
declare(strict_types=1);

namespace App\Ranking;

use Patterns\IUnitSchema;

final class RankingSchema implements IUnitSchema
{
    /** @param array<string, mixed> $props */
    private function __construct(private readonly array $props)
    {
    }

    /** @param array<string, mixed> $props */
    public static function create(array $props): self
    {
        return new self([
            'id'          => (string) $props['id'],
            'version'     => (string) $props['version'],
            'label'       => (string) ($props['label'] ?? $props['id'] . ' v' . $props['version']),
            'description' => (string) ($props['description'] ?? ''),
            'versions'    => array_values((array) ($props['versions'] ?? [])),
        ]);
    }

    public function id(): string           { return $this->props['id']; }
    public function version(): string      { return $this->props['version']; }
    public function label(): string        { return $this->props['label']; }
    public function description(): string  { return $this->props['description']; }
    public function versions(): array      { return $this->props['versions']; }
    public function toArray(): array       { return $this->props; }
    public function jsonSerialize(): array { return $this->props; }
}
```

You may extend `Patterns\ValueObject` for equality and JSON for free — or not.
The contract asks for seven methods and nothing about your base class.

### 2. The unit

```php
<?php
declare(strict_types=1);

namespace App\Ranking;

use Patterns\IUnit;
use Patterns\IUnitSchema;

final class RankingUnit implements IUnit
{
    public const VERSION = '1.1.0';

    /** The identity, declared once. */
    private const DNA = [
        'id'          => 'supplier-ranking',
        'version'     => self::VERSION,
        'description' => 'Ranks suppliers by weighted velocity.',
        'versions'    => [
            ['version' => '1.0.0', 'date' => '2026-01-15', 'notes' => 'Initial release'],
            ['version' => '1.1.0', 'date' => '2026-10-05', 'notes' => 'Weight cadence per supplier'],
        ],
    ];

    /** @param array<string, mixed> $config */
    private function __construct(
        private readonly RankingSchema $dna,
        private readonly array $config,
    ) {
    }

    /** @param array<string, mixed> $props */
    public static function create(array $props): self
    {
        return new self(
            RankingSchema::create($props['dna'] ?? self::DNA),
            (array) ($props['config'] ?? []),
        );
    }

    public function dna(): IUnitSchema
    {
        return $this->dna;
    }

    public function whoami(): string
    {
        return $this->dna->label() . ' | ' . json_encode($this->config);
    }

    // ---- the actual work ----

    /** @return array{ranked: list<array<string, mixed>>} */
    public function rank(array $suppliers): array
    {
        // ...
        return ['ranked' => []];
    }
}
```

That is the whole pattern: a schema, a factory, the work. No base class, no
registry, no capability table.

`dna()` is **instance-only** by contract, so the identity literal lives in a
private constant instead of a second method. That gives exactly one place to
change the identity - and avoids two methods competing for the same name.

## DNA in practice

The reason DNA exists is that it can be **persisted next to the data**. A price
intelligence system ranks products by a computed *force*; the number is only
meaningful together with the algorithm that produced it.

```php
// 1. write: attach the DNA of the unit that produced the row
$unit = RankingUnit::create(['config' => ['w_flow' => 1.0, 'w_cadence' => 0.8]]);

foreach ($suppliers as $supplier) {
    $rows[] = [
        'ean'   => $supplier['ean'],
        'force' => $unit->rank($supplier),
        'unit'  => $unit->dna()->toArray(),   // provenance, stored with the data
    ];
}
```

```json
{
  "ean": "5901234123457",
  "force": 63723.0,
  "unit": {
    "id": "supplier-ranking",
    "version": "1.1.0",
    "label": "SupplierRanking v1.1.0",
    "description": "Ranks suppliers by weighted velocity.",
    "versions": [
      { "version": "1.0.0", "date": "2026-01-15", "notes": "Initial release" },
      { "version": "1.1.0", "date": "2026-10-05", "notes": "Weight cadence per supplier" }
    ]
  }
}
```

```php
// 2. read: which version produced this row - and what changed in it?
$dna = RankingSchema::create($row['unit']);

$dna->label();       // "SupplierRanking v1.1.0"
$dna->versions();    // the ledger: 1.0.0 initial, 1.1.0 cadence weighting
$dna->description(); // what that version was for
```

The ledger is the part that pays off. It is not a changelog in a file nobody
reads — it travels *with the data*, so a figure can explain itself:

> These numbers came from `supplier-ranking v1.0.0`. In `1.1.0` cadence became
> weighted per supplier, so any row still tagged `1.0.0` predates that change.

Without it, that sentence is guesswork.

## Use cases where unit versioning is a game changer

**Rankings, scores and reports.** Comparing two runs is meaningless until you
know the runs used the same formula version. With DNA you can group and diff by
version instead of hoping.

**Backfills and reprocessing.** "Which rows are stale?" becomes a query —
`WHERE unit->>'version' = '1.0.0'` — instead of a reasoning exercise about
deployment dates. You re-run exactly what needs re-running.

**Debugging production data.** A wrong price, a strange score, a missing row:
the row names the code and the version, so you read the *right* implementation
instead of the current one.

**Rollouts and A/B.** Run `1.1.0` for a slice of traffic and compare outcomes,
because every result carries which side it came from. Rolling back does not
rewrite history — old rows keep their truth.

**Audit and compliance.** "Who or what produced this number, when, and under
which rules?" is answered from the data itself, not from logs that have rotated.

**Multi-service and multi-language systems.** The id is a plain string, not a
class name, so a service in another language can read and write the same
provenance. Renaming or moving a class never breaks the trail.

**Observability.** A unit that logs can tag every line with its own DNA, so a
log stream tells you which version of which component is speaking.

## What is deliberately not in this pattern

| Not here | Where it belongs |
|---|---|
| `props()`, `toArray()` on the unit | the implementation — raw state is its own business |
| `params()`, `help()` | discoverability and documentation, a different axis from identity |
| capabilities, registries, `execute()` | composition is a constructor; dispatch is the caller's job |
| teach / learn, runtime capability transfer | a registry's problem, not a unit's |
| events, emitters, channels | compose `patterns/event-channel` when you need it |
| validators, builders, base classes | the pattern asks for seven methods; supply the rest yourself |

The contract is intentionally narrow so that a unit can be *anything*: a
strongly typed class with readonly props, an array-backed record, a database row,
a remote proxy, an enum case.

## Implementing a unit

1. **Name it.** Pick an `id` you will never want to change — a name, not a class.
2. **Version it.** Semantic version. Change it whenever the output can change.
3. **Keep the ledger honest.** Record *released* versions only; the ledger is
   what lets a stored record say which version produced it.
4. **Make `create()` the only entry point.** No public constructor.
5. **Persist the DNA with the data.** A version you did not store is a version
   you cannot recover.
6. **Depend on `IUnit`, never on a concrete unit.** The contract stays out of
   signatures; implementations stay replaceable.

## Tests

```bash
composer install
composer test
# or
vendor/bin/phpunit
```

The suite pins the shape of both interfaces (it fails if either grows), proves
they are satisfiable without extending anything, and exercises the provenance
round-trip.

## License

MIT.
