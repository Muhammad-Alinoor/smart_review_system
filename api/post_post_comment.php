<?php
/**
 * API: Post Comment on User Post
 */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = intval($data['post_id'] ?? 0);
$comment_text = trim($data['comment_text'] ?? '');
$rating = isset($data['rating']) && $data['rating'] !== '' ? floatval($data['rating']) : null;
$user_id = $_SESSION['user_id'];

if ($post_id <= 0 || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Invalid comment data.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO post_comments (post_id, user_id, comment_text, rating)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$post_id, $user_id, $comment_text, $rating]);
    
    echo json_encode(['success' => true, 'message' => 'Comment posted!']);
    
} catch (PDOException $e) {
    error_log("Post Comment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to post comment.']);
}
?>