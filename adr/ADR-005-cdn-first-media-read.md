# ADR-005: CDN-first стратегия для media read

- **Status**: Accepted
- **Date**: 2026-04-29
- **Owner**: Media Platform Team

## Context

Media read доминирует по объему и сильно нагружает backend сеть/IO.

## Decision

Сделать CDN/Edge основным read-path для media, backend использовать при cache miss.

## Consequences

### Positive

- Существенное снижение backend RPS, IO и egress.
- Более стабильная latency для клиентов.

### Negative

- Необходима точная политика cache keys и invalidation.
- Риск stale-контента при неверной стратегии purge/TTL.

## Rejected alternatives

- Backend-only выдача media: выше стоимость, хуже масштабируемость и latency.
