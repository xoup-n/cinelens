<?php
/** @var array|null $currentUser expected to be set by the including page */
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — CineLens' : 'CineLens' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="nav">
    <div class="container">
        <a href="/index.php" class="nav-brand">Cine<span>Lens</span></a>
        <div class="nav-links">
            <a href="/catalog.php" class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>">Каталог</a>
            <?php if ($currentUser): ?>
                <a href="/recommendations.php" class="<?= $currentPage === 'recommendations.php' ? 'active' : '' ?>">Рекомендації</a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="/admin.php" class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>">Адмін-панель</a>
                <?php endif; ?>
                <span class="nav-user"><?= htmlspecialchars($currentUser['username']) ?></span>
                <a href="/logout.php" class="btn btn-outline btn-sm">Вийти</a>
            <?php else: ?>
                <a href="/login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Увійти</a>
                <a href="/register.php" class="btn btn-primary btn-sm">Реєстрація</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main>
