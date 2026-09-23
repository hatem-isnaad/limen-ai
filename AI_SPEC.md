# Limen AI — Master Specification

This document is the canonical product specification for the Limen AI Laravel package.

## Vision

Build a large, reusable Laravel AI Agent Framework called **Limen AI**.

- First delivery: installable Laravel package for the existing Limen 3PL system
- Not a SaaS in v1
- Designed from day one for future SaaS compatibility
- Generic package core; host app owns business logic via Tools, Skills, Services

## Non-Negotiable Principle

The LLM must **never** directly control the application.

| LLM may | Laravel must |
|--------|----------------|
| Understand requests | Authenticate users |
| Suggest tools + arguments | Authorize actions |
| Generate responses | Validate input |
| Reason over allowed context | Execute tools safely |
| | Enforce business rules |
| | Scope data access |
| | Audit and log |

## Subsystems

1. Agents
2. Runtime
3. Tools
4. Skills
5. Workflows
6. Memory
7. Knowledge / RAG
8. Conversations
9. Authorization
10. Human Approval
11. Providers (LLM / Embeddings)
12. HTTP Integrations
13. Queue
14. Broadcasting
15. Chat UI + Themes
16. Attachments
17. Observability
18. Audit
19. Security
20. Artisan CLI + Stubs
21. Testing + Architecture Validation

## Checklist — Phase 01 (Architecture)

- [x] Begin Phase 11 — Knowledge / RAG
- [x] Phase 11 — Config/vector retrievers, vector store abstraction, runtime integration
