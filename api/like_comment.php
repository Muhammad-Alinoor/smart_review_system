<?php
/**
 * API: Like Comment
 */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$comment_id = intval($data['comment_id'] ?? 0);
$user_id = $_SESSION['user_id'];

try {
    // Check if already liked
    $stmt = $pdo->prepare("SELECT like_id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);
    
    if ($stmt->fetch()) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
        $stmt->execute([$comment_id, $user_id]);
    }
    
    // Get new count
    $stmt = $pdo->prepare("SELECT COUNT(*) as likes FROM comment_likes WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $result = $stmt->fetch();
    
    echo json_encode(['success' => true, 'likes' => (int)$result['likes']]);
    
} catch (PDOException $e) {
    error_log("Like Comment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to like comment.']);
}
?>