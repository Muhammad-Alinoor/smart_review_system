<?php
/**
 * Auto Setup User Posts Feature
 * Run this once to add the user posts feature automatically
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup User Posts Feature</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 Setting Up User Posts Feature...</h1>
<pre>";

try {
    echo "Step 1: Checking if tables already exist...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='info'>ℹ️  User posts tables already exist. Skipping creation.</span>\n\n";
    } else {
        echo "<span class='success'>✅ Tables don't exist. Creating now...</span>\n\n";
        
        // Create categories table
        echo "Creating categories table...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                category_id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT DEFAULT NULL,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parent (parent_id),
                INDEX idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='success'>✅ categories table created</span>\n";
        
        // Create user_posts table
        echo "Creating user_posts table...\n";
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='success'>✅ user_posts table created</span>\n";
        
        // Create post_likes table
        echo "Creating post_likes table...\n";
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='success'>✅ post_likes table created</span>\n";
        
        // Create post_comments table
        echo "Creating post_comments table...\n";
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='success'>✅ post_comments table created</span>\n";
        
        // Create comment_likes table
        echo "Creating comment_likes table...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS comment_likes (
                like_id INT AUTO_INCREMENT PRIMARY KEY,
                comment_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (comment_id) REFERENCES post_comments(comment_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_comment (user_id, comment_id),
                INDEX idx_comment (comment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='success'>✅ comment_likes table created</span>\n";
        
        // Create community_insights table
        echo "Creating community_insights table...\n";
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='success'>✅ community_insights table created</span>\n\n";
    }
    
    // Insert categories if table is empty
    echo "Step 2: Adding categories...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Inserting categories...\n";
        $pdo->exec("
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
            (10, NULL, 'Books', 'books')
        ");
        echo "<span class='success'>✅ 10 categories inserted</span>\n\n";
    } else {
        echo "<span class='info'>ℹ️  Categories already exist ($count categories)</span>\n\n";
    }
    
    // Insert sample posts if table is empty
    echo "Step 3: Adding sample posts...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_posts");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Inserting sample posts...\n";
        $pdo->exec("
            INSERT INTO user_posts (user_id, category_id, product_name, rating, post_text, tags, sentiment_score, is_verified) VALUES
            (1, 2, 'Samsung Galaxy A55 5G', 4.0, 
            'The camera quality is excellent, especially in daylight. Battery lasts easily for one full day. However, charging speed could be faster.',
            '#Camera #Battery #MidRange', 0.6, TRUE),
            
            (2, 2, 'Redmi Note 13', 4.3,
            'Amazing value for money! Display is vibrant and performance is smooth. Gaming experience is good but heats up a bit.',
            '#Budget #Gaming #Display', 0.7, TRUE),
            
            (3, 2, 'Vivo V30 Lite', 4.0,
            'Great selfie camera! Design is sleek and premium. Battery life is decent but not exceptional.',
            '#Selfie #Design #Camera', 0.5, FALSE)
        ");
        echo "<span class='success'>✅ 3 sample posts inserted</span>\n\n";
    } else {
        echo "<span class='info'>ℹ️  Posts already exist ($count posts)</span>\n\n";
    }
    
    echo "\n<span class='success'>========================================\n";
    echo "✅ SETUP COMPLETED SUCCESSFULLY!\n";
    echo "========================================</span>\n\n";
    
    echo "You can now:\n";
    echo "1. Visit the feed: <a href='public/feed.php'>public/feed.php</a>\n";
    echo "2. Create posts: <a href='public/create_post.php'>public/create_post.php</a>\n";
    echo "3. Go to homepage: <a href='public/index.php'>public/index.php</a>\n\n";
    
    echo "<span class='info'>Note: You can run this script multiple times safely.</span>\n";
    
} catch (PDOException $e) {
    echo "\n<span class='error'>========================================\n";
    echo "❌ ERROR OCCURRED!\n";
    echo "========================================</span>\n\n";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n\n";
    echo "Please check:\n";
    echo "1. Database connection is working\n";
    echo "2. You have imported database/database.sql first\n";
    echo "3. MySQL user has CREATE TABLE permissions\n";
}

echo "</pre>
<hr>
<p><a href='public/index.php' style='padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>← Back to Homepage</a></p>
</div>
</body>
</html>";
?>