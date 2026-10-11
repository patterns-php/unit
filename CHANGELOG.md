# Changelog

All notable changes to `patterns/unit` are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) ·
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-10-11

### Added

- `Patterns\IOperator` — the contract for reaching a set of tools: `tools()`,
  `execute()`, `help()`. Deliberately not a unit: no identity, no version, no
  `create()`, no DNA, and no AI vocabulary. An operator is wired to live services,
  so a props factory would be a signature nobody could honour.

### Removed

- `IUnitSchema::versions()`, and the `versions` key it wrote into `toArray()`.
  A changelog of released versions is the repository's job — a persisted record
  needs to name the version that produced it, not list every version that ever
  existed. Callers of `versions()` are affected; implementers are not, and a unit
  that genuinely manages several versions can declare that in its own contract.

[1.0.1]: https://github.com/patterns-php/unit/releases/tag/v1.0.1

## [1.0.0] - 2026-10-05

### Added

- `Patterns\IUnit` — a named, versioned identity: `create()`, `dna()`, `whoami()`.
- `Patterns\IUnitSchema` — the DNA contract, `JsonSerializable`, round-trippable
  through `toArray()` / `create()`.
- A contract test suite that pins the shape of both interfaces, proves they are
  satisfiable without a base class, and exercises the provenance round-trip.

[1.0.0]: https://github.com/patterns-php/unit/releases/tag/v1.0.0
