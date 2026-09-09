# anatomy.md

> Auto-maintained by OpenWolf. Last scanned: 2026-09-09T09:36:27.580Z
> Files: 21 tracked | Anatomy hits: 0 | Misses: 0

> Project structure index. Auto-maintained by OpenWolf hooks and daemon.
> Run `openwolf scan` to generate, or wait for the first Claude Code session.
> Status: Pending initial scan

## ./

- `.dockerignore` — Keep the PHP image build context small and secret-free. (~67 tok)
- `.editorconfig` — https://editorconfig.org (~104 tok)
- `.gitattributes` — Normalize line endings: LF in the repo, regardless of host OS. (~220 tok)
- `.gitignore` — Git ignore rules (~336 tok)
- `AGENTS.md` — OpenWolf (~75 tok)
- `CLAUDE.md` — OpenWolf (~99 tok)
- `docker-compose.yml` — Docker Compose services (~890 tok)
- `GEMINI.md` — OpenWolf (~75 tok)
- `Makefile` — Nexora — developer command surface. (~1002 tok)
- `README.md` — Project documentation (~819 tok)

## docker/mysql/init/

- `01-create-test-database.sql` — Runs once on first MySQL container start (empty data dir). (~116 tok)

## docker/nginx/

- `default.conf` (~276 tok)

## docker/php/

- `Dockerfile` — Docker container definition (~512 tok)
- `entrypoint.sh` (~168 tok)
- `php.ini` (~117 tok)
- `xdebug.ini` (~78 tok)

## docs/adr/

- `0001-record-architecture-decisions.md` — ADR-0001: Record architecture decisions (~255 tok)
- `0002-modular-monolith-layout.md` — ADR-0002: Modular monolith layout with hand-rolled PSR-4 modules (~628 tok)
- `0003-docker-dev-environment.md` — ADR-0003: Full Docker development environment (~416 tok)
- `0004-authentication-transport.md` — ADR-0004: Authentication transport — Sanctum SPA cookie session (~504 tok)
- `0005-test-database-mysql.md` — ADR-0005: Run the test suite against MySQL, not SQLite (~451 tok)
