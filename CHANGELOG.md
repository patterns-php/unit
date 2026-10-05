# Changelog

All notable changes to `patterns/unit` are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) ·
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-05

### Added

- `Patterns\IUnit` — a named, versioned identity: `create()`, `dna()`, `whoami()`.
- `Patterns\IUnitSchema` — the DNA contract, `JsonSerializable`, round-trippable
  through `toArray()` / `create()`.
- A contract test suite that pins the shape of both interfaces, proves they are
  satisfiable without a base class, and exercises the provenance round-trip.

[1.0.0]: https://github.com/patterns-php/unit/releases/tag/v1.0.0
