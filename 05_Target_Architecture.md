# Целевая архитектура

Документ фиксирует «красивую» целевую картину и архитектурные принципы для трёх независимых проектов: `Comments`, `Media`, `SSO`.
Каждая архитектура рассматривается отдельно, без предполагаемой тесной интеграции.

## Metadata

- **Owner**: Platform Architecture Group
- **Status**: Approved Target State
- **Last Reviewed**: 2026-04-29
- **Related docs**: `04_Load_Calculations.md`, `06_Architecture_Audit.md`, `07_Incident_Runbook.md`

## 1. Принципы

- **Security by default**: каждый внешний вызов проверяем, каждую сессию валидируем.
- **Read-heavy optimization**: комментарии и media проектируются под быстрые чтения.
- **Loose coupling**: доменные сервисы связаны контрактами, а не внутренними деталями.
- **Observable system**: все критичные пути имеют метрики, алерты, и error budget.
- **Graceful degradation**: деградация управляемая, без каскадных падений.

## 2. C4 — Context (уровень системы)

Внешние участники:

- Web/Mobile клиенты.
- SSO provider (центральная auth зона).
- CDN/Edge (кеш media).

Архитектурные зоны и проекты:

- Comments Project (треды, ветки, фильтры).
- Media Project (объекты, деривативы изображений).
- SSO Project (redirect/callback/session/refresh).
- Data Platform (DB, Redis, object storage, observability stack).
- Окружающие системы: CDN/Edge, SSO provider, клиенты.

## 3. C4 — Container (уровень контейнеров)

## 3.1 API/Web Container

Отвечает за HTTP вход, маршрутизацию, middleware policy и оркестрацию use-case.

Требования:

- stateless app-инстансы,
- session store вне памяти процесса (Redis),
- таймауты на внешние вызовы (SSO/media).

## 3.2 Comments Container

Контуры:

- write-path (транзакции, блокировки, генерация `path`/`number`),
- read-path (плоская выборка + сборка дерева),
- moderation/access policy.

Хранилище:

- relational DB, индексы по `post_id`, `path`, `replyto_id`.

## 3.3 Media Container

Контуры:

- signature verification (AWS4-like),
- object read/write/delete,
- image derivative generation.

Хранилище:

- object/files storage + CDN front.

## 3.4 SSO Container

Контуры:

- login redirect flow,
- callback token validation,
- session JWT lifecycle (check + refresh + invalidation).

Хранилище:

- Redis (session + blacklist/cache).

## 4. Ключевые сквозные потоки

## 4.1 User Login

1. Клиент -> `/login`.
2. Redirect на SSO login.
3. Callback с token.
4. Валидация token claims и подписи.
5. Session bootstrap в Redis.

## 4.2 Read Comments

1. API получает пост и режим фильтра.
2. Read strategy собирает модель дерева.
3. Ответ с единым контрактом `children`.
4. Hot threads кешируются.

## 4.3 Media Download

1. Запрос через CDN/edge.
2. При miss — backend media проверяет подпись.
3. Отдает оригинал или webp-дериватив.
4. Edge кеширует результат по policy.

## 5. Нефункциональные требования (NFR)

- **Availability**: 99.9%+ для login/comments/media read.
- **Latency**:
  - comments read p95 < 250ms,
  - media read p95 < 400ms (backend miss),
  - refresh p95 < 250ms.
- **Security**:
  - проверка `iss/aud/exp/nbf`,
  - rotation ключей и секретов,
  - replay protection для signed media запросов.
- **Scalability**:
  - горизонтальный app tier,
  - независимое масштабирование media CPU и comments DB.

## 6. Отказоустойчивость и anti-fragility

- Circuit breaker на refresh endpoint SSO.
- Retry policy только для идемпотентных операций.
- Backpressure на тяжелые медиа-запросы.
- Fallback-поведение:
  - при проблемах refresh -> контролируемый logout,
  - при проблемах media optimization -> возврат оригинала.

## 7. Управление данными

- **Comments**: ACID-критичный write-path, read оптимизирован.
- **Media**: immutable-friendly подход для объектов, деривативы детерминированы.
- **Sessions**: централизованный TTL и ревокация токенов.
- **Retention**: политики хранения логов и деривативов по средам.

## 8. Наблюдаемость и SRE-практики

Минимальный мониторинг:

- RED-метрики по HTTP endpoint-ам.
- Saturation по CPU/IO/DB connections/Redis ops.
- Бизнес-метрики:
  - login success rate,
  - refresh failure rate,
  - comments rendering latency.

Алертинг:

- по SLO burn-rate (а не только по «красным» порогам),
- раздельно для read-path и write-path.

## 9. Архитектурные решения (ADR-кандидаты)

- ADR-001: Materialized Path для комментариев.
- ADR-002: AWS4-like подпись для media API.
- ADR-003: JWT session через SSO callback + refresh.
- ADR-004: Redis как единый session/blacklist/cache слой.
- ADR-005: CDN-first стратегия для media read.

Принятые документы см. в `adr/`.

## 10. Roadmap (поэтапно)

## Фаза 1 — Stabilize (1-2 спринта)

- Единый контракт claims для SSO.
- Полная проверка JWT подписи/issuer/audience.
- Метрики p95/p99 + error budget дашборды.

## Фаза 2 — Scale (2-4 спринта)

- Кеш hot threads комментариев.
- CDN policy + cache keys для media.
- Jitter для expiry токенов против refresh storm.

## Фаза 3 — Harden (4+ спринтов)

- Выделенный media processing контур.
- Rate limiting и adaptive throttling.
- Регулярные game-day сценарии отказов (SSO down, storage slow, Redis failover).

## 11. Definition of Done для архитектуры

Архитектура считается «сделанной», когда:

- есть подтвержденный SLO baseline,
- емкость подтверждена нагрузочным тестом >= 1.5x текущего пика,
- каждый критичный поток имеет fallback-сценарий,
- ADR по ключевым решениям зафиксированы и приняты командой.

## 12. Деградационные режимы (обязательные)

- **Comments degraded**: выдача только корней + lazy children по запросу.
- **Media degraded**: отключение on-demand resize, отдача оригинала и async pre-generation.
- **SSO degraded**: ограниченный refresh режим с backoff, принудительный logout только после повторных неуспехов.

## 13. Архитектурные anti-goals

- Не допускается синхронная связанность доменов через внутренние детали.
- Не допускается хранение session-state в памяти процесса приложения.
- Не допускается отключение валидации подписи JWT в любых средах, кроме изолированного локального dev.
