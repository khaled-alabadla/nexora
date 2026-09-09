# ADR-0002: Modular monolith layout with hand-rolled PSR-4 modules

- Status: Accepted
- Date: 2026-09-09

## Context

`docs/ARCHITECTURE.md` mandates a modular monolith with business modules under
`backend/modules/`. We need a concrete mechanism for:

- autoloading module code
- registering module service providers
- discovering module routes, migrations, factories, and tests

Options considered:

1. **`nwidart/laravel-modules`** — mature package with generators and per-module
   asset/config handling.
2. **Hand-rolled PSR-4 convention** — a `Modules\` namespace mapped to
   `backend/modules/`, with one service provider per module.

## Decision

Use a **hand-rolled PSR-4 convention**. `CLAUDE.md` requires that dependencies be
justified by a concrete need, and our module requirements are modest and stable.

Structure of a module (`backend/modules/<Name>/`):

```
modules/Identity/
├── src/                     # Modules\Identity\  (PSR-4 root)
│   ├── Providers/IdentityServiceProvider.php
│   ├── Models/
│   ├── Http/
│   └── Services/
├── routes/
│   ├── api.php
│   └── console.php
├── database/
│   ├── migrations/
│   └── factories/           # Modules\Identity\Database\Factories\
├── tests/
│   ├── Feature/
│   └── Unit/
└── module.json              # metadata: name, description
```

Composer autoload (root `backend/composer.json`):

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Modules\\": "modules/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Modules\\": "modules/"
    }
  }
}
```

Each module's service provider is registered **explicitly** in
`backend/bootstrap/providers.php` (no auto-discovery magic). It is responsible for
loading that module's routes (inside a `Route::prefix('api/v1')` group),
migrations, and factories.

A generator script (`backend/scripts/make-module.php`, invoked via
`make module name=<Name>`) scaffolds a new module from a template and appends its
provider to `bootstrap/providers.php`.

Pest is configured to discover `modules/*/tests` in addition to `tests/`.

## Consequences

- Zero third-party module framework; the mechanism is ~1 base provider + a
  template + a small generator, all readable in a few minutes.
- Explicit provider registration means the list of active modules is greppable in
  one file.
- We own the convention; if module count or complexity grows substantially we can
  revisit `nwidart/laravel-modules` (this ADR would be superseded).
- Slightly more upfront wiring than installing a package.
