# Limen AI — Implementation Guide

## Current Phase

**Phase 12 — Workflow Engine**

## Phase 11 Completed

1. `AgentKnowledgeRetriever` contract with `DefaultAgentKnowledgeRetriever`
2. `ConfigKnowledgeRetriever` keyword scoring and `VectorKnowledgeRetriever` embedding search
3. `InMemoryVectorStore`, `NullVectorStore`, and `KnowledgeService` upsert helper
4. `KnowledgeFormatter` marks retrieved content as untrusted system context
5. Runtime knowledge injection after memory, before conversation history
6. Config-driven driver bindings (`null`, `config`, `vector`) in service provider
7. Unit, integration, and feature knowledge tests

## Next Implementation Tasks (Phase 12)

1. Workflow definition repository and step runner skeleton
2. Branching and approval step types
3. Workflow resume integration with runtime checkpoints
