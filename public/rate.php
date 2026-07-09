<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Rating.php';
require_once __DIR__ . '/../src/Movie.php';

header('Content-Type: application/json; charset=utf-8');

Auth::start();
$currentUser = Auth::currentUser();

if ($currentUser === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Потрібно увійти в акаунт.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не підтримується.']);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];

$movieId = (int) ($input['movie_id'] ?? 0);
$rating  = (int) ($input['rating'] ?? 0);

if ($movieId <= 0 || Movie::find($movieId) === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Фільм не знайдено.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Оцінка має бути від 1 до 5.']);
    exit;
}

Rating::upsert((int) $currentUser['id'], $movieId, $rating);

$movie = Movie::find($movieId);

echo json_encode([
    'ok'          => true,
    'avg_rating'  => $movie['avg_rating'],
    'rating_count'=> (int) $movie['rating_count'],
]);
