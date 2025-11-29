-- Smart Review System Database Schema
-- Drop existing tables if they exist
DROP TABLE IF EXISTS search_logs;
DROP TABLE IF EXISTS scores;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;

-- Users table: stores user authentication data
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items table: products/services to be reviewed (mobile phones)
CREATE TABLE items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    metadata_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table: user reviews with ratings and sentiment
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    sentiment_score FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table: comments on reviews
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_review (review_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Likes table: user likes/dislikes on reviews
CREATE TABLE likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    like_type TINYINT NOT NULL COMMENT '1=like, -1=dislike',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (user_id, review_id),
    INDEX idx_review (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scores table: cached computed scores for items
CREATE TABLE scores (
    score_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL UNIQUE,
    score_value FLOAT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    INDEX idx_score (score_value DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search logs table: track search queries for analytics
CREATE TABLE search_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(255) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_query (query),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA
-- ============================================

-- Insert test users (password: password123 for all)
INSERT INTO users (name, email, password_hash) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Ahmed Rahman', 'ahmed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Fatima Hassan', 'fatima@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Michael Chen', 'michael@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert 8 mobile phone items
INSERT INTO items (title, description, metadata_json) VALUES
('iPhone 15 Pro Max', 'Apple flagship with A17 Pro chip, titanium design, and advanced camera system', 
 '{"brand": "Apple", "price": 1199, "storage": "256GB", "color": "Natural Titanium", "screen": "6.7 inch"}'),
('Samsung Galaxy S24 Ultra', 'Premium Android phone with S Pen, 200MP camera, and AI features',
 '{"brand": "Samsung", "price": 1299, "storage": "512GB", "color": "Titanium Gray", "screen": "6.8 inch"}'),
('Google Pixel 8 Pro', 'Google phone with Tensor G3, exceptional camera AI, and pure Android',
 '{"brand": "Google", "price": 999, "storage": "256GB", "color": "Obsidian", "screen": "6.7 inch"}'),
('OnePlus 12', 'Flagship killer with Snapdragon 8 Gen 3, fast charging, and great value',
 '{"brand": "OnePlus", "price": 799, "storage": "256GB", "color": "Flowy Emerald", "screen": "6.82 inch"}'),
('Xiaomi 14 Pro', 'Premium phone with Leica camera, powerful specs, and competitive pricing',
 '{"brand": "Xiaomi", "price": 899, "storage": "512GB", "color": "Titanium", "screen": "6.73 inch"}'),
('iPhone 14', 'Previous gen Apple phone still great value with reliable performance',
 '{"brand": "Apple", "price": 699, "storage": "128GB", "color": "Midnight", "screen": "6.1 inch"}'),
('Samsung Galaxy A54', 'Mid-range Samsung with great camera and long battery life',
 '{"brand": "Samsung", "price": 449, "storage": "128GB", "color": "Awesome Violet", "screen": "6.4 inch"}'),
('Nothing Phone 2', 'Unique design with Glyph interface, clean software, and solid performance',
 '{"brand": "Nothing", "price": 599, "storage": "256GB", "color": "White", "screen": "6.7 inch"}');

-- Insert ~40 reviews across all items
INSERT INTO reviews (item_id, user_id, rating, review_text, sentiment_score, created_at) VALUES
-- iPhone 15 Pro Max reviews
(1, 1, 5, 'Amazing phone! The camera quality is outstanding and the titanium build feels premium. Best iPhone yet.', 0.8, '2024-11-01 10:30:00'),
(1, 2, 4, 'Great performance but very expensive. Battery life could be better for the price.', 0.3, '2024-11-02 14:20:00'),
(1, 3, 5, 'Perfect! Smooth, fast, beautiful display. Worth every penny.', 0.9, '2024-11-03 09:15:00'),
(1, 4, 3, 'Good phone but not a huge upgrade from 14 Pro. Overpriced in my opinion.', -0.2, '2024-11-05 16:45:00'),
(1, 5, 5, 'Excellent build quality and camera system. The action button is very useful.', 0.7, '2024-11-08 11:00:00'),

-- Samsung Galaxy S24 Ultra reviews
(2, 2, 5, 'The S Pen makes this phone unique. Amazing screen and camera. Best Android phone!', 0.85, '2024-10-28 13:30:00'),
(2, 1, 4, 'Powerful phone with great features but battery drains fast with heavy use.', 0.4, '2024-10-30 15:20:00'),
(2, 3, 5, 'Outstanding! The 200MP camera captures incredible detail. Love the AI features.', 0.8, '2024-11-01 10:00:00'),
(2, 4, 4, 'Solid phone overall. S Pen is great for notes. A bit bulky though.', 0.5, '2024-11-04 12:30:00'),
(2, 5, 5, 'Perfect for productivity and photography. Best Samsung ever made.', 0.9, '2024-11-06 09:45:00'),

-- Google Pixel 8 Pro reviews
(3, 1, 5, 'Best camera phone on the market. The AI photo editing is magical. Clean Android experience.', 0.9, '2024-10-25 14:00:00'),
(3, 3, 4, 'Great phone with excellent software. Battery life is decent but not amazing.', 0.6, '2024-10-27 11:30:00'),
(3, 2, 5, 'Love the pure Android and fast updates. Camera quality is unmatched.', 0.8, '2024-10-29 16:15:00'),
(3, 5, 4, 'Good phone but gets warm during gaming. Otherwise very happy with it.', 0.4, '2024-11-02 13:20:00'),
(3, 4, 5, 'Brilliant! The Tensor chip handles everything smoothly. Highly recommend.', 0.75, '2024-11-07 10:30:00'),

-- OnePlus 12 reviews
(4, 2, 5, 'Amazing value for money! Fast charging is incredible - 100% in 30 minutes.', 0.85, '2024-10-26 12:00:00'),
(4, 4, 4, 'Great performance and display. Camera is good but not flagship level.', 0.5, '2024-10-28 14:30:00'),
(4, 1, 5, 'Best bang for buck. Smooth software and powerful hardware. Very impressed!', 0.8, '2024-11-01 09:00:00'),
(4, 5, 4, 'Solid phone with no major issues. Battery life is excellent.', 0.6, '2024-11-03 15:45:00'),
(4, 3, 5, 'Perfect! Fast, beautiful, and affordable. OnePlus nailed it this time.', 0.9, '2024-11-05 11:20:00'),

-- Xiaomi 14 Pro reviews
(5, 3, 4, 'Great camera with Leica partnership. Build quality is premium. Good value.', 0.7, '2024-10-24 10:30:00'),
(5, 1, 4, 'Solid performance and nice design. MIUI can be bloated but customizable.', 0.3, '2024-10-27 13:15:00'),
(5, 5, 5, 'Excellent phone! Camera rivals the best. Fast charging and great screen.', 0.8, '2024-10-30 16:00:00'),
(5, 2, 3, 'Good hardware but software needs improvement. Too many pre-installed apps.', -0.1, '2024-11-02 11:45:00'),
(5, 4, 4, 'Nice phone overall. Camera is impressive and performance is smooth.', 0.6, '2024-11-06 14:30:00'),

-- iPhone 14 reviews
(6, 4, 4, 'Still a great phone in 2024. Reliable and smooth. Good value now that price dropped.', 0.7, '2024-10-23 09:30:00'),
(6, 2, 4, 'Solid iPhone experience. Camera is good and battery lasts all day.', 0.6, '2024-10-26 12:00:00'),
(6, 5, 3, 'Decent phone but feels dated compared to newer models. Still works well though.', 0.2, '2024-10-29 15:30:00'),
(6, 1, 4, 'Good reliable phone. Perfect for those who don''t need latest features.', 0.5, '2024-11-01 10:15:00'),
(6, 3, 5, 'Great value! Does everything I need. Battery life is excellent.', 0.8, '2024-11-04 13:45:00'),

-- Samsung Galaxy A54 reviews
(7, 5, 4, 'Excellent mid-range phone. Great camera and battery life for the price.', 0.7, '2024-10-22 11:00:00'),
(7, 3, 5, 'Amazing value! Premium features at mid-range price. Very happy with purchase.', 0.85, '2024-10-25 14:30:00'),
(7, 1, 4, 'Good phone for the money. Screen is beautiful and performance is adequate.', 0.6, '2024-10-28 09:45:00'),
(7, 4, 3, 'Decent phone but can lag sometimes. Camera is good in daylight only.', 0.1, '2024-11-01 16:20:00'),
(7, 2, 4, 'Solid mid-ranger. Battery easily lasts two days. Great for daily use.', 0.7, '2024-11-05 12:00:00'),

-- Nothing Phone 2 reviews
(8, 1, 4, 'Unique design with Glyph lights. Clean software and good performance. Refreshing!', 0.7, '2024-10-21 13:30:00'),
(8, 5, 5, 'Love the design and software! Glyph interface is fun and useful. Great phone.', 0.85, '2024-10-24 10:00:00'),
(8, 2, 4, 'Good phone with unique features. Camera could be better but overall happy.', 0.5, '2024-10-27 15:15:00'),
(8, 3, 3, 'Interesting phone but Glyph is more gimmick than useful. Performance is okay.', -0.1, '2024-10-31 11:30:00'),
(8, 4, 4, 'Clean Android experience and cool design. Good value for what you get.', 0.6, '2024-11-03 14:00:00');

-- Insert comments on reviews
INSERT INTO comments (review_id, user_id, comment_text, created_at) VALUES
(1, 2, 'I agree! The camera is incredible in low light too.', '2024-11-01 11:00:00'),
(1, 3, 'How is the battery life compared to 14 Pro?', '2024-11-01 12:30:00'),
(2, 1, 'Battery improves after a few charge cycles.', '2024-11-02 15:00:00'),
(6, 3, 'The S Pen is a game changer for note taking!', '2024-10-28 14:00:00'),
(6, 4, 'Does it work well with Samsung Notes app?', '2024-10-28 16:30:00'),
(11, 2, 'Agreed! Pixel cameras are unbeatable.', '2024-10-25 15:00:00'),
(11, 5, 'How is the battery compared to iPhone?', '2024-10-25 17:30:00'),
(16, 1, 'That fast charging is a must-have feature!', '2024-10-26 13:00:00'),
(16, 3, 'Does it support wireless charging?', '2024-10-26 14:30:00'),
(21, 4, 'Leica cameras always produce great colors.', '2024-10-24 11:30:00'),
(26, 5, 'Perfect choice if you want reliable iPhone experience.', '2024-10-23 10:30:00'),
(31, 1, 'Best value Samsung phone right now!', '2024-10-22 12:00:00'),
(31, 2, 'How does it compare to A53?', '2024-10-22 13:30:00'),
(36, 3, 'The Glyph lights are so cool at night!', '2024-10-21 14:30:00'),
(36, 5, 'Can you customize the Glyph patterns?', '2024-10-21 16:00:00');

-- Insert likes and dislikes
INSERT INTO likes (review_id, user_id, like_type) VALUES
-- Likes on positive reviews
(1, 2, 1), (1, 3, 1), (1, 4, 1), (1, 5, 1),
(3, 1, 1), (3, 2, 1), (3, 4, 1),
(6, 1, 1), (6, 3, 1), (6, 4, 1), (6, 5, 1),
(8, 2, 1), (8, 3, 1), (8, 5, 1),
(11, 2, 1), (11, 3, 1), (11, 4, 1), (11, 5, 1),
(13, 1, 1), (13, 4, 1), (13, 5, 1),
(16, 1, 1), (16, 3, 1), (16, 4, 1), (16, 5, 1),
(18, 2, 1), (18, 3, 1), (18, 5, 1),
(21, 1, 1), (21, 2, 1), (21, 4, 1),
(23, 1, 1), (23, 3, 1), (23, 5, 1),
(26, 2, 1), (26, 3, 1), (26, 5, 1),
(28, 1, 1), (28, 4, 1), (28, 5, 1),
(31, 1, 1), (31, 2, 1), (31, 3, 1), (31, 4, 1),
(32, 2, 1), (32, 4, 1), (32, 5, 1),
(36, 2, 1), (36, 3, 1), (36, 4, 1),
(37, 1, 1), (37, 3, 1), (37, 4, 1),
-- Dislikes on negative/critical reviews
(4, 1, -1), (4, 2, -1),
(24, 3, -1), (24, 5, -1),
(29, 2, -1), (29, 3, -1),
(34, 2, -1), (34, 5, -1),
(39, 1, -1), (39, 5, -1);

-- Initialize scores table (will be computed by compute_score function)
INSERT INTO scores (item_id, score_value) VALUES
(1, 0), (2, 0), (3, 0), (4, 0), (5, 0), (6, 0), (7, 0), (8, 0);