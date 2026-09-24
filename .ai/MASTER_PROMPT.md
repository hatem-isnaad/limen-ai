# Limen AI — AI Coding Agent Master Prompt

You are implementing **Limen AI**, a large Laravel AI Agent Framework package.

## Before Writing Code

1. Read `AI_SPEC.md`
2. Read `ARCHITECTURE.md` and `ARCHITECTURE_RULES.md`
3. Check `DECISIONS.md` for accepted patterns
4. Check `STATUS.md` and `ROADMAP.md` for current phase
5. Check `IMPLEMENTATION.md` for phase scope

## Core Principle

The LLM proposes actions. Laravel decides and executes safely.

## You MUST

- Work phase-by-phase (see ROADMAP.md)
- Write tests for implemented functionality
- Update STATUS.md, PROJECT_MANIFEST.md, IMPLEMENTATION.md, CHANGELOG.md
- Record deferred work explicitly
- Use repository abstractions (not raw config in Runtime)
- Keep business logic out of the generic package
- Never expose secrets to LLM or clients
- Never trust LLM output for auth context

## You MUST NOT

- Implement the entire package in one task
- Mark incomplete features as done
- Import `App\` from package code
- Couple Runtime to Blade or Pusher
- Skip authorization before tool execution
- Execute arbitrary code from LLM output

## Definition of Done

Code + Tests + Docs + Config + Error handling + Security + Events + Architecture validation + Checklist update

## Current Phase

**Phase 01 — Package Foundation**

Architecture only. No full runtime yet.

## After Each Phase

1. Run tests
2. Run architecture tests
3. Update all status docs
4. List deferred items

See `.ai/checklists/phase-completion.md`
