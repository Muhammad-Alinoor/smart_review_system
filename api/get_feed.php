<?php
/**
 * API: Get Feed
 * Returns paginated list of user posts
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$category_id = intval($_GET['category'] ?? 0);
$sort = $_GET['sort'] ?? 'recent';
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // First check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'User posts feature not installed. Please run setup_user_posts.php'
        ]);
        exit;
    }

    // Build WHERE clause
    $where = "1=1";
    $params = [];
    
    if ($category_id > 0) {
        $where .= " AND up.category_id = ?";
        $params[] = $category_id;
    }
    
    // Sort order
    $orderBy = match($sort) {
        'popular' => 'ORDER BY (COALESCE(likes, 0) - COALESCE(dislikes, 0) + COALESCE(comment_count, 0)) DESC',
        'rating' => 'ORDER BY up.rating DESC',
        default => 'ORDER BY up.created_at DESC'
    };
    
    // Simplified query without complex joins
    $sql = "
        SELECT 
            up.post_id,
            up.user_id,
            up.category_id,
            up.product_name,
            up.rating,
            up.post_text,
            up.tags,
            up.sentiment_score,
            up.is_verified,
            up.created_at,
            u.name as user_name,
            c.name as category_name
        FROM user_posts up
        JOIN users u ON up.user_id = u.user_id
        LEFT JOIN categories c ON up.category_id = c.category_id
        WHERE $where
        $orderBy
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    // Get additional data for each post
    foreach ($posts as &$post) {
        // Convert rating to float
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
        $stmt->execute([$post['post_id']]);
        $likes = $stmt->fetch();
        $post['likes'] = (int)$likes['likes'];
        $post['dislikes'] = (int)$likes['dislikes'];
        
        // Get comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_comments WHERE post_id = ?");
        $stmt->execute([$post['post_id']]);
        $post['comment_count'] = (int)$stmt->fetchColumn();
        
        // Get category path
        if ($post['category_id']) {
            $stmt = $pdo->prepare("
                SELECT c.name, pc.name as parent_name
                FROM categories c
                LEFT JOIN categories pc ON c.parent_id = pc.category_id
                WHERE c.category_id = ?
            ");
            $stmt->execute([$post['category_id']]);
            $cat = $stmt->fetch();
            if ($cat) {
                $post['category_path'] = $cat['parent_name'] ? $cat['parent_name'] . ' > ' . $cat['name'] : $cat['name'];
            }
        }
        
        // Get insights
        $stmt = $pdo->prepare("SELECT common_praise, common_complaints FROM community_insights WHERE product_name = ?");
        $stmt->execute([$post['product_name']]);
        $insight = $stmt->fetch();
        $post['has_insights'] = $insight ? true : false;
        $post['insight_praise'] = $insight['common_praise'] ?? null;
        $post['insight_complaint'] = $insight['common_complaints'] ?? null;
        
        // Similar count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_posts WHERE product_name LIKE ? AND post_id != ?");
        $stmt->execute(['%' . $post['product_name'] . '%', $post['post_id']]);
        $post['similar_count'] = (int)$stmt->fetchColumn();
        
        // Check user's like status if logged in
        $post['user_like'] = 0;
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT like_type FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post['post_id'], $_SESSION['user_id']]);
            $like = $stmt->fetch();
            $post['user_like'] = $like ? (int)$like['like_type'] : 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts,
        'page' => $page,
        'has_more' => count($posts) === $limit
    ]);
    
} catch (PDOException $e) {
    error_log("Get Feed Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to load feed.',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>