# ADR-002: AWS4-like подпись для Media API

- **Status**: Accepted
- **Date**: 2026-04-29
- **Owner**: Media Platform Team

## Context

Нужна криптографически верифицируемая авторизация запросов к media endpoint-ам.

## Decision

Применять AWS Signature V4-like схему (canonical request + string-to-sign + secret key).

## Consequences

### Positive

- Сильная проверка подлинности запроса.
- Понятная интеграция с существующими SDK/паттернами.

### Negative

- Более сложная отладка client-side подписи.
- Нужен строгий контроль clock skew и replay window.

## Rejected alternatives

- Простые bearer токены: хуже контроль replay и целостности запроса.
