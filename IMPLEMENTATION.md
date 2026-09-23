# Limen AI — Implementation Guide

This document tracks how the package will be built, phase by phase.

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
