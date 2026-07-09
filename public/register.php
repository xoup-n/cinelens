<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';

Auth::start();
$currentUser = Auth::currentUser();
if ($currentUser !== null) {
    header('Location: /catalog.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = Auth::register(
        (string) ($_POST['username'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );

    if ($result['ok']) {
        header('Location: /catalog.php');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Реєстрація';
require __DIR__ . '/partials/header.php';
?>
<div class="container">
    <div class="form-card">
        <h1>Реєстрація</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="field">
                <label for="username">Ім'я користувача</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Створити акаунт</button>
        </form>
        <div class="form-footer">
            Вже маєте акаунт? <a href="/login.php">Увійти</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
