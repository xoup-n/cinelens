<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Rating.php';
require_once __DIR__ . '/Movie.php';

/**
 * Item-based collaborative filtering.
 *
 * Two similarity metrics are implemented on purpose, so the app can compare
 * them side by side rather than treating "the algorithm" as a black box:
 *
 *  - Cosine similarity: treats each movie's ratings as a vector and measures
 *    the angle between two movies' vectors. Simple, fast, but sensitive to
 *    users who rate everything high or everything low (no centering).
 *
 *  - Pearson correlation: centers each movie's ratings around its own mean
 *    (computed only over the users who rated *both* movies being compared)
 *    before comparing. This cancels out a rater's personal generosity bias,
 *    so two movies liked by the same people but rated on different personal
 *    scales (a "harsh critic" vs a "generous" viewer) still score as similar.
 */
final class RecommendationEngine
{
    private const MIN_CO_RATERS = 2;

    /**
     * Build [movieId => [userId => rating]] — the transpose of Rating::matrix(),
     * indexed by movie first since similarity is computed item-to-item.
     */
    private static function itemVectors(): array
    {
        $byUser = Rating::matrix(); // [userId => [movieId => rating]]
        $byItem = [];
        foreach ($byUser as $userId => $movieRatings) {
            foreach ($movieRatings as $movieId => $rating) {
                $byItem[$movieId][$userId] = $rating;
            }
        }
        return $byItem;
    }

    /**
     * @param array<int,int> $a userId => rating for movie A
     * @param array<int,int> $b userId => rating for movie B
     */
    public static function cosineSimilarity(array $a, array $b): array
    {
        $commonUsers = array_intersect_key($a, $b);
        $coRaters = count($commonUsers);
        if ($coRaters === 0) {
            return [0.0, 0];
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        foreach ($commonUsers as $userId => $_) {
            $dot   += $a[$userId] * $b[$userId];
            $normA += $a[$userId] ** 2;
            $normB += $b[$userId] ** 2;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return [0.0, $coRaters];
        }

        $sim = $dot / (sqrt($normA) * sqrt($normB));
        return [$sim, $coRaters];
    }

    /**
     * @param array<int,int> $a userId => rating for movie A
     * @param array<int,int> $b userId => rating for movie B
     */
    public static function pearsonSimilarity(array $a, array $b): array
    {
        $commonUsers = array_keys(array_intersect_key($a, $b));
        $coRaters = count($commonUsers);
        if ($coRaters === 0) {
            return [0.0, 0];
        }

        $meanA = array_sum(array_intersect_key($a, array_flip($commonUsers))) / $coRaters;
        $meanB = array_sum(array_intersect_key($b, array_flip($commonUsers))) / $coRaters;

        $num = 0.0;
        $denomA = 0.0;
        $denomB = 0.0;

        foreach ($commonUsers as $userId) {
            $da = $a[$userId] - $meanA;
            $db = $b[$userId] - $meanB;
            $num    += $da * $db;
            $denomA += $da ** 2;
            $denomB += $db ** 2;
        }

        if ($denomA == 0.0 || $denomB == 0.0) {
            // No variance among co-raters (e.g. everyone gave the same score) —
            // undefined correlation, treat as no signal rather than divide by zero.
            return [0.0, $coRaters];
        }

        $sim = $num / (sqrt($denomA) * sqrt($denomB));
        return [$sim, $coRaters];
    }

    /**
     * Recompute the full item-item similarity matrix and persist it to
     * item_similarity. This is O(n^2) in the number of movies and is meant
     * to run offline (see scripts/recompute_similarity.php), not per-request.
     */
    public static function recomputeSimilarityMatrix(): int
    {
        $vectors = self::itemVectors();
        $movieIds = array_keys($vectors);
        sort($movieIds);

        $db = Database::connection();
        $db->beginTransaction();

        $upsert = $db->prepare(
            'INSERT INTO item_similarity (movie_id_a, movie_id_b, cosine_sim, pearson_sim, co_raters)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE cosine_sim = VALUES(cosine_sim),
                                     pearson_sim = VALUES(pearson_sim),
                                     co_raters = VALUES(co_raters)'
        );

        $pairsWritten = 0;
        $count = count($movieIds);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $idA = $movieIds[$i];
                $idB = $movieIds[$j];

                [$cosine, $coRatersCosine] = self::cosineSimilarity($vectors[$idA], $vectors[$idB]);
                [$pearson, $coRatersPearson] = self::pearsonSimilarity($vectors[$idA], $vectors[$idB]);
                $coRaters = max($coRatersCosine, $coRatersPearson);

                if ($coRaters < self::MIN_CO_RATERS) {
                    continue; // not enough shared signal to trust this pair
                }

                $upsert->execute([$idA, $idB, round($cosine, 6), round($pearson, 6), $coRaters]);
                $pairsWritten++;
            }
        }

