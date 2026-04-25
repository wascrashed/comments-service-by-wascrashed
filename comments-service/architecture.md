# Comments Service Architecture

## Цель

Создать высоконагруженный комментарийный сервис как демонстрационный продукт для enterprise-систем.

## Ключевая идея

Используем materialized path для описания и чтения дерева комментариев. Это позволяет:

- быстро собирать дерево одним запросом
- масштабировать чтение
- обеспечивать строгое управление глубиной

## Слои

1. **Controller/API**: HTTP, валидация через FormRequest, API Resources
2. **Service / Use Case**: бизнес-логика, кеширование, транзакции, invalidation
3. **Repository**: абстракция данных, retry-логика при unique violations
4. **Domain**: сборка дерева, пагинация, бизнес-правила
5. **Infrastructure / Model**: Eloquent, миграции, индексы

## Нефункциональные требования

- **read-heavy**: p95 чтения дерева < 250ms
- **write path**: транзакции с lockForUpdate, retry при конфликтах
- **кеширование**: hot threads 5 мин TTL, invalidation при изменениях
- **пагинация**: корни с lazy children для больших деревьев
- **масштабирование**: stateless, готов к horizontal scaling

## Ключевые решения

- **Materialized Path**: `path` как строка (1.2.3), `level` для глубины
- **Sequential Number**: `number` внутри поста для порядка
- **Locking**: `FOR UPDATE` на посте и родителе
- **Caching**: Redis для деревьев, wildcard invalidation
- **Retry**: до 3 попыток при unique constraint violations
- **Pagination**: API с page/per_page для корней

## Риски и mitigation

- **Large trees**: пагинация + shallow children
- **Concurrent writes**: locks + retry
- **Cache staleness**: event-driven invalidation
- **Payload size**: ограничение глубины дерева

## Тестирование

- Unit: TreeBuilder, логика path
- Feature: API workflow, tree building, caching
- Фабрики: CommentFactory для тестовых данных
