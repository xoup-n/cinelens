<?php

declare(strict_types=1);

/**
 * Usage: php scripts/recompute_similarity.php
 *
 * Recomputes the item_similarity cache from the current ratings table.
 * Run this after a batch of new ratings comes in (e.g. via cron), not on
 * every page load — similarity computation is O(n^2) in movie count.
 */

require_once __DIR__ . '/../src/RecommendationEngine.php';

$start = microtime(true);
$pairs = RecommendationEngine::recomputeSimilarityMatrix();
$elapsed = round(microtime(true) - $start, 3);

echo "Recomputed similarity for {$pairs} movie pairs in {$elapsed}s.\n";
