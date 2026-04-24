# Расчет нагрузок и емкости

Документ дает рабочие модели capacity planning для трёх независимых проектов: комментариев, media и SSO.

## Metadata

- **Owner**: SRE + Platform Architecture
- **Status**: Working baseline
- **Last Reviewed**: 2026-04-29
- **Refresh trigger**: изменение DAU, SLA, cache-hit, размеров media или auth policy

## 1. Базовые входные параметры

Для расчетов используем:

- `DAU` — активные пользователи в сутки.
- `RPS_avg` — средняя нагрузка запросов/сек.
- `RPS_peak` — пиковая нагрузка.
- `Read/Write ratio` — доля чтений к записям.
- `Payload_avg` — средний размер ответа.

Формулы:

- `RPS_avg = Requests_per_day / 86400`
- `RPS_peak ~= RPS_avg * PeakFactor`
- `Net_out = RPS * Payload_avg`

Рекомендуемый `PeakFactor`: `5-12` (зависит от характера трафика).

---

## 2. Комментарии: модель нагрузки

Пусть:

- 80k DAU,
- 24 запроса чтения комментариев на пользователя/сутки,
- 1.2 записи комментариев на пользователя/сутки.

Тогда:

- Read/day = `80,000 * 24 = 1,920,000`
- Write/day = `80,000 * 1.2 = 96,000`
- `RPS_avg_read = 1,920,000 / 86400 ~= 22.2`
- `RPS_avg_write = 96,000 / 86400 ~= 1.1`

При `PeakFactor = 8`:

- `RPS_peak_read ~= 178`
- `RPS_peak_write ~= 9`

Вывод:

- Главная нагрузка на чтение дерева, не на запись.
- Кэширование read-модели дает максимальный эффект.

---

## 3. Media: модель нагрузки

Пусть:

- 35% сессий запрашивают media,
- в среднем 14 media GET на сессию,
- средний объект 180 KB,
- пиковая доля webp-generation: 6% от GET.

Оценка при 80k DAU (1 сессия/день):

- GET/day = `80,000 * 0.35 * 14 = 392,000`
- `RPS_avg_get ~= 4.5`
- при `PeakFactor = 10`: `RPS_peak_get ~= 45`

Сетевой выход на пике:

- `45 * 180 KB ~= 8.1 MB/s` (без CDN)

CPU-нагрузка оптимизации:

- если 6% новых деривативов: `~2.7 RPS` resize на пике.
- для burst-паттернов нужен отдельный worker-pool или pre-generation.

---

## 4. SSO: модель нагрузки

Пусть:

- login/day = 0.6 * DAU = 48,000,
- доля refresh/day = 2.5 на сессию => 200,000 refresh/day.

Тогда:

- `RPS_avg_login ~= 0.56`
- `RPS_avg_refresh ~= 2.31`

На пике (`PeakFactor = 12`):

- `RPS_peak_login ~= 7`
- `RPS_peak_refresh ~= 28`

Ключевой риск:

- synchronized token expiry может кратно поднять refresh RPS.
- mitigation: jitter TTL +-10-15%.

---

## 5. Емкость по слоям

## DB (comments)

- при `RPS_peak_write ~ 9` достаточно 1 primary среднего класса,
- обязательно: индексы + транзакционные блокировки,
- read replicas полезны при тяжелой аналитике/выгрузках.

## Redis (sessions/blacklist/cache)

- целевой запас по throughput: минимум `3x` от расчетного пика.
- при `~30-50k ops/s` обычно хватает 1-2 узлов среднего уровня, но лучше стартовать с managed failover.

## App instances

- стартовая оценка: `N = ceil(RPS_peak_total / RPS_per_instance_safe)`.
- если safe-per-instance ~ 40 rps и общий пик ~ 230 rps, нужно `ceil(230/40)=6` инстансов + 1 резерв.

## CDN / Edge

- media трафик желательно вынести в CDN, чтобы снять 60-95% backend GET.
- это резко сокращает и сеть, и I/O backend-сервиса.

---

## 6. SLO/SLA ориентиры

- Comments read p95: `< 250ms`
- Comment create p95: `< 350ms`
- Media GET p95 (cache miss backend): `< 400ms`
- SSO callback p95: `< 300ms`
- Refresh token p95: `< 250ms`
- Availability ключевых путей: `99.9%`+

---

## 7. План масштабирования по этапам

1. **Stage A (текущий/близкий рост)**:
   - метрики + алерты + профилирование узких мест.
2. **Stage B (рост 2-3x)**:
   - кэш тредов комментариев, CDN для media, jitter токенов.
3. **Stage C (рост 5x+)**:
   - выделенный media-processing контур,
   - read-model/materialized views для комментариев,
   - жесткий rate limiting и backpressure.

---

## 8. Что уточнить для финального capacity-файла

Для точного расчета нужны реальные метрики:

- DAU/MAU и сезонность,
- реальный пиковый час,
- распределение размеров media,
- доля cache hit/miss,
- текущие p95/p99 и error budgets.

---

## 9. Валидация модели в production (обязательно)

Для каждого релиза с архитектурными изменениями:

1. Сравнить `model_peak_rps` и `observed_peak_rps` по comments/media/sso.
2. Сравнить модельные p95 и фактические p95/p99.
3. Зафиксировать delta и принять решение:
   - `|delta| <= 20%` -> модель ок,
   - `20% < |delta| <= 40%` -> пересчитать коэффициенты,
   - `|delta| > 40%` -> пересобрать модель с новыми входными.

## 10. Exit criteria для capacity readiness

- Нагрузочный тест >= `1.5x` текущего пика проходит без нарушения SLO.
- Запас по Redis throughput минимум `3x` от наблюдаемого пика.
- При отказе одного app-instance p95 не выходит за SLO более 10 минут.
- Для media подтвержден CDN hit ratio не ниже целевого на ключевых маршрутах.
