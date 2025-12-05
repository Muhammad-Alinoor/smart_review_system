<?php
/**
 * Debug Feed - Diagnose feed loading issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Feed Debug</title>
<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
.success { color: #27ae60; }
.error { color: #e74c3c; }
.info { color: #3498db; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
</head><body>";

echo "<h1>🔍 Feed Debug Information</h1>";

// Test 1: Database Connection
echo "<div class='section'>";
echo "<h2>1. Database Connection</h2>";
try {
    require_once 'config/db.php';
    echo "<p class='success'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
    exit;
}
echo "</div>";

// Test 2: Check Tables
echo "<div class='section'>";
echo "<h2>2. Required Tables</h2>";
$tables = ['users', 'user_posts', 'categories', 'post_likes', 'post_comments', 'community_insights'];
$allExist = true;
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p class='success'>✅ $table: $count rows</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ $table: MISSING</p>";
        $allExist = false;
    }
}

if (!$allExist) {
    echo "<p class='info'>⚠️ Some tables are missing. Run <a href='setup_user_posts.php'>setup_user_posts.php</a></p>";
}
echo "</div>";

// Test 3: Sample Query
echo "<div class='section'>";
echo "<h2>3. Sample Feed Query</h2>";
try {
    $sql = "
        SELECT 
            up.post_id,
            up.product_name,
            up.rating,
            up.created_at,
            u.name as user_name
        FROM user_posts up
        JOIN users u ON up.user_id = u.user_id
        ORDER BY up.created_at DESC
        LIMIT 3
    ";
    
    echo "<p class='info'>Executing query:</p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll();
    
    echo "<p class='success'>✅ Query executed successfully. Found " . count($posts) . " posts</p>";
    
    if (count($posts) > 0) {
        echo "<p class='info'>Sample posts:</p>";
        echo "<pre>" . htmlspecialchars(print_r($posts, true)) . "</pre>";
    } else {
        echo "<p class='info'>No posts found. You may need to create some posts.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

// Test 4: API Endpoint
echo "<div class='section'>";
echo "<h2>4. API Endpoint Test</h2>";
try {
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/get_feed.php';
    echo "<p class='info'>Testing: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE']
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        echo "<p class='error'>❌ Could not connect to API endpoint</p>";
    } else {
        echo "<p class='success'>✅ API endpoint accessible</p>";
        echo "<p class='info'>Response:</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . (strlen($response) > 500 ? '...' : '') . "</pre>";
        
        $json = json_decode($response, true);
        if ($json === null) {
            echo "<p class='error'>❌ Response is not valid JSON</p>";
        } else {
            echo "<p class='success'>✅ Valid JSON response</p>";
            if (isset($json['success']) && $json['success']) {
                echo "<p class='success'>✅ API returned success</p>";
                echo "<p class='info'>Number of posts: " . (isset($json['posts']) ? count($json['posts']) : 0) . "</p>";
            } else {
                echo "<p class='error'>❌ API returned error: " . htmlspecialchars($json['message'] ?? 'Unknown') . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ API test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 5: Session
echo "<div class='section'>";
echo "<h2>5. Session Information</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ User logged in: " . htmlspecialchars($_SESSION['name']) . " (ID: " . $_SESSION['user_id'] . ")</p>";
} else {
    echo "<p class='info'>ℹ️ No user logged in</p>";
}
echo "</div>";

// Recommendations
echo "<div class='section'>";
echo "<h2>6. Recommendations</h2>";
echo "<ul>";
echo "<li><a href='public/feed.php'>Try opening feed page</a></li>";
echo "<li><a href='api/get_feed.php' target='_blank'>Test API directly in browser</a></li>";
echo "<li><a href='setup_user_posts.php'>Run setup if tables are missing</a></li>";
echo "<li><a href='public/index.php'>Go to homepage</a></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>