<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class Rating
{
    public static function upsert(int $userId, int $movieId, int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO ratings (user_id, movie_id, rating)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating)'
        );
        $stmt->execute([$userId, $movieId, $rating]);
    }

    public static function forUser(int $userId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT movie_id, rating FROM ratings WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['movie_id']] = (int) $row['rating'];
        }
        return $map;
    }

    public static function userRatingForMovie(int $userId, int $movieId): ?int
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT rating FROM ratings WHERE user_id = ? AND movie_id = ?');
        $stmt->execute([$userId, $movieId]);
        $row = $stmt->fetch();
        return $row !== false ? (int) $row['rating'] : null;
    }

    /**
     * Full ratings matrix as [userId => [movieId => rating]].
     * Used by RecommendationEngine to compute item similarity.
     */
    public static function matrix(): array
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT user_id, movie_id, rating FROM ratings');

        $matrix = [];
        foreach ($stmt->fetchAll() as $row) {
            $matrix[(int) $row['user_id']][(int) $row['movie_id']] = (int) $row['rating'];
        }
        return $matrix;
    }
}
