<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Movie.php';
require_once __DIR__ . '/../src/RecommendationEngine.php';

Auth::start();
$currentUser = Auth::requireAdmin();

$notice = null;
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $title       = trim((string) ($_POST['title'] ?? ''));
        $genre       = trim((string) ($_POST['genre'] ?? ''));
        $year        = (int) ($_POST['release_year'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $posterUrl   = trim((string) ($_POST['poster_url'] ?? '')) ?: null;

        if ($title === '' || $genre === '' || $year < 1900 || $year > 2100) {
            $error = 'Перевірте поля: назва, жанр і рік обов\'язкові, рік має бути коректним.';
        } elseif ($action === 'create') {
            Movie::create($title, $genre, $year, $description, $posterUrl);
            $notice = 'Фільм додано.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            Movie::update($id, $title, $genre, $year, $description, $posterUrl);
            $notice = 'Фільм оновлено.';
        }
    } elseif ($action === 'delete') {
        Movie::delete((int) ($_POST['id'] ?? 0));
        $notice = 'Фільм видалено.';
    } elseif ($action === 'recompute') {
        $pairs = RecommendationEngine::recomputeSimilarityMatrix();
        $notice = "Матрицю схожості перераховано: {$pairs} пар фільмів.";
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? Movie::find($editId) : null;

$movies = Movie::all();

$pageTitle = 'Адмін-панель';
require __DIR__ . '/partials/header.php';
?>
<div class="container" style="padding-top:32px;">
    <h1>Адмін-панель</h1>

    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 1.4fr; gap:32px; align-items:start;">
        <div class="form-card" style="margin:0;">
            <h3><?= $editing ? 'Редагувати фільм' : 'Додати фільм' ?></h3>
            <form method="post">
                <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <?php endif; ?>
                <div class="field">
                    <label for="title">Назва</label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="genre">Жанр</label>
                    <input type="text" id="genre" name="genre" required value="<?= htmlspecialchars($editing['genre'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="release_year">Рік</label>
                    <input type="number" id="release_year" name="release_year" required min="1900" max="2100" value="<?= htmlspecialchars((string) ($editing['release_year'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="description">Опис</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
                </div>
                <div class="field">
                    <label for="poster_url">URL постера (необов'язково)</label>
                    <input type="url" id="poster_url" name="poster_url" value="<?= htmlspecialchars($editing['poster_url'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <?= $editing ? 'Зберегти зміни' : 'Додати фільм' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="/admin.php" class="btn btn-outline" style="width:100%; margin-top:10px; text-align:center;">Скасувати</a>
                <?php endif; ?>
            </form>

            <hr style="border-color:var(--border); margin:24px 0;">

            <h3 style="font-size:1rem;">Матриця схожості</h3>
            <p style="color:var(--fg-dim); font-size:0.85rem;">
                Перераховується офлайн, а не на кожен запит (O(n²) за кількістю фільмів).
            </p>
            <form method="post">
                <input type="hidden" name="action" value="recompute">
                <button type="submit" class="btn btn-outline" style="width:100%;">Перерахувати зараз</button>
            </form>
        </div>

        <div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Назва</th>
                        <th>Жанр</th>
                        <th>Рік</th>
                        <th>Рейтинг</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td><?= htmlspecialchars($movie['title']) ?></td>
                            <td><?= htmlspecialchars($movie['genre']) ?></td>
                            <td><?= (int) $movie['release_year'] ?></td>
                            <td><?= $movie['rating_count'] > 0 ? htmlspecialchars((string) $movie['avg_rating']) . ' ★' : '—' ?></td>
                            <td style="white-space:nowrap;">
                                <a href="/admin.php?edit=<?= (int) $movie['id'] ?>" class="btn btn-outline btn-sm">Редагувати</a>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Видалити цей фільм?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $movie['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Видалити</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
