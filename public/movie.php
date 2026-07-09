<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Movie.php';
require_once __DIR__ . '/../src/Rating.php';

Auth::start();
$currentUser = Auth::currentUser();

$id = (int) ($_GET['id'] ?? 0);
$movie = Movie::find($id);

if ($movie === null) {
    http_response_code(404);
    $pageTitle = 'Фільм не знайдено';
    require __DIR__ . '/partials/header.php';
    echo '<div class="container"><p>Фільм не знайдено.</p></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = $movie['title'];
require __DIR__ . '/partials/header.php';
?>
<div class="container" style="padding-top:32px; max-width:720px;">
    <div class="movie-card-genre"><?= htmlspecialchars($movie['genre']) ?> &middot; <?= (int) $movie['release_year'] ?></div>
    <h1><?= htmlspecialchars($movie['title']) ?></h1>
    <p style="color:var(--fg-dim); font-size:1rem;"><?= nl2br(htmlspecialchars($movie['description'] ?? '')) ?></p>

    <div style="margin:24px 0; display:flex; align-items:center; gap:10px;">
        <?php if ($movie['rating_count'] > 0): ?>
            <span style="font-family:var(--font-display); font-size:1.4rem;">★ <?= htmlspecialchars((string) $movie['avg_rating']) ?></span>
            <span style="color:var(--fg-dim)">на основі <?= (int) $movie['rating_count'] ?> оцінок</span>
        <?php else: ?>
            <span style="color:var(--fg-dim)">Поки що немає оцінок — станьте першим.</span>
        <?php endif; ?>
    </div>

    <?php if ($currentUser === null): ?>
        <p style="color:var(--fg-dim)"><a href="/login.php">Увійдіть</a>, щоб оцінити цей фільм.</p>
    <?php else: ?>
        <?php $myRating = Rating::userRatingForMovie((int) $currentUser['id'], (int) $movie['id']); ?>
        <div id="rating-widget" data-movie-id="<?= (int) $movie['id'] ?>" data-current-rating="<?= (int) ($myRating ?? 0) ?>">
            <div class="star-row">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="star-btn <?= $myRating !== null && $i <= $myRating ? 'filled' : '' ?>" data-value="<?= $i ?>" aria-label="Оцінити <?= $i ?> з 5">★</button>
                <?php endfor; ?>
            </div>
            <p class="rating-status" style="color:var(--fg-dim); font-size:0.85rem; margin-top:6px;">
                <?= $myRating !== null ? 'Ваша оцінка: ' . $myRating . '/5. Натисніть, щоб змінити.' : 'Оцініть цей фільм.' ?>
            </p>
        </div>
        <script src="/assets/js/rating.js" defer></script>
    <?php endif; ?>

    <p style="margin-top:32px;"><a href="/catalog.php">&larr; Назад до каталогу</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
