<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/RecommendationEngine.php';

Auth::start();
$currentUser = Auth::requireLogin();

$algorithm = ($_GET['algorithm'] ?? 'cosine') === 'pearson' ? 'pearson' : 'cosine';
$recommendations = RecommendationEngine::recommendForUser((int) $currentUser['id'], $algorithm, 10);

$pageTitle = 'Рекомендації';
require __DIR__ . '/partials/header.php';
?>
<div class="container" style="padding-top:32px;">
    <h1>Ваші рекомендації</h1>
    <p style="color:var(--fg-dim); max-width:640px;">
        Прогноз рахується через item-based collaborative filtering: система
        шукає фільми, схожі за патерном оцінок з тими, що вам уже
        сподобались, — не за жанром, а за тим, кому ще вони сподобались.
    </p>

    <div class="algo-toggle" role="group" aria-label="Вибір алгоритму">
        <a href="/recommendations.php?algorithm=cosine" class="btn <?= $algorithm === 'cosine' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            Косинусна схожість
        </a>
        <a href="/recommendations.php?algorithm=pearson" class="btn <?= $algorithm === 'pearson' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            Кореляція Пірсона
        </a>
    </div>

    <?php if (empty($recommendations)): ?>
        <p style="color:var(--fg-dim); margin-top:24px;">
            Поки недостатньо даних для рекомендацій. Оцініть кілька фільмів у
            <a href="/catalog.php">каталозі</a> — і повертайтесь сюди.
        </p>
    <?php else: ?>
        <?php if (($recommendations[0]['is_fallback'] ?? false)): ?>
            <p style="color:var(--fg-dim); font-size:0.9rem;">
                Персональних збігів поки замало — показуємо найкраще оцінені фільми загалом.
            </p>
        <?php endif; ?>
        <div class="movie-grid">
            <?php foreach ($recommendations as $movie): ?>
                <a href="/movie.php?id=<?= (int) $movie['id'] ?>" class="movie-card">
                    <div class="movie-card-genre"><?= htmlspecialchars($movie['genre']) ?></div>
                    <h3><?= htmlspecialchars($movie['title']) ?></h3>
                    <div class="movie-card-meta">
                        <?= (int) $movie['release_year'] ?>
                        &middot; прогноз <?= htmlspecialchars((string) $movie['predicted']) ?>/5
                        <?php if (!($movie['is_fallback'] ?? false)): ?>
                            <span style="color:var(--fg-dim)">(<?= (int) $movie['neighbor_count'] ?> схожих фільмів)</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
