# Comments Service

Это демонстрационный Laravel-проект для высоконагруженного сервиса комментирования.

## Цели

- показать зрелую слоистую архитектуру
- реализовать materialized path для дерева комментариев
- моделировать высокую нагрузку и read-heavy паттерны
- сделать проект удобным для коммерческого демо

## Архитектура

- **Controller/API**: HTTP, валидация, ресурсы
- **Service / Use Case**: бизнес-логика, кеширование, транзакции
- **Repository**: абстракция данных, retry-логика
- **Domain**: сборка дерева, бизнес-правила
- **Model**: Eloquent, миграции

## Ключевые фичи

- Materialized path для дерева комментариев
- Кеширование hot threads (5 мин TTL)
- Пагинация корней с lazy children
- Retry-логика при unique constraint violations
- API Resources для структурированных ответов
- Тесты: unit + feature с фабриками

## API

- `GET /api/posts/{id}/comments?page=1&per_page=20&expand=false`
- `POST /api/posts/{id}/comments` — создать комментарий
- `GET /api/comments/{id}` — получить комментарий
- `PATCH /api/comments/{id}` — обновить
- `DELETE /api/comments/{id}` — удалить

## Запуск

1. `composer install`
2. `cp .env.example .env`
3. `php artisan key:generate`
4. `php artisan migrate`
5. `php artisan serve`

Для Docker: `docker-compose up`

## Тесты

`php artisan test`

## Производительность

- Read p95 < 250ms для дерева
- Write с транзакциями и lock
- Кеш invalidation при изменениях
