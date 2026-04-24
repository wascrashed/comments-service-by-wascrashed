# Архитектура Media Service

## Metadata

- **Owner**: Media Platform Team
- **Status**: Approved
- **Last Reviewed**: 2026-04-29
- **Related ADR**: `ADR-002-aws4-like-signature-media-api.md`, `ADR-005-cdn-first-media-read.md`

## 1. Роль сервиса

Media Service предоставляет S3-подобный слой для:

- upload/download файлов,
- HEAD-проверок метаданных,
- удаления оригиналов и производных,
- on-demand оптимизации изображений.

## 2. Основные компоненты

- **HTTP Routes**: `PUT`, `GET`, `HEAD`, `DELETE`, `LIST`.
- **Signature Middleware**: проверка AWS Signature V4.
- **Storage Layer**: локальный диск (`public`) как backend.
- **Image Optimization Service**: генерация webp по шаблону имени.

### Trade-offs выбранного подхода

- On-demand генерация снижает storage cost на редких размерах.
- Цена подхода: latency spikes и CPU bursts на cache miss.
- Для top-size профилей нужен pre-generation, иначе p95 будет нестабилен.

## 3. Security perimeter

Вход в сервис только через проверку подписи:

- проверка `Authorization`,
- проверка `Credential`/`SignedHeaders`/`Signature`,
- сверка `access_key`, `region`, `service`,
- пересчет canonical request + string-to-sign.

Критично:

- string-to-sign должен использовать `x-amz-date` запроса,
- секреты не должны попадать в логи.

## 4. Данные и хранение

- Валидные buckets ограничены конфигом (`posts`, `users`).
- Путь хранения: `{bucket}/{path}`.
- Производные изображений: `{name}.{WxH}.{jpg|jpeg|png}.webp`.

## 5. Потоки

### Upload (`PUT`)

1. Проверка bucket и подписи.
2. Запись бинарного тела в storage.
3. Ответ с метаданными файла.

### Download (`GET/HEAD`)

1. Проверка bucket и подписи.
2. Поиск оригинала.
3. Для image-pattern — проверка/генерация optimized webp.
4. Возврат файла/метаданных.

### Delete (`DELETE`)

1. Проверка bucket и подписи.
2. Удаление оригинала.
3. Удаление только связанных деривативов (без wildcard-рисков).

## 6. Масштабирование

- Горизонтально stateless (если storage и ключи внешние/общие).
- Оптимизация изображений — CPU-heavy, требует отдельного пула воркеров при росте.
- Горячие объекты желательно отдавать через CDN.

## 7. Риски и архитектурные меры

- **Риск**: replay-запросы по валидной подписи -> **мера**: временное окно + nonce/idempotency-key.
- **Риск**: CPU spikes на resize -> **мера**: async pre-generation top размеров.
- **Риск**: large-object I/O -> **мера**: streaming ответов, лимиты upload.
- **Риск**: случайное массовое удаление -> **мера**: строгий matching производных по regex.

## 8. Operational hardening

- Ограничить `PUT` по размеру и скорости (request body limits + timeout).
- Ввести rate limiting на expensive resize path.
- Настроить circuit-breaker для image optimizer, fallback на оригинал.
- Включить quarantine bucket для подозрительных uploads (по mime/scan policy).

## 9. Capacity guardrails

- Допустимый `resize_queue_depth` до деградации: `<= 500`.
- При превышении порога переводить новые resize в async и отвечать оригиналом.
- Целевой CDN hit ratio: `>= 85%` для top-media путей.
