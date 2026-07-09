<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';

Auth::start();
$currentUser = Auth::currentUser();

$pageTitle = 'Головна';
require __DIR__ . '/partials/header.php';
?>
<div class="container" style="padding-top:56px;">
    <div style="max-width:640px;">
        <h1 style="font-size:2.6rem;">Фільми, підібрані під ваш смак —<br>не під усіх одразу.</h1>
        <p style="color:var(--fg-dim); font-size:1.05rem;">
            CineLens аналізує, як схожі глядачі оцінювали фільми, і рахує
            персональні прогнози через колаборативну фільтрацію —
            той самий принцип, що стоїть за рекомендаціями великих
            стрімінгових сервісів.
        </p>
        <div style="display:flex; gap:12px; margin-top:24px;">
            <?php if ($currentUser): ?>
                <a href="/recommendations.php" class="btn btn-primary">Мої рекомендації</a>
                <a href="/catalog.php" class="btn btn-outline">Каталог фільмів</a>
            <?php else: ?>
                <a href="/register.php" class="btn btn-primary">Почати</a>
                <a href="/catalog.php" class="btn btn-outline">Переглянути каталог</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="sprocket-rule" style="margin-top:48px;"></div>
<div class="container">
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-bottom:48px;">
        <div>
            <h3>1. Оцінюйте</h3>
            <p style="color:var(--fg-dim); font-size:0.92rem;">Ставте оцінки 1–5 фільмам у каталозі — чим більше, тим точніші прогнози.</p>
        </div>
        <div>
            <h3>2. Алгоритм рахує</h3>
            <p style="color:var(--fg-dim); font-size:0.92rem;">Item-based collaborative filtering шукає фільми, схожі за патерном оцінок, а не за жанром у назві.</p>
        </div>
        <div>
            <h3>3. Отримуйте рекомендації</h3>
            <p style="color:var(--fg-dim); font-size:0.92rem;">Порівнюйте, як по-різному радять косинусна схожість і кореляція Пірсона.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
