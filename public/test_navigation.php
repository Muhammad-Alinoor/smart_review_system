<?php
/**
 * Test Navigation
 * Quick test to verify file paths
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Navigation Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .link-box { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .status { padding: 5px 10px; border-radius: 3px; margin-left: 10px; }
        .exists { background: #d4edda; color: #155724; }
        .missing { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Navigation Test</h1>
    <p>Current location: <?php echo __FILE__; ?></p>
    <p>Current URL: <?php echo $_SERVER['REQUEST_URI']; ?></p>
    
    <h2>File Existence Check:</h2>
    
    <?php
    $files = [
        'feed.php' => 'Review Feed',
        'create_post.php' => 'Create Post Page',
        'post_view.php' => 'Post View Page',
        '../api/create_post.php' => 'Create Post API',
        '../api/get_feed.php' => 'Get Feed API'
    ];
    
    foreach ($files as $file => $name) {
        $exists = file_exists($file);
        $status = $exists ? 'exists' : 'missing';
        $icon = $exists ? '✅' : '❌';
        
        echo "<div class='link-box'>";
        echo "<strong>$name</strong>: <code>$file</code> ";
        echo "<span class='status $status'>$icon " . ($exists ? 'EXISTS' : 'MISSING') . "</span>";
        
        if ($exists && strpos($file, '.php') !== false && !strpos($file, 'api')) {
            echo "<br><a href='$file' target='_blank'>Test Link</a>";
        }
        echo "</div>";
    }
    ?>
    
    <h2>Test Navigation Links:</h2>
    <div class="link-box">
        <a href="index.php">← Back to Homepage</a>
    </div>
    <div class="link-box">
        <a href="feed.php">Go to Feed</a>
    </div>
    <div class="link-box">
        <a href="create_post.php">Go to Create Post</a>
    </div>
</body>
</html>