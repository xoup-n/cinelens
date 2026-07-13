-- Seed data for CineLens
-- Run AFTER schema.sql:  mysql -u root -p cinelens < sql/seed.sql
USE cinelens;

-- ---------------------------------------------------------------
-- Movies (20 titles across several genres)
-- ---------------------------------------------------------------
INSERT INTO movies (title, genre, release_year, description, poster_url) VALUES
('Nebula Drift',        'Sci-Fi',   2019, 'A salvage crew finds a derelict ship with a mind of its own.', NULL),
('Quiet Horizon',       'Drama',    2016, 'Two estranged siblings reconnect during their father''s final months.', NULL),
('Last Light Protocol', 'Sci-Fi',   2021, 'An AI tasked with preserving humanity begins to question its orders.', NULL),
('Paper Lanterns',      'Drama',    2014, 'A small-town teacher rebuilds her life after a flood.', NULL),
('Static Pulse',        'Thriller', 2020, 'A radio host uncovers a conspiracy hidden in late-night call-ins.', NULL),
('Comet Season',        'Sci-Fi',   2017, 'A family road trip collides with the arrival of an alien signal.', NULL),
('Laugh Track',         'Comedy',   2018, 'A failing sitcom writer accidentally becomes a stand-up sensation.', NULL),
('The Wrong Reservation','Comedy',  2015, 'A double-booked wedding venue forces two families to share one day.', NULL),
('Glass Corridor',      'Thriller', 2019, 'A museum heist unravels when the thieves realize they are being watched.', NULL),
('Undertow',            'Thriller', 2022, 'A marine biologist finds evidence of a cover-up after a coastal disaster.', NULL),
('Painted Skies',       'Animation',2013, 'A young cartographer maps a world that redraws itself every night.', NULL),
('Tin Can Heart',       'Animation',2020, 'A discarded robot searches junkyards for the parts of its old self.', NULL),
('Marigold Avenue',     'Comedy',   2021, 'Neighbors on a quiet street compete over the world''s pettiest lawn contest.', NULL),
('Signal Lost',         'Sci-Fi',   2015, 'A deep-space communications officer is the last to know Earth went silent.', NULL),
('The Long Thaw',       'Drama',    2019, 'A glacier researcher confronts old grief while the ice she studies melts.', NULL),
('Midnight Ledger',     'Thriller', 2017, 'An accountant discovers her firm has been laundering more than money.', NULL),
('Feather & Frame',     'Animation',2016, 'An orphaned bird and an old painter build an unlikely friendship.', NULL),
('Second Wind',         'Drama',    2022, 'A retired sprinter coaches a rival''s daughter for one last shot at gold.', NULL),
('Static and Stars',    'Sci-Fi',   2023, 'Two rival colonies on Mars must share one failing life-support system.', NULL),
('Kitchen Confidential Lies','Comedy',2014,'A chef fakes a five-star review to save his restaurant, then the reviewer shows up.', NULL);

-- ---------------------------------------------------------------
-- Synthetic "seed" users — used ONLY to populate the ratings matrix
-- so the recommendation engine has data to work with out of the box.
-- Their password hashes are placeholders (not valid logins).
-- Create real, loginable accounts via scripts/create_admin.php or /register.php
-- ---------------------------------------------------------------
INSERT INTO users (username, email, password_hash, role) VALUES
('seed_scifi_fan',    'seed1@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000001', 'user'),
('seed_scifi_fan2',   'seed2@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000002', 'user'),
('seed_drama_lover',  'seed3@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000003', 'user'),
('seed_drama_lover2', 'seed4@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000004', 'user'),
('seed_comedy_buff',  'seed5@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000005', 'user'),
('seed_comedy_buff2', 'seed6@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000006', 'user'),
('seed_thriller_head','seed7@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000007', 'user'),
('seed_thriller_head2','seed8@cinelens.local', '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000008', 'user'),
('seed_animation_kid','seed9@cinelens.local',  '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000009', 'user'),
('seed_generalist',   'seed10@cinelens.local', '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000010', 'user'),
('seed_generalist2',  'seed11@cinelens.local', '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000011', 'user'),
('seed_critic',       'seed12@cinelens.local', '$2y$10$SeedAccountNotForLoginPlaceholderHash0000000000000012', 'user');

-- Ratings — patterned so that genre clusters emerge and the
-- similarity algorithm has real signal to find.
-- movie ids 1-20 follow insertion order above.

-- seed_scifi_fan: loves sci-fi (1,3,6,14,19), lukewarm elsewhere
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(1,1,5),(1,3,5),(1,6,4),(1,14,5),(1,19,5),(1,2,2),(1,9,3),(1,16,3);

-- seed_scifi_fan2: also sci-fi, slightly different taste
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(2,1,4),(2,3,5),(2,6,5),(2,14,4),(2,19,4),(2,11,3),(2,20,2);

-- seed_drama_lover: drama cluster (2,4,15,18)
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(3,2,5),(3,4,5),(3,15,5),(3,18,4),(3,1,2),(3,7,2),(3,10,3);

-- seed_drama_lover2
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(4,2,4),(4,4,5),(4,15,4),(4,18,5),(4,9,2),(4,13,3);

-- seed_comedy_buff: comedy cluster (7,8,13,20)
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(5,7,5),(5,8,5),(5,13,4),(5,20,5),(5,3,2),(5,16,2);

-- seed_comedy_buff2
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(6,7,4),(6,8,4),(6,13,5),(6,20,4),(6,17,3),(6,11,3);

-- seed_thriller_head: thriller cluster (5,9,10,16)
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(7,5,5),(7,9,5),(7,10,4),(7,16,5),(7,1,3),(7,6,2);

-- seed_thriller_head2
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(8,5,4),(8,9,4),(8,10,5),(8,16,4),(8,2,2),(8,4,2);

-- seed_animation_kid: animation cluster (11,12,17)
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(9,11,5),(9,12,5),(9,17,4),(9,7,3),(9,13,3),(9,8,2);

-- seed_generalist: a bit of everything (helps connect clusters)
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(10,1,4),(10,2,4),(10,5,3),(10,7,4),(10,11,4),(10,14,3),(10,18,3),(10,20,3);

-- seed_generalist2
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(11,3,3),(11,4,3),(11,8,3),(11,9,3),(11,12,4),(11,15,3),(11,19,3),(11,13,3);

-- seed_critic: rates almost everything, slightly harsh (adds baseline signal)
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(12,1,3),(12,2,3),(12,3,3),(12,4,3),(12,5,2),(12,6,3),(12,7,3),(12,8,2),
(12,9,3),(12,10,3),(12,11,3),(12,12,3),(12,13,2),(12,14,3),(12,15,3),
(12,16,3),(12,17,3),(12,18,3),(12,19,3),(12,20,2);
