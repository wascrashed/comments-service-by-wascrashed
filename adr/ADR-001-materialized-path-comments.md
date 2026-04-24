# ADR-001: Materialized Path для комментариев

- **Status**: Accepted
- **Date**: 2026-04-29
- **Owner**: Comments Domain Team

## Context

Нужно эффективно читать деревья комментариев с ветками и фильтрами при read-heavy профиле.

## Decision

Использовать Materialized Path (`path`, `level`) с транзакционным write-path.

## Consequences

### Positive

- Быстрое чтение дерева/веток.
- Предсказуемая модель индексов и сериализации.

### Negative

- Риски lock contention при burst insert.
- Нужны guardrails по payload и глубине.

## Rejected alternatives

- Adjacency list only: плохо масштабируется для tree-read.
- Nested sets: дорогие перестройки при частых вставках.
