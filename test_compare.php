<?php
/**
 * Test Comparison Feature
 * Debug tool to verify comparison is working
 */

require_once 'config/db.php';

echo "<!DOCTYPE html><html><head><title>Test Comparison</title>
<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
.success { color: #27ae60; }
.error { color: #e74c3c; }
.info { color: #3498db; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
.test-btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
</style>
</head><body>";

echo "<h1>🧪 Comparison Feature Test</h1>";

// Test 1: Check available items
echo "<div class='section'>";
echo "<h2>1. Available Items</h2>";

// Official items
$stmt = $pdo->query("SELECT item_id, title FROM items LIMIT 3");
$items = $stmt->fetchAll();
echo "<h3>Official Items:</h3>";
foreach ($items as $item) {
    echo "<p class='info'>item_{$item['item_id']}: {$item['title']}</p>";
}

// User posts (if exists)
$stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
if ($stmt->rowCount() > 0) {
    $stmt = $pdo->query("SELECT post_id, product_name FROM user_posts LIMIT 3");
    $posts = $stmt->fetchAll();
    echo "<h3>User Posts:</h3>";
    foreach ($posts as $post) {
        echo "<p class='info'>post_{$post['post_id']}: {$post['product_name']}</p>";
    }
} else {
    echo "<p class='error'>❌ User posts table not found. Run setup_user_posts.php</p>";
}
echo "</div>";

// Test 2: Test API directly
echo "<div class='section'>";
echo "<h2>2. Test Comparison API</h2>";

if (!empty($items) && count($items) >= 2) {
    $item1 = $items[0];
    $item2 = $items[1];
    
    $test_url = "api/compare_universal.php?a=item_{$item1['item_id']}&b=item_{$item2['item_id']}";
    echo "<p class='info'>Test URL: <code>$test_url</code></p>";
    echo "<button class='test-btn' onclick=\"testAPI('$test_url')\">Test Item vs Item</button>";
    
    if (!empty($posts) && count($posts) >= 2) {
        $post1 = $posts[0];
        $post2 = $posts[1];
        
        $test_url2 = "api/compare_universal.php?a=post_{$post1['post_id']}&b=post_{$post2['post_id']}";
        echo "<button class='test-btn' onclick=\"testAPI('$test_url2')\">Test Post vs Post</button>";
        
        $test_url3 = "api/compare_universal.php?a=item_{$item1['item_id']}&b=post_{$post1['post_id']}";
        echo "<button class='test-btn' onclick=\"testAPI('$test_url3')\">Test Item vs Post</button>";
    }
}

echo "<div id='apiResult'></div>";
echo "</div>";

// Test 3: Test full comparison page
echo "<div class='section'>";
echo "<h2>3. Test Comparison Pages</h2>";

if (!empty($items) && count($items) >= 2) {
    $item1 = $items[0];
    $item2 = $items[1];
    
    $page_url = "public/compare_universal.php?a=item_{$item1['item_id']}&b=item_{$item2['item_id']}";
    echo "<p><a href='$page_url' target='_blank' class='test-btn'>Open: {$item1['title']} vs {$item2['title']}</a></p>";
    
    if (!empty($posts) && count($posts) >= 2) {
        $post1 = $posts[0];
        $post2 = $posts[1];
        
        $page_url2 = "public/compare_universal.php?a=post_{$post1['post_id']}&b=post_{$post2['post_id']}";
        echo "<p><a href='$page_url2' target='_blank' class='test-btn'>Open: {$post1['product_name']} vs {$post2['product_name']}</a></p>";
    }
}
echo "</div>";

// Test 4: Search and compare flow
echo "<div class='section'>";
echo "<h2>4. Full User Flow Test</h2>";
echo "<ol>";
echo "<li>Go to <a href='public/index.php'>Homepage</a></li>";
echo "<li>Search for 'Samsung'</li>";
echo "<li>Check 2 items to compare</li>";
echo "<li>Click 'Compare Selected' button</li>";
echo "<li>Should see comparison page</li>";
echo "</ol>";
echo "<p><a href='public/index.php' class='test-btn'>Start Test</a></p>";
echo "</div>";

echo "<script>
async function testAPI(url) {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<p>Testing: ' + url + '</p><p>Loading...</p>';
    
    try {
        const response = await fetch(url);
        const text = await response.text();
        
        resultDiv.innerHTML = '<h3>Response:</h3><pre>' + text + '</pre>';
        
        try {
            const json = JSON.parse(text);
            if (json.success) {
                resultDiv.innerHTML += '<p class=\"success\">✅ API call successful!</p>';
            } else {
                resultDiv.innerHTML += '<p class=\"error\">❌ API returned error: ' + json.message + '</p>';
            }
        } catch (e) {
            resultDiv.innerHTML += '<p class=\"error\">❌ Response is not valid JSON</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p class=\"error\">❌ API call failed: ' + error.message + '</p>';
    }
}
</script>";

echo "</body></html>";
?>