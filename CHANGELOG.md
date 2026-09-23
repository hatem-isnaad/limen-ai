# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Phase 05 tool execution pipeline with authorization, validation, idempotency, audit, and events
- `ClassBasedToolExecutor`, `ToolInputValidator`, `LaravelAuthorizationService`, and `LogAuditLogger`
- Tool exceptions and approval-required gate skeleton

### Added (Phase 04)

- Phase 04 agent resolution with `DefaultAgentResolver`, `ResolvedAgent`, and `InstructionComposer`
- `ToolSchemaBuilder` for LLM function calling schemas
- `AgentValidator` and `limen-ai:validate` Artisan command
- Agent resolution, validation, and feature tests

### Added (Phase 03)

- Phase 03 provider system with `LlmProviderManager` and `EmbeddingProviderManager`
- `FakeLlmProvider`, `FakeEmbeddingProvider`, and `OpenAiProvider` skeleton
- `LlmResponseData` value object and provider exceptions
- Provider unit, integration, and architecture tests

### Added (Phase 02)

- Phase 02 config-backed DTOs and repositories for agents, tools, skills, workflows, and knowledge
- `RunContextData` execution context value object
- Service provider bindings for all definition repositories
- Example skill (`general_assistance`) and knowledge collection (`getting_started`)
- Repository unit, integration, and architecture tests

### Added (Phase 01)

- Initial repository bootstrap
- Master specification and architecture documentation
- Phase 01 contract interfaces (skeleton)
- Configuration schema draft
- `.ai/` coding agent guidance structure
- Dependency graph, database proposal, event map, UI architecture docs
- Security model and testing strategy
- PHPUnit / Testbench scaffold

### Notes

- No runtime implementation yet — architecture phase only
