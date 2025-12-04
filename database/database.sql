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
-- User Posts Feature Migration
-- Run this after initial database setup
-- This adds support for user-created posts about any product

-- Add categories table for organizing posts
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user_posts table for freeform product reviews/posts
CREATE TABLE IF NOT EXISTS user_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    product_name VARCHAR(200) NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating BETWEEN 1.0 AND 5.0),
    post_text TEXT NOT NULL,
    tags VARCHAR(500),
    sentiment_score FLOAT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_category (category_id),
    INDEX idx_rating (rating),
    INDEX idx_created (created_at DESC),
    FULLTEXT idx_search (product_name, post_text, tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add post_images table for photo carousel
CREATE TABLE IF NOT EXISTS post_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(200),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES user_posts(post_id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add post_likes table for thumbs up/down
CREATE TABLE IF NOT EXISTS post_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    like_type TINYINT NOT NULL COMMENT '1=like, -1=dislike',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES user_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_post (user_id, post_id),
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add post_comments table
CREATE TABLE IF NOT EXISTS post_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT DEFAULT NULL,
    comment_text TEXT NOT NULL,
    rating DECIMAL(2,1) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES user_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES post_comments(comment_id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_parent (parent_comment_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment_likes table
CREATE TABLE IF NOT EXISTS comment_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES post_comments(comment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_comment (user_id, comment_id),
    INDEX idx_comment (comment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add community insights table (aggregated data)
CREATE TABLE IF NOT EXISTS community_insights (
    insight_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(200) NOT NULL,
    positive_keywords JSON,
    negative_keywords JSON,
    common_praise TEXT,
    common_complaints TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product (product_name),
    INDEX idx_product (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed categories
INSERT INTO categories (category_id, parent_id, name, slug) VALUES
(1, NULL, 'Electronics', 'electronics'),
(2, 1, 'Smartphone', 'smartphone'),
(3, 1, 'Laptop', 'laptop'),
(4, 1, 'Tablet', 'tablet'),
(5, 1, 'Smartwatch', 'smartwatch'),
(6, NULL, 'Home Appliances', 'home-appliances'),
(7, 6, 'Refrigerator', 'refrigerator'),
(8, 6, 'Air Conditioner', 'air-conditioner'),
(9, NULL, 'Fashion', 'fashion'),
(10, NULL, 'Books', 'books');

-- Insert seed user posts
INSERT INTO user_posts (user_id, category_id, product_name, rating, post_text, tags, sentiment_score, is_verified) VALUES
(1, 2, 'Samsung Galaxy A55 5G', 4.0, 
'The camera quality is excellent, especially in daylight. Battery lasts easily for one full day. However, charging speed could be faster.',
'#Camera #Battery #MidRange', 0.6, TRUE),

(2, 2, 'Redmi Note 13', 4.3,
'Amazing value for money! Display is vibrant and performance is smooth. Gaming experience is good but heats up a bit.',
'#Budget #Gaming #Display', 0.7, TRUE),

(3, 2, 'Vivo V30 Lite', 4.0,
'Great selfie camera! Design is sleek and premium. Battery life is decent but not exceptional.',
'#Selfie #Design #Camera', 0.5, FALSE),

(4, 3, 'MacBook Air M2', 4.8,
'Incredible performance and battery life! Silent operation is a huge plus. Perfect for developers and content creators.',
'#Performance #Battery #Professional', 0.9, TRUE),

(5, 5, 'Apple Watch Series 9', 4.5,
'Health tracking is accurate. Battery lasts 2 days. Fitness features are comprehensive. A bit expensive though.',
'#Fitness #Health #Premium', 0.65, TRUE);

-- Insert post likes
INSERT INTO post_likes (post_id, user_id, like_type) VALUES
(1, 2, 1), (1, 3, 1), (1, 4, 1), (1, 5, -1),
(2, 1, 1), (2, 3, 1), (2, 5, 1),
(3, 1, 1), (3, 2, 1), (3, 4, -1),
(4, 1, 1), (4, 2, 1), (4, 3, 1), (4, 5, 1),
(5, 2, 1), (5, 3, 1), (5, 4, 1);

-- Insert post comments
INSERT INTO post_comments (post_id, user_id, comment_text, rating) VALUES
(1, 2, 'Totally agree! Camera is solid for this price.', 4.0),
(1, 3, 'Mine heats up after long gaming 😅', NULL),
(2, 1, 'How is the MIUI experience? Any bloatware?', NULL),
(2, 4, 'MIUI is better now, minimal bloat if you choose carefully', NULL),
(3, 5, 'Front camera quality is really impressive', 4.5),
(4, 2, 'Is 8GB RAM enough for video editing?', NULL),
(5, 1, 'Battery life claim is accurate! Love it', 5.0);

-- Insert comment likes
INSERT INTO comment_likes (comment_id, user_id) VALUES
(1, 1), (1, 3), (1, 4), (1, 5),
(2, 1), (2, 4),
(3, 2), (3, 5),
(5, 1), (5, 2), (5, 3);

-- Insert community insights
INSERT INTO community_insights (product_name, positive_keywords, negative_keywords, common_praise, common_complaints) VALUES
('Samsung Galaxy A55 5G', 
 '["camera", "battery", "display", "performance"]',
 '["charging", "heating", "price"]',
 'Great camera quality and good battery life',
 'Slow charging speed'),
 
('Redmi Note 13',
 '["value", "display", "performance", "gaming"]',
 '["heating", "MIUI", "bloatware"]',
 'Excellent value for money with great display',
 'Device heating during gaming');

-- Add view tracking trigger
DELIMITER //
CREATE TRIGGER IF NOT EXISTS increment_post_views
AFTER INSERT ON post_comments
FOR EACH ROW
BEGIN
    UPDATE user_posts SET view_count = view_count + 1 WHERE post_id = NEW.post_id;
END//
DELIMITER ;