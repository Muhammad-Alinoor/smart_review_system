<?php
/**
 * API: Get Reviews
 * Returns reviews for an item with comments and like counts
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$item_id = intval($_GET['item_id'] ?? 0);

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

try {
    // Get reviews with user info and like counts
    $stmt = $pdo->prepare("
        SELECT 
            r.review_id,
            r.rating,
            r.review_text,
            r.sentiment_score,
            r.created_at,
            r.user_id,
            u.name as user_name,
            COALESCE(SUM(CASE WHEN l.like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
            COALESCE(SUM(CASE WHEN l.like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes,
            (SELECT COUNT(*) FROM comments WHERE review_id = r.review_id) as comment_count
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        LEFT JOIN likes l ON r.review_id = l.review_id
        WHERE r.item_id = ?
        GROUP BY r.review_id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$item_id]);
    $reviews = $stmt->fetchAll();
    
    // Get comments for each review
    foreach ($reviews as &$review) {
        $stmt = $pdo->prepare("
            SELECT 
                c.comment_id,
                c.comment_text,
                c.created_at,
                u.name as user_name
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.review_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$review['review_id']]);
        $review['comments'] = $stmt->fetchAll();
        
        // Check if current user liked/disliked this review
        $review['user_like'] = 0;
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT like_type FROM likes WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$review['review_id'], $_SESSION['user_id']]);
            $like = $stmt->fetch();
            if ($like) {
                $review['user_like'] = (int)$like['like_type'];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
    
} catch (PDOException $e) {
    error_log("Get Reviews Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch reviews.']);
}
