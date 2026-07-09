<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class Movie
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(?string $search = null, ?string $genre = null): array
    {
        $db = Database::connection();

        $sql = 'SELECT m.*,
                       ROUND(AVG(r.rating), 2) AS avg_rating,
                       COUNT(r.id) AS rating_count
                FROM movies m
                LEFT JOIN ratings r ON r.movie_id = m.id
                WHERE 1=1';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (m.title LIKE ? OR m.description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($genre !== null && $genre !== '') {
            $sql .= ' AND m.genre = ?';
            $params[] = $genre;
        }

        $sql .= ' GROUP BY m.id ORDER BY m.title ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT m.*,
                    ROUND(AVG(r.rating), 2) AS avg_rating,
                    COUNT(r.id) AS rating_count
             FROM movies m
             LEFT JOIN ratings r ON r.movie_id = m.id
             WHERE m.id = ?
             GROUP BY m.id'
        );
        $stmt->execute([$id]);
        $movie = $stmt->fetch();
        return $movie !== false ? $movie : null;
    }

    /**
     * @return array<int, string>
     */
    public static function genres(): array
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT DISTINCT genre FROM movies ORDER BY genre ASC');
        return array_column($stmt->fetchAll(), 'genre');
    }

    public static function create(string $title, string $genre, int $year, string $description, ?string $posterUrl): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO movies (title, genre, release_year, description, poster_url) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$title, $genre, $year, $description, $posterUrl]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $title, string $genre, int $year, string $description, ?string $posterUrl): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE movies SET title = ?, genre = ?, release_year = ?, description = ?, poster_url = ? WHERE id = ?'
        );
        $stmt->execute([$title, $genre, $year, $description, $posterUrl, $id]);
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM movies WHERE id = ?');
        $stmt->execute([$id]);
    }
}
