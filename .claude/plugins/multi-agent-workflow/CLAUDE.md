# Multi-Agent Workflow Plugin

A compound engineering framework for coordinating multiple AI agents on software development.

## The Flow

```
  ROUTE --> SHAPE --> PLAN --> WORK --> REVIEW --> COMPOUND
  (entry)  (optional)  (80%)   (15%)    (4%)       (1%)

  ROUTE --> QUICK (lightweight alternative for simple tasks)
```

**Every request starts with routing.** No exceptions.

### Core Commands

| # | Command | Purpose | When |
|---|---------|---------|------|
| 0 | `/workflows:route` | Classify request, ask questions, select workflow | **Always first** |
| 0b | `/workflows:quick` | Lightweight path for simple tasks (<=3 files) | Simple tasks |
| 1 | `/workflows:shape` | Separate problem from solution, spike unknowns | Complex/unclear features |
| 2 | `/workflows:plan` | Architecture-first planning with SOLID constraint | Before implementation |
| 3 | `/workflows:work` | Execute with TDD + Bounded Correction Protocol | After plan COMPLETED |
| 4 | `/workflows:review` | Multi-agent quality review | After work COMPLETED |
| 5 | `/workflows:compound` | Capture learnings for future acceleration | After review APPROVED |

### Support Commands

| Command | Purpose |
|---------|---------|
| `/workflows:status` | View all roles' progress |
| `/workflows:help` | Quick reference and guidance |
| `/workflows:discover` | Auto-analyze project architecture (`--setup` for onboarding) |

### Flow Guards (enforced)

- `plan` requires: routing completed
- `work` requires: plan status = COMPLETED in `tasks.md` Workflow State
- `review` requires: work status = COMPLETED in `tasks.md` Workflow State
- `compound` requires: review status = APPROVED in `tasks.md` Workflow State

---

## Automatic Operations (integrated into core commands)

| Operation | Integrated into |
|-----------|----------------|
| Git sync | `plan`, `work` |
| Checkpoint/save progress | `work` (Step 7) |
| TDD enforcement | `work` (Step 5: TDD cycle) |
| Spec validation | `plan` (Phase 2: Specs) |
| Role assignment | `work` (auto-detects role) |

---

## Agents

| Category | Agents | Invoked by |
|----------|--------|------------|
| Roles (3) | planner, implementer, reviewer | `plan`, `work`, `review` |
| Review (4) | code-reviewer, architecture-reviewer, security-reviewer, performance-reviewer | `review` |
| Research (2) | codebase-analyzer, learnings-researcher | `route`, `plan` |
| Workflow (2) | diagnostic-agent, spec-analyzer | `work`, `review` |

Agents are invoked automatically by core commands. You rarely need to invoke them directly.

## Skills (14)

| Category | Skills |
|----------|--------|
| Core | consultant, checkpoint, git-sync, commit-formatter |
| Quality | test-runner, coverage-checker, lint-fixer |
| Compound | spec-merger, validation-learning-log |
| Integration | mcp-connector |
| SOLID | solid-analyzer, criteria-generator |
| Shaping | shaper, breadboarder |

## Context Activation Model

| Content | Activation | When Loaded |
|---------|-----------|-------------|
| This file (CLAUDE.md) | Always | Every session |
| Operational rules (`core/rules/framework_rules.md`) | Always | Every session |
| Scoped rules (`core/rules/*-rules.md`) | Software-determined | When matching file types are edited |
| Role definitions (`core/roles/*.md`) | LLM-determined | When role is active |
| Skills (`skills/`) | Human-triggered | On `/skill:X` invocation |
| Review agents (`agents/review/*.md`) | Human-triggered | During `/workflows:review` |
| Project rules (`.ai/project/rules/`) | Software-determined | When matching patterns apply |

Heavy skills and review agents run with `context: fork` -- isolated context windows returning summaries only. See `core/docs/CONTEXT_ENGINEERING.md`.

## Capability Providers

Model-agnostic and execution-agnostic. Abstracts capabilities (parallelization, context management, fork strategy) behind providers that auto-detect the running model. Config: `core/providers.yaml`, details: `core/docs/CAPABILITY_PROVIDERS.md`.

When `providers.yaml` is set to `auto` (default), resolve providers using the Detection Protocol before executing provider-dependent operations.

## Key Patterns

- **Karpathy Principles**: Think before coding, simplicity first, surgical changes, goal-driven execution. See `core/docs/KARPATHY_PRINCIPLES.md`.
- **Bounded Correction Protocol**: 3 deviation types with scale-adaptive limits (simple: 5, moderate: 10, complex: 15). Includes diagnostic escalation (invokes diagnostic-agent after 3 consecutive same errors). See `core/rules/testing-rules.md`.
- **Compound Capture**: After each feature, extract patterns and update rules via `/workflows:compound`.
- **Validation Learning**: AI self-questions solutions, logs answers for future use. See `core/docs/VALIDATION_LEARNING.md`.
- **SOLID Constraint**: Phase 3 solutions must be COMPLIANT per contextual analysis. See `core/architecture-reference.md` and `openspec/specs/architecture-profile.yaml`.

## State Management

All roles communicate via `tasks.md` (Workflow State section in `openspec/changes/{slug}/tasks.md`). Status values: `PENDING`, `IN_PROGRESS`, `BLOCKED`, `WAITING_API`, `COMPLETED`, `APPROVED`, `REJECTED`.

## Best Practices

1. Route first -- every request through `/workflows:route`
2. Ask when unclear -- confidence < 60% means ask before acting
3. State assumptions before coding
4. Define testable success criteria
5. Implement only what's requested
6. Touch only essential code, preserve existing style
7. Plan 80%, execute 20%
8. Write tests before implementation (TDD)
9. Snapshot before breaks or risky operations

## Reference Documentation

- **Docs** (`core/docs/`): `CAPABILITY_PROVIDERS`, `ROUTING_REFERENCE`, `KARPATHY_PRINCIPLES`, `CONTEXT_ENGINEERING`, `SESSION_CONTINUITY`, `MCP_INTEGRATION`, `VALIDATION_LEARNING`
- **Rules** (`core/rules/`): `framework_rules`, `testing-rules`, `security-rules`, `git-rules`
- **Other**: `core/providers.yaml`, `core/architecture-reference.md`

---

**Version**: 3.1.0 | **Aligned with**: Compound Engineering + Karpathy + Context Engineering (Fowler) + Capability Providers + Shape Up (Singer) + AI Validation Learning + GSD + BMAD
