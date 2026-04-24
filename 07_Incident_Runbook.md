# Incident Runbook (Architecture Suite)

## Metadata

- **Owner**: SRE On-call
- **Status**: Active
- **Last Reviewed**: 2026-04-29

## 1. SSO refresh degradation

### Триггеры

- `refresh_failure_rate > 5%` за 5 минут.
- `refresh_p95 > 250ms` более 10 минут.

### Действия

1. Включить защитный режим refresh (backoff + ограничение параллелизма).
2. Проверить доступность SSO upstream и latency каналов.
3. Активировать degraded UX banner для login/refresh.
4. При длительном сбое выполнить контролируемый logout батчами.

### Выход из инцидента

- `refresh_failure_rate < 1%` и `refresh_p95` в SLO 30 минут подряд.

## 2. Media resize overload

### Триггеры

- `media_read_p95 > 400ms` 10 минут.
- `resize_queue_depth > 500`.

### Действия

1. Переключить resize-path в async-only.
2. Временно отдавать оригиналы без on-demand оптимизации.
3. Ограничить heavy-size профили.
4. Прогреть top-size деривативы pre-generation job.

### Выход из инцидента

- Очередь стабильно < 100 и p95 в пределах SLO 30 минут.

## 3. Redis failover / saturation

### Триггеры

- Высокий `redis_ops_latency`, drop соединений, рост timeouts.

### Действия

1. Перевести трафик на failover node.
2. Временно отключить non-critical cache keys.
3. Приоритизировать session/blacklist трафик над вторичными кэшами.
4. Проверить persistence и репликацию после восстановления.

### Выход из инцидента

- Ошибки подключения и latency возвращены к baseline.

## 4. Comments lock contention

### Триггеры

- Рост `unique_conflict_rate`, `comment_create_p95` > 350ms.

### Действия

1. Уменьшить размер транзакции write-path.
2. Ограничить burst на один `post_id` rate limit-ом.
3. Проверить индексы и план запросов.
4. Включить деградацию read payload (только корни + lazy children).

### Выход из инцидента

- `unique_conflict_rate < 1%`, `comment_create_p95` стабилен в SLO.
