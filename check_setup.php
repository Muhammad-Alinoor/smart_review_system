<?php
/**
 * Setup Checker
 * Verifies all files and database tables exist
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Checker - Smart Review System</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #2c3e50; }
        .check-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .section { margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Smart Review System - Setup Checker</h1>
        
        <div class="section">
            <h2>Database Connection</h2>
            <?php
            try {
                require_once 'config/db.php';
                echo '<div class="check-item success">✅ Database connection successful</div>';
            } catch (Exception $e) {
                echo '<div class="check-item error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="check-item info">Make sure you have imported database/database.sql</div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>Core Tables</h2>
            <?php
            $coreTables = ['users', 'items', 'reviews', 'comments', 'likes', 'scores', 'search_logs'];
            foreach ($coreTables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    echo "<div class='check-item success'>✅ Table '$table' exists ($count rows)</div>";
                } catch (Exception $e) {
                    echo "<div class='check-item error'>❌ Table '$table' missing</div>";
                }
            }
            ?>
        </div>

        <div class="section">
            <h2>User Posts Feature Tables</h2>
            <?php
            $postTables = ['categories', 'user_posts', 'post_likes', 'post_comments', 'comment_likes', 'community_insights'];
            $allExist = true;
            foreach ($postTables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    echo "<div class='check-item success'>✅ Table '$table' exists ($count rows)</div>";
                } catch (Exception $e) {
                    echo "<div class='check-item error'>❌ Table '$table' missing</div>";
                    $allExist = false;
                }
            }
            
            if (!$allExist) {
                echo '<div class="check-item info">⚠️ User Posts feature not installed. Import <code>database/add_user_posts.sql</code></div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>Required Files</h2>
            <?php
            $requiredFiles = [
                'public/index.php' => 'Homepage',
                'public/feed.php' => 'Review Feed',
                'public/create_post.php' => 'Create Post',
                'public/post_view.php' => 'Post View',
                'api/create_post.php' => 'Create Post API',
                'api/get_feed.php' => 'Get Feed API',
                'api/like_post.php' => 'Like Post API',
                'api/post_post_comment.php' => 'Post Comment API',
                'functions/compute_score.php' => 'Score Calculator',
                'functions/analyze_sentiment.php' => 'Sentiment Analyzer'
            ];
            
            foreach ($requiredFiles as $file => $name) {
                if (file_exists($file)) {
                    echo "<div class='check-item success'>✅ $name: <code>$file</code></div>";
                } else {
                    echo "<div class='check-item error'>❌ $name missing: <code>$file</code></div>";
                }
            }
            ?>
        </div>

        <div class="section">
            <h2>Quick Actions</h2>
            <p><a href="public/index.php" style="padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">Go to Homepage</a></p>
            <p><a href="public/feed.php" style="padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;">Go to Feed</a></p>
            <p><a href="functions/init_scores.php" style="padding: 10px 20px; background: #f39c12; color: white; text-decoration: none; border-radius: 5px;">Initialize Scores</a></p>
        </div>

        <div class="section">
            <h2>Setup Instructions</h2>
            <ol>
                <li>Import <code>database/database.sql</code> in phpMyAdmin</li>
                <li>Import <code>database/add_user_posts.sql</code> in phpMyAdmin (for new feature)</li>
                <li>Visit <code>functions/init_scores.php</code> to compute initial scores</li>
                <li>Start using the application!</li>
            </ol>
        </div>
    </div>
</body>
</html>