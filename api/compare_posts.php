<?php
/**
 * API: Compare User Posts
 * Returns side-by-side comparison of two user reviews
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$post_a = intval($_GET['a'] ?? 0);
$post_b = intval($_GET['b'] ?? 0);

if ($post_a <= 0 || $post_b <= 0) {
    echo json_encode(['success' => false, 'message' => 'Two valid post IDs required.']);
    exit;
}

if ($post_a === $post_b) {
    echo json_encode(['success' => false, 'message' => 'Please select two different posts.']);
    exit;
}

try {
    // Check if user_posts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'User posts feature not available.']);
        exit;
    }
    
    $comparison = [];
    
    foreach ([$post_a, $post_b] as $post_id) {
        // Get post details
        $stmt = $pdo->prepare("
            SELECT 
                up.*,
                u.name as user_name,
                c.name as category_name
            FROM user_posts up
            JOIN users u ON up.user_id = u.user_id
            LEFT JOIN categories c ON up.category_id = c.category_id
            WHERE up.post_id = ?
        ");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => "Post $post_id not found."]);
            exit;
        }
        
        // Convert types
        $post['rating'] = (float)$post['rating'];
        $post['sentiment_score'] = (float)$post['sentiment_score'];
        $post['is_verified'] = (bool)$post['is_verified'];
        
        // Get like counts
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
                COALESCE(SUM(CASE WHEN like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes
            FROM post_likes 
            WHERE post_id = ?
        ");
        $stmt->execute([$post_id]);
        $likes = $stmt->fetch();
        $post['likes'] = (int)$likes['likes'];
        $post['dislikes'] = (int)$likes['dislikes'];
        
        // Get comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $post['comment_count'] = (int)$stmt->fetchColumn();
        
        $comparison[] = $post;
    }
    
    echo json_encode([
        'success' => true,
        'comparison' => $comparison
    ]);
    
} catch (PDOException $e) {
    error_log("Compare Posts Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Comparison failed.']);
}
?>