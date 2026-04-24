# Comments Service Architecture

## Цель

Создать высоконагруженный комментарийный сервис как демонстрационный продукт для enterprise-систем.

## Ключевая идея

Используем materialized path для описания и чтения дерева комментариев. Это позволяет:

- быстро собирать дерево одним запросом
- масштабировать чтение
- обеспечивать строгое управление глубиной

## Слои

1. Controller/API
2. Service / Use Case
3. Repository
4. Domain
5. Infrastructure / Model

## Нефункциональные требования

- read-heavy, p95 чтения дерева < 250ms
- write path с транзакцией и retry при конфликте
- защита от больших payload
- возможность деградации: root-only + lazy children
