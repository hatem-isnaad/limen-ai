# Limen AI

**Limen AI** is a reusable Laravel AI Agent Framework. It provides the infrastructure for building AI-powered assistants inside any Laravel application while keeping business logic, authorization, and data access in the host app.

## Status

This repository is in **Phase 01 — Architecture & Foundation**. Runtime implementation has not started yet.

See:

- [AI_SPEC.md](AI_SPEC.md) — master specification
- [ARCHITECTURE.md](ARCHITECTURE.md) — architecture proposal
- [ROADMAP.md](ROADMAP.md) — phased implementation plan
- [STATUS.md](STATUS.md) — current progress

## Core Principle

The LLM proposes actions. Laravel decides whether they are allowed and executes them safely.

## First Host Application

The first integration target is **Limen**, a 3PL/Fulfillment management system. The package itself remains generic; Limen-specific tools live in the host application.

## License

MIT
