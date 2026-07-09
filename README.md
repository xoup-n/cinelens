# CineLens — рекомендаційна система фільмів

Веб-застосунок на **PHP + JS + SQL**, що реалізує колаборативну фільтрацію
(item-based collaborative filtering) для персональних рекомендацій фільмів —
той самий клас алгоритмів, що лежить в основі рекомендацій Netflix, YouTube,
Amazon.

## Наукова складова

Ядро проєкту — `src/RecommendationEngine.php`, яке будує матрицю оцінок
`users × movies` і обчислює **схожість між фільмами** двома методами:

1. **Косинусна схожість (cosine similarity)**

   ```
   sim(a, b) = (Σ rᵤₐ·rᵤᵦ) / (√Σ rᵤₐ² · √Σ rᵤᵦ²)
   ```

   де сума береться по всіх користувачах `u`, які оцінили обидва фільми
   `a` і `b`.

2. **Кореляція Пірсона (Pearson correlation)** — та ж ідея, але з
   центруванням оцінок відносно середнього значення кожного фільму, що
   компенсує "суворих" і "добрих" глядачів:

   ```
   sim(a, b) = Σ(rᵤₐ - r̄ₐ)(rᵤᵦ - r̄ᵦ) / (√Σ(rᵤₐ-r̄ₐ)² · √Σ(rᵤᵦ-r̄ᵦ)²)
   ```

Прогноз оцінки користувача `u` для непереглянутого фільму `i`:

```
pred(u, i) = Σ sim(i, j)·rᵤⱼ / Σ |sim(i, j)|   (по всіх j, що u вже оцінив)
```

Топ-N фільмів з найвищим прогнозом — і є рекомендації. Матриця схожості
кешується в таблиці `item_similarity` і перераховується скриптом
`scripts/recompute_similarity.php` (окремо, а не на кожен запит) — це
свідоме архітектурне рішення: обчислення схожості для всіх пар фільмів має
складність O(n²·m) і його недоцільно робити синхронно під час завантаження
сторінки.

Користувач на сторінці рекомендацій може сам перемикати алгоритм
(cosine / pearson) і бачити, як міняється видача — це і є демонстрація
"цікавої проблеми", а не просто список з БД.

## Технології

- **Backend:** PHP 8 (PDO, сесії, без важких фреймворків — чистий, зрозумілий код)
- **Frontend:** vanilla JS (fetch API, без збірки)
- **DB:** MySQL / MariaDB

## Структура проєкту

```
cinelens/
├── config/database.php          # підключення до БД (PDO)
├── src/
│   ├── Auth.php                 # реєстрація/логін/сесії
│   ├── Movie.php                # робота з каталогом фільмів
│   ├── Rating.php                # оцінки користувачів
│   └── RecommendationEngine.php # алгоритм колаборативної фільтрації
├── public/                      # document root
│   ├── index.php, login.php, register.php, logout.php
│   ├── catalog.php, movie.php
│   ├── rate.php                 # AJAX-ендпоінт
│   ├── recommendations.php
│   ├── admin.php
│   └── assets/{css,js}
├── sql/schema.sql, seed.sql
└── scripts/
    ├── create_admin.php         # створення адміна (хешує пароль)
    └── recompute_similarity.php # перерахунок матриці схожості
```

## Встановлення

1. Створити БД та імпортувати схему:
   ```bash
   mysql -u root -p < sql/schema.sql
   mysql -u root -p cinelens < sql/seed.sql
   ```
2. Скопіювати `config/database.php.example` → `config/database.php` і
   вписати свої дані підключення (файл з реальними даними лежить в
   `.gitignore` і не потрапляє в репозиторій).
3. Створити адміністратора:
   ```bash
   php scripts/create_admin.php admin admin@cinelens.local admin123
   ```
4. Підняти вбудований сервер PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Відкрити `http://localhost:8000`, зареєструвати кількох користувачів,
   поставити оцінки декільком фільмам, потім виконати:
   ```bash
   php scripts/recompute_similarity.php
   ```
   і зайти на `/recommendations.php` — з'являться персональні рекомендації.

## Git-workflow

Розробка велась по фічах, кожна — в окремій гілці, після чого мержилась
у `main` через `--no-ff` (щоб історія фіч була видна окремо):

- `feature/database-schema`
- `feature/auth`
- `feature/catalog`
- `feature/rating`
- `feature/recommendation-engine`
- `feature/admin-panel`

Подивитись графік гілок:

```bash
git log --oneline --graph --all
```