        $db->commit();
        return $pairsWritten;
    }

    /**
     * @return array<int, array{movie_id:int, similarity:float, co_raters:int}>
     */
    private static function neighborsOf(int $movieId, string $algorithm, PDO $db): array
    {
        $column = $algorithm === 'pearson' ? 'pearson_sim' : 'cosine_sim';

        $stmt = $db->prepare(
            "SELECT movie_id_a, movie_id_b, {$column} AS similarity, co_raters
             FROM item_similarity
             WHERE movie_id_a = :id_a OR movie_id_b = :id_b"
        );
        $stmt->execute(['id_a' => $movieId, 'id_b' => $movieId]);

        $neighbors = [];
        foreach ($stmt->fetchAll() as $row) {
            $otherId = (int) $row['movie_id_a'] === $movieId ? (int) $row['movie_id_b'] : (int) $row['movie_id_a'];
            $neighbors[] = [
                'movie_id'   => $otherId,
                'similarity' => (float) $row['similarity'],
                'co_raters'  => (int) $row['co_raters'],
            ];
        }
        return $neighbors;
    }

    /**
     * Predict ratings for every movie the user hasn't rated yet and return
     * the top N as recommendations. Falls back to "most popular" for users
     * with no ratings yet (cold start).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recommendForUser(int $userId, string $algorithm = 'cosine', int $topN = 10): array
    {
        $algorithm = $algorithm === 'pearson' ? 'pearson' : 'cosine';
        $userRatings = Rating::forUser($userId);

        if (empty($userRatings)) {
            return self::popularFallback($topN);
        }

        $db = Database::connection();
        $scores = []; // movieId => ['num' => float, 'denom' => float, 'neighbors' => int]

        foreach ($userRatings as $ratedMovieId => $ratedValue) {
            $neighbors = self::neighborsOf($ratedMovieId, $algorithm, $db);

            foreach ($neighbors as $n) {
                $candidateId = $n['movie_id'];

                if (isset($userRatings[$candidateId])) {
                    continue; // already rated, not a candidate
                }
                if ($n['similarity'] <= 0) {
                    continue; // ignore negative/no correlation
                }

                $scores[$candidateId]['num']       = ($scores[$candidateId]['num'] ?? 0) + $n['similarity'] * $ratedValue;
                $scores[$candidateId]['denom']     = ($scores[$candidateId]['denom'] ?? 0) + abs($n['similarity']);
                $scores[$candidateId]['neighbors'] = ($scores[$candidateId]['neighbors'] ?? 0) + 1;
            }
        }

        if (empty($scores)) {
            return self::popularFallback($topN);
        }

        $predictions = [];
        foreach ($scores as $movieId => $s) {
            if ($s['denom'] == 0.0) {
                continue;
            }
            $predictions[$movieId] = [
                'movie_id'       => $movieId,
                'predicted'      => $s['num'] / $s['denom'],
                'neighbor_count' => $s['neighbors'],
            ];
        }

        usort($predictions, fn ($a, $b) => $b['predicted'] <=> $a['predicted']);
        $top = array_slice($predictions, 0, $topN);

        return self::hydrate($top);
    }

    /**
     * Cold-start fallback: highest-rated movies with enough votes to be trustworthy.
     */
    private static function popularFallback(int $topN): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT m.*, ROUND(AVG(r.rating), 2) AS avg_rating, COUNT(r.id) AS rating_count
             FROM movies m
             JOIN ratings r ON r.movie_id = m.id
             GROUP BY m.id
             HAVING rating_count >= 2
             ORDER BY avg_rating DESC, rating_count DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $topN, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function ($row) {
            $row['predicted']      = (float) $row['avg_rating'];
            $row['neighbor_count'] = 0;
            $row['is_fallback']    = true;
            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * Attach full movie details to a list of {movie_id, predicted, neighbor_count}.
     */
    private static function hydrate(array $predictions): array
    {
        $result = [];
        foreach ($predictions as $p) {
            $movie = Movie::find($p['movie_id']);
            if ($movie === null) {
                continue;
            }
            $movie['predicted']      = round($p['predicted'], 3);
            $movie['neighbor_count'] = $p['neighbor_count'];
            $movie['is_fallback']    = false;
            $result[] = $movie;
        }
        return $result;
    }
}
