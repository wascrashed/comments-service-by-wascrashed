# ADR-003: JWT session через SSO callback + refresh

- **Status**: Accepted
- **Date**: 2026-04-29
- **Owner**: Identity Platform Team

## Context

Нужен единый login между сервисами, с локальной сессией и контролируемым жизненным циклом токена.

## Decision

Использовать SSO redirect/callback с сохранением JWT в server-side session и refresh при истечении.

## Consequences

### Positive

- Единая auth-политика для всех сервисов.
- Централизованный контроль claims и logout behavior.

### Negative

- Зависимость от доступности SSO refresh endpoint.
- Необходимость анти-шторм защиты при массовом expiry.

## Rejected alternatives

- Локальный логин в каждом сервисе: повышает операционный риск и дублирует auth-логику.
