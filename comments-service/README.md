# Comments Service

Это демонстрационный Laravel-проект для высоконагруженного сервиса комментирования.

## Цели

- показать зрелую слоистую архитектуру
- реализовать materialized path для дерева комментариев
- моделировать высокую нагрузку и read-heavy паттерны
- сделать проект удобным для коммерческого демо

## Структура

- `app/Http/Controllers` — контроллеры API
- `app/Services` — бизнес-логика и use case
- `app/Repositories` — абстракция доступа к данным
- `app/Domain/Comment` — доменный слой и сборка дерева
- `app/Models` — Eloquent-модели
- `database/migrations` — схема таблицы
- `routes/api.php` — маршруты API
- `tests` — feature и unit тесты

## Как использовать

1. Установите PHP, Composer и Laravel.
2. Запустите `composer install` в `comments-service`.
3. Скопируйте `.env.example` в `.env`.
4. Настройте БД и выполните `php artisan migrate`.
5. Запустите `php artisan serve` или Docker Compose.
