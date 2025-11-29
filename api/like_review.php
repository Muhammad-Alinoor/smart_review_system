<?php
/**
 * API: Like/Dislike Review
 * Toggles like/dislike for a review
 */

header('Content-Type: application/json');
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like reviews.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$review_id = intval($data['review_id'] ?? 0);
$like_type = intval($data['like_type'] ?? 0); // 1 = like, -1 = dislike
$user_id = $_SESSION['user_id'];

// Validation
if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
    exit;
}

if ($like_type !== 1 && $like_type !== -1) {
    echo json_encode(['success' => false, 'message' => 'Invalid like type.']);
    exit;
}

try {
    // Check if user already liked/disliked this review
    $stmt = $pdo->prepare("SELECT like_id, like_type FROM likes WHERE review_id = ? AND user_id = ?");
    $stmt->execute([$review_id, $user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['like_type'] == $like_type) {
            // Same action - remove the like/dislike (toggle off)
            $stmt = $pdo->prepare("DELETE FROM likes WHERE like_id = ?");
            $stmt->execute([$existing['like_id']]);
            $action = 'removed';
        } else {
            // Different action - update to new type
            $stmt = $pdo->prepare("UPDATE likes SET like_type = ? WHERE like_id = ?");
            $stmt->execute([$like_type, $existing['like_id']]);
            $action = 'updated';
        }
    } else {
        // New like/dislike
        $stmt = $pdo->prepare("INSERT INTO likes (review_id, user_id, like_type) VALUES (?, ?, ?)");
        $stmt->execute([$review_id, $user_id, $like_type]);
        $action = 'added';
    }
    
    // Get updated counts
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
            COALESCE(SUM(CASE WHEN like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes
        FROM likes 
        WHERE review_id = ?
    ");
    $stmt->execute([$review_id]);
    $counts = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => (int)$counts['likes'],
        'dislikes' => (int)$counts['dislikes']
    ]);
    
} catch (PDOException $e) {
    error_log("Like Review Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to process like.']);
}
?>