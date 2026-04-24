# Архитектура SSO

## Metadata

- **Owner**: Identity Platform Team
- **Status**: Approved
- **Last Reviewed**: 2026-04-29
- **Related ADR**: `ADR-003-jwt-session-via-sso-callback-refresh.md`, `ADR-004-redis-session-blacklist-cache.md`

## 1. Роль подсистемы

SSO обеспечивает единый вход между сервисами:

- редирект пользователя на центральный login,
- получение callback-токена,
- хранение сессионного JWT,
- контроль валидности и refresh.

## 2. Компоненты

- **Redirect Service**: строит URL логина SSO с callback.
- **Login Callback Controller**: принимает token и инициализирует локальную сессию.
- **Custom Guard (`SSOGuard`)**: извлекает пользователя из JWT в сессии.
- **Token Validity Middleware**: проверяет expiry/blacklist и refresh.

## 3. Поток аутентификации

1. Клиент идет на `/login`.
2. Сервис редиректит на `SSO_HOST/login?callback=...`.
3. SSO возвращает callback с `token`.
4. Локальный сервис:
   - валидирует token,
   - берет expiration claim,
   - сохраняет в сессию.
5. Дальше защищенные роуты работают через `auth:jwt` + middleware валидации токена.

## 4. Поток валидации токена на каждом запросе

1. Достать `auth-token` из сессии.
2. Проверить blacklist/инвалидацию.
3. Проверить срок действия.
4. Если истек:
   - вызвать refresh endpoint SSO,
   - обновить токен в сессии,
   - продолжить запрос.
5. Если refresh неуспешен: logout и отказ доступа.

## 5. Архитектурные требования безопасности

- Обязательная валидация подписи JWT (не только payload decode).
- Проверка `iss`, `aud`, `exp`, `nbf` по единому контракту.
- Общая схема claim-ов во всех сервисах (`exp` как стандарт).
- Защита callback от CSRF/replay (`state`, short TTL).
- Таймауты и circuit-breaker на refresh-запросы.

Дополнительно:

- Ротация signing keys с overlap-периодом и поддержкой `kid`.
- Защита от token replay в callback через одноразовый `state` и хранение nonce.

## 6. Масштабирование SSO-зоны

- Gateway и приложение stateless, сессия в распределенном backend (Redis).
- Refresh endpoint должен выдерживать bursts при массовом истечении токенов.
- Нужен jitter для TTL токенов, чтобы избежать synchronized refresh storm.

## 7. Наблюдаемость

Ключевые метрики:

- login redirect success rate,
- callback validation failure rate,
- refresh success/fail ratio,
- p95 latency refresh endpoint,
- forced logout rate.

## 8. Trade-offs и ограничения

- Централизация auth упрощает единые политики безопасности.
- Цена: SSO становится критичным внешним dependency для login и refresh.
- Redis как session-store снижает sticky-session зависимость, но добавляет single critical layer.

## 9. Capacity guardrails

- Не допускать synchronized token expiry: jitter TTL `+-10-15%`.
- При `refresh_failure_rate` > 5% в 5 минут включать защитный режим:
  - увеличивать backoff refresh,
  - снижать параллелизм refresh запросов,
  - переключать login flow на degraded banner.
