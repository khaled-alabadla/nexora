---
description: learned preferences, project conventions, and Do-Not-Repeat rules
budget_tokens: 2000
---
# Cerebrum

> OpenWolf's learning memory. Updated automatically as the AI learns from interactions.
> Do not edit manually unless correcting an error.
> Last updated: 2026-09-09

## User Preferences

- Autonomous build mode: proceed phase→phase without asking; commit/merge locally
  after gates pass. Only stop for missing credentials / unsafe destructive ops /
  genuinely ambiguous business requirements. Report `Phase X completed…` not
  `ready for review`.
- Environment: `git push` is classifier-blocked; no `gh`/token. Work local-only
  (branches, commits, `--no-ff` merges, phase tags). CI runs on first push.
- Docker registry pulls are flaky/slow; npm registry ~17s/request. Prefer
  reusing running containers (`exec`) over `run --rm`; keep heavy trees on named
  volumes (Windows bind-mount is slow: dump-autoload was 150s on bind mount).

## Key Learnings

- **Project:** Nexora

## Do-Not-Repeat

<!-- Mistakes made and corrected. Each entry prevents the same mistake recurring. -->
<!-- Format: [YYYY-MM-DD] Description of what went wrong and what to do instead. -->

## Decision Log

<!-- Significant technical decisions with rationale. Why X was chosen over Y. -->

- [2026-09-09] **Module system:** hand-rolled PSR-4 `Modules\` -> `backend/modules/`, one ServiceProvider per module registered explicitly in `bootstrap/providers.php`. No `nwidart/laravel-modules` (CLAUDE.md: no deps without justification). → ADR-0002.
- [2026-09-09] **Auth transport:** Sanctum SPA cookie session (HttpOnly, CSRF) for the first-party React app; personal-access tokens kept available for future external API clients. Contradicts original API.md Bearer example — API.md to be updated. → ADR-0004.
- [2026-09-09] **Dev environment:** full Docker (php 8.4-fpm, node, mysql:8.4, redis:7, queue, scheduler, mailpit). Host PHP is 8.2 and unsupported; all backend commands run via `compose exec`. → ADR-0003.
- [2026-09-09] **Test database:** MySQL 8 (dedicated `nexora_test` schema), NOT SQLite — DECIMAL/`FOR UPDATE` locking/FK/JSON semantics matter for financial+inventory modules. → ADR-0005.
- [2026-09-09] **Git/CI:** `git init` now, author `.github/workflows/ci.yml`, defer GitHub remote (CI verified once remote provisioned). No commits without explicit request.
- [2026-09-09] **PHPStan:** level 6 in Phase 0; level 8 is a Phase 1 exit criterion. **Coverage floor:** 60% in Phase 0 CI, raised per phase.
