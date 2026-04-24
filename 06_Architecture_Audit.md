# Архитектурный аудит (2026-04-29)

## Scope

- `Comments`, `Media`, `SSO`, NFR, capacity и операционная готовность. Каждая архитектура рассматривается независимо.

## Итоговая оценка

- **Состояние**: Good baseline, требует hardening.
- **Оценка зрелости**: 8/10.
- **Главный вывод**: архитектурный фундамент сильный, основные риски лежат в операционной дисциплине и кэш/refresh пиках.

## Findings

### P1 (критично закрыть в первую очередь)

1. **Refresh storm в SSO**
   - Риск: массовое истечение токенов перегружает refresh endpoint.
   - Меры: jitter TTL, refresh backoff, circuit breaker, защитный режим.
   - Статус: **Mitigation documented**, требуется нагрузочная валидация.

2. **CPU spikes на media resize**
   - Риск: on-demand resize повышает p95 и провоцирует деградацию.
   - Меры: async processing, pre-generation top размеров, queue depth guardrail.
   - Статус: **Mitigation documented**, требуется реализация worker контура.

### P2 (высокий приоритет)

1. **Large payload в comments tree**
   - Меры: root pagination, lazy children, feature flag деградации.
   - Статус: **Documented**.

2. **Критичность Redis слоя**
   - Меры: failover, SLO на Redis ops, runbook failover/recovery.
   - Статус: **Partially documented**, runbook добавлен.

3. **Риск stale cache**
   - Меры: event-driven invalidation + TTL fallback.
   - Статус: **Documented**, нужна верификация на стенде.

### P3 (улучшения)

1. Формализовать проверку модели нагрузки через факт/модель delta.
2. Зафиксировать ADR как обязательный merge-gate для архитектурных изменений.
3. Проводить quarterly game-day по сценариям SSO/Redis/Storage.

## Исправления, внесенные по аудиту

- Добавлены metadata-блоки владельцев/статуса/даты ревью.
- Добавлены trade-offs и rejected alternatives в доменные документы.
- Добавлены guardrails и деградационные режимы.
- Добавлена production-валидация capacity модели.
- Добавлен инцидентный runbook.
- Добавлен набор ADR 001..005.

## План закрытия рисков

- **Sprint 1**: SSO storm protection + runbook drills.
- **Sprint 2**: Media async/pre-gen контур + queue guardrails.
- **Sprint 3**: Comments payload control + cache invalidation hardening.
