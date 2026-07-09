-- CineLens database schema
-- MySQL / MariaDB, InnoDB, utf8mb4

CREATE DATABASE IF NOT EXISTS cinelens
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cinelens;

-- ---------------------------------------------------------------
-- users
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- movies
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movies (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(200) NOT NULL,
    genre         VARCHAR(100) NOT NULL,
    release_year  SMALLINT     NOT NULL,
    description   TEXT,
    poster_url    VARCHAR(500),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_title_desc (title, description)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- ratings  (one rating per user per movie)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    movie_id   INT NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_movie (user_id, movie_id),
    CONSTRAINT chk_rating_range CHECK (rating BETWEEN 1 AND 5),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_ratings_movie ON ratings(movie_id);
CREATE INDEX idx_ratings_user  ON ratings(user_id);

-- ---------------------------------------------------------------
-- item_similarity  (cached similarity matrix, recomputed offline)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS item_similarity (
    movie_id_a  INT NOT NULL,
    movie_id_b  INT NOT NULL,
    cosine_sim  DECIMAL(7,6) NOT NULL DEFAULT 0,
    pearson_sim DECIMAL(7,6) NOT NULL DEFAULT 0,
    co_raters   INT NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (movie_id_a, movie_id_b),
    FOREIGN KEY (movie_id_a) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id_b) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB;
