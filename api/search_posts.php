<?php
/**
 * API: Search User Posts
 * Search specifically in user posts by product name, tags, or content
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$query = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all, product, tags, content

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query required.']);
    exit;
}

try {
    // Check if user_posts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'User posts feature not available.']);
        exit;
    }
    
    $search_term = '%' . $query . '%';
    
    // Build WHERE clause based on filter
    $whereClause = match($filter) {
        'product' => 'up.product_name LIKE ?',
        'tags' => 'up.tags LIKE ?',
        'content' => 'up.post_text LIKE ?',
        default => 'up.product_name LIKE ? OR up.post_text LIKE ? OR up.tags LIKE ?'
    };
    
    // Build params array
    $params = [];
    if ($filter === 'all') {
        $params = [$search_term, $search_term, $search_term];
    } else {
        $params = [$search_term];
    }
    
    $sql = "
        SELECT 
            up.post_id,
            up.product_name,
            up.rating,
            up.post_text,
            up.tags,
            up.is_verified,
            up.sentiment_score,
            up.created_at,
            u.name as user_name,
            c.name as category_name,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = up.post_id) as comment_count,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = up.post_id AND like_type = 1) as likes,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = up.post_id AND like_type = -1) as dislikes
        FROM user_posts up
        JOIN users u ON up.user_id = u.user_id
        LEFT JOIN categories c ON up.category_id = c.category_id
        WHERE $whereClause
        ORDER BY up.created_at DESC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Format results
    foreach ($results as &$post) {
        $post['rating'] = (float)$post['rating'];
        $post['sentiment_score'] = (float)$post['sentiment_score'];
        $post['comment_count'] = (int)$post['comment_count'];
        $post['likes'] = (int)$post['likes'];
        $post['dislikes'] = (int)$post['dislikes'];
        
        // Truncate post text for results
        if (strlen($post['post_text']) > 200) {
            $post['post_text_preview'] = substr($post['post_text'], 0, 200) . '...';
        } else {
            $post['post_text_preview'] = $post['post_text'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'filter' => $filter,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (PDOException $e) {
    error_log("Search Posts Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search failed.']);
}
?>