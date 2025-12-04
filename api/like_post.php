<?php
/**
 * API: Like/Dislike Post
 * Toggle like/dislike on user posts
 */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like posts.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = intval($data['post_id'] ?? 0);
$like_type = intval($data['like_type'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($post_id <= 0 || ($like_type !== 1 && $like_type !== -1)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

try {
    // Check existing like
    $stmt = $pdo->prepare("SELECT like_id, like_type FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['like_type'] == $like_type) {
            // Remove like (toggle off)
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE like_id = ?");
            $stmt->execute([$existing['like_id']]);
        } else {
            // Update to new type
            $stmt = $pdo->prepare("UPDATE post_likes SET like_type = ? WHERE like_id = ?");
            $stmt->execute([$like_type, $existing['like_id']]);
        }
    } else {
        // New like
        $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id, like_type) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $like_type]);
    }
    
    // Get updated counts
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
            COALESCE(SUM(CASE WHEN like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes
        FROM post_likes 
        WHERE post_id = ?
    ");
    $stmt->execute([$post_id]);
    $counts = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'likes' => (int)$counts['likes'],
        'dislikes' => (int)$counts['dislikes']
    ]);
    
} catch (PDOException $e) {
    error_log("Like Post Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to process like.']);
}
?>