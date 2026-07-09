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
    $result = Auth::login(
        (string) ($_POST['login'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );

    if ($result['ok']) {
        header('Location: /catalog.php');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Вхід';
require __DIR__ . '/partials/header.php';
?>
<div class="container">
    <div class="form-card">
        <h1>Увійти</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="field">
                <label for="login">Ім'я користувача або email</label>
                <input type="text" id="login" name="login" required
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Увійти</button>
        </form>
        <div class="form-footer">
            Немає акаунта? <a href="/register.php">Зареєструватися</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
