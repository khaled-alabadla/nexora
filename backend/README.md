# Nexora — Backend

Laravel 12 (PHP 8.4) modular-monolith API for Nexora.

- Architecture: [`../docs/ARCHITECTURE.md`](../docs/ARCHITECTURE.md)
- Module convention: [`../docs/adr/0002-modular-monolith-layout.md`](../docs/adr/0002-modular-monolith-layout.md)
- API contract: [`../docs/API.md`](../docs/API.md)
- Local setup & commands: [`../docs/DEVELOPMENT.md`](../docs/DEVELOPMENT.md)
- Test strategy: [`../docs/TESTING.md`](../docs/TESTING.md)

## Quick reference

```bash
# from the repo root — everything runs in Docker
make setup                      # install, key:generate, migrate
make check                      # pint + phpstan + pest (the backend gate)
make artisan ARGS="migrate"
make module name=Sales          # scaffold a new module
```

Layout: `app/` (framework + shared support), `modules/<Name>/` (business
modules), `config/nexora.php` (api prefix, money precision), `routes/api.php`
(core routes — health).
