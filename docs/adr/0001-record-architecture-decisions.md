# ADR-0001: Record architecture decisions

- Status: Accepted
- Date: 2026-09-09

## Context

Nexora is a long-lived, multi-phase project. Significant technical decisions
(architecture style, tenancy model, authentication transport, testing strategy)
need a durable, reviewable record so that future contributors understand *why*
the system looks the way it does, not just *what* it does.

## Decision

We keep lightweight Architecture Decision Records (ADRs) in `docs/adr/`, one file
per decision, numbered sequentially (`NNNN-title.md`).

Each ADR has: Status, Date, Context, Decision, Consequences.

Statuses: `Proposed`, `Accepted`, `Superseded by ADR-XXXX`, `Deprecated`.

An ADR is immutable once `Accepted`. A changed decision is a new ADR that
supersedes the old one.

## Consequences

- Decisions are discoverable and reviewable in version control.
- Minimal process overhead — a few paragraphs of Markdown.
- The `docs/*.md` reference documents describe the *current* state; ADRs explain
  *how we got there*.
