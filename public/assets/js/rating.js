/**
 * Interactive 1-5 star rating widget.
 * Expects a container: <div id="rating-widget" data-movie-id="123">
 * with child buttons rendered server-side (see movie.php).
 */
(function () {
    const widget = document.getElementById('rating-widget');
    if (!widget) return;

    const stars = widget.querySelectorAll('.star-btn');
    if (stars.length === 0) return;

    const movieId = parseInt(widget.dataset.movieId, 10);
    const statusEl = widget.querySelector('.rating-status');

    function paint(activeValue) {
        stars.forEach((star) => {
            const value = parseInt(star.dataset.value, 10);
            star.classList.toggle('filled', value <= activeValue);
        });
    }

    stars.forEach((star) => {
        star.addEventListener('mouseenter', () => paint(parseInt(star.dataset.value, 10)));
        star.addEventListener('mouseleave', () => paint(widget.dataset.currentRating || 0));

        star.addEventListener('click', async () => {
            const value = parseInt(star.dataset.value, 10);

            stars.forEach((s) => (s.disabled = true));
            if (statusEl) statusEl.textContent = 'Зберігаємо…';

            try {
                const res = await fetch('/rate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ movie_id: movieId, rating: value }),
                });
                const data = await res.json();

                if (!res.ok || !data.ok) {
                    throw new Error(data.error || 'Не вдалося зберегти оцінку.');
                }

                widget.dataset.currentRating = String(value);
                paint(value);
                if (statusEl) {
                    statusEl.textContent =
                        `Дякуємо! Ваша оцінка: ${value}/5 · середня: ${data.avg_rating} (${data.rating_count})`;
                }
            } catch (err) {
                if (statusEl) statusEl.textContent = err.message;
            } finally {
                stars.forEach((s) => (s.disabled = false));
            }
        });
    });

    paint(parseInt(widget.dataset.currentRating || '0', 10));
})();
