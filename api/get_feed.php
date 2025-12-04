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
    // Build query based on filters
    $where = "1=1";
    $params = [];
    
    if ($category_id > 0) {
        $where .= " AND up.category_id = ?";
        $params[] = $category_id;
    }
    
    // Sort order
    $orderBy = match($sort) {
        'popular' => 'ORDER BY (likes - dislikes + comment_count) DESC',
        'rating' => 'ORDER BY up.rating DESC',
        default => 'ORDER BY up.created_at DESC'
    };
    
    // Get posts with aggregated data
    $sql = "
        SELECT 
            up.*,
            u.name as user_name,
            c.name as category_name,
            CONCAT(pc.name, ' > ', c.name) as category_path,
            COALESCE(SUM(CASE WHEN pl.like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
            COALESCE(SUM(CASE WHEN pl.like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = up.post_id) as comment_count,
            (SELECT COUNT(*) FROM user_posts WHERE product_name = up.product_name AND post_id != up.post_id) as similar_count,
            ci.common_praise as insight_praise,
            ci.common_complaints as insight_complaint,
            (ci.insight_id IS NOT NULL) as has_insights
        FROM user_posts up
        JOIN users u ON up.user_id = u.user_id
        LEFT JOIN categories c ON up.category_id = c.category_id
        LEFT JOIN categories pc ON c.parent_id = pc.category_id
        LEFT JOIN post_likes pl ON up.post_id = pl.post_id
        LEFT JOIN community_insights ci ON up.product_name = ci.product_name
        WHERE $where
        GROUP BY up.post_id
        $orderBy
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    // Check user's like status if logged in
    if (isset($_SESSION['user_id'])) {
        foreach ($posts as &$post) {
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
    echo json_encode(['success' => false, 'message' => 'Failed to load feed.']);
}
?>