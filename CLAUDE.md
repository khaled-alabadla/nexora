# OpenWolf

This project uses OpenWolf for context management. The always-on rules live in `.claude/rules/openwolf.md`; the hooks handle bookkeeping (anatomy index, memory log, read tracking) automatically.

For the full operating protocol (session handoff, memory discipline, bug logging), load the `openwolf` skill, or read `.wolf/OPENWOLF.md`. Regenerate the session handoff with `/handoff`.

# Nexora

Nexora is a production-grade multi-tenant ERP SaaS.

## Core Principles

- Tenant isolation is mandatory.
- Never trust tenant_id from client input.
- Authorization must always be enforced server-side.
- Business logic must not live in controllers.
- Financial operations must use database transactions.
- Inventory operations must be auditable.
- Accounting entries must always remain balanced.
- Every significant feature must have tests.
- Do not modify unrelated files.
- Do not commit unless explicitly requested.

## Development Workflow

Every major feature follows:

PLAN
→ GRILL-ME
→ IMPLEMENT
→ TEST
→ CODE-REVIEW
→ FIX
→ TEST AGAIN
→ DOCUMENT
→ COMPLETE

Never skip GRILL-ME for significant business features.

Never skip CODE-REVIEW after significant implementation.

Never start the next major phase without explicit approval.

## Current Phase

Phase 0 — Foundation

Do not implement business functionality until Phase 0 is approved.