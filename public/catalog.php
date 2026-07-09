<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Movie.php';

Auth::start();
$currentUser = Auth::currentUser();

$search = trim((string) ($_GET['q'] ?? ''));
$genre  = trim((string) ($_GET['genre'] ?? ''));

$movies = Movie::all($search !== '' ? $search : null, $genre !== '' ? $genre : null);
$genres = Movie::genres();

$pageTitle = 'Каталог';
require __DIR__ . '/partials/header.php';
?>
<div class="container" style="padding-top:32px;">
    <h1>Каталог фільмів</h1>

    <form method="get" style="display:flex; gap:12px; margin-bottom:28px; flex-wrap:wrap;">
        <input type="text" name="q" placeholder="Пошук за назвою або описом…"
               value="<?= htmlspecialchars($search) ?>"
               style="flex:1; min-width:220px; padding:10px 12px; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); color:var(--fg);">
        <select name="genre" style="padding:10px 12px; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); color:var(--fg);">
            <option value="">Усі жанри</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>" <?= $g === $genre ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline">Фільтрувати</button>
    </form>

    <?php if (empty($movies)): ?>
        <p style="color:var(--fg-dim)">Нічого не знайдено. Спробуйте інший запит.</p>
    <?php endif; ?>

    <div class="movie-grid">
        <?php foreach ($movies as $movie): ?>
            <a href="/movie.php?id=<?= (int) $movie['id'] ?>" class="movie-card">
                <div class="movie-card-genre"><?= htmlspecialchars($movie['genre']) ?></div>
                <h3><?= htmlspecialchars($movie['title']) ?></h3>
                <div class="movie-card-meta">
                    <?= (int) $movie['release_year'] ?>
                    <?php if ($movie['rating_count'] > 0): ?>
                        &middot; ★ <?= htmlspecialchars((string) $movie['avg_rating']) ?>
                        <span style="color:var(--fg-dim)">(<?= (int) $movie['rating_count'] ?>)</span>
                    <?php else: ?>
                        &middot; <span style="color:var(--fg-dim)">ще немає оцінок</span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
