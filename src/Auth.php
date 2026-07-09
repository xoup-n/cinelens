<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Handles registration, login, logout and session state.
 */
final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public static function register(string $username, string $email, string $password): array
    {
        $username = trim($username);
        $email    = trim($email);

        if ($username === '' || $email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Всі поля обов\'язкові.'];
        }
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['ok' => false, 'error' => 'Ім\'я користувача має бути від 3 до 50 символів.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Некоректний email.'];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Пароль має містити щонайменше 6 символів.'];
        }

        $db = Database::connection();

        $check = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $check->execute([$username, $email]);
        if ($check->fetch() !== false) {
            return ['ok' => false, 'error' => 'Користувач з таким іменем або email вже існує.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $insert = $db->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, "user")'
        );
        $insert->execute([$username, $email, $hash]);

        self::loginUserById((int) $db->lastInsertId());

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public static function login(string $usernameOrEmail, string $password): array
    {
        $usernameOrEmail = trim($usernameOrEmail);

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Невірний логін або пароль.'];
        }

        self::loginUserById((int) $user['id']);

        return ['ok' => true];
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain']);
        }
        session_destroy();
    }

    public static function currentUser(): ?array
    {
        self::start();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        $cached = $user !== false ? $user : null;
        return $cached;
    }

    public static function requireLogin(): array
    {
        $user = self::currentUser();
        if ($user === null) {
            header('Location: /login.php');
            exit;
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo 'Доступ заборонено: потрібні права адміністратора.';
            exit;
        }
        return $user;
    }

    private static function loginUserById(int $id): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
    }
}
