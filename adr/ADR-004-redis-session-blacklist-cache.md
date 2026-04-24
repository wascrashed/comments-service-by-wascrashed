# ADR-004: Redis как единый session/blacklist/cache слой

- **Status**: Accepted
- **Date**: 2026-04-29
- **Owner**: Platform + SRE

## Context

Нужен быстрый распределенный слой для session-state, token blacklist и hot cache.

## Decision

Использовать Redis как централизованный in-memory backend с failover.

## Consequences

### Positive

- Устраняет необходимость sticky sessions.
- Низкая latency для session/check/cache операций.

### Negative

- Redis становится критической зависимостью.
- Требуются failover, capacity запас и отдельные runbook-процедуры.

## Rejected alternatives

- In-memory per instance: плохо для масштабирования и отказоустойчивости.
