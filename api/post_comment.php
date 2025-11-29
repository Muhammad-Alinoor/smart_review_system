<?php
/**
 * API: Post Comment
 * Adds a comment to a review
 */

header('Content-Type: application/json');
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$review_id = intval($data['review_id'] ?? 0);
$comment_text = trim($data['comment_text'] ?? '');
$user_id = $_SESSION['user_id'];

// Validation
if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
    exit;
}

if (empty($comment_text) || strlen($comment_text) < 2) {
    echo json_encode(['success' => false, 'message' => 'Comment must be at least 2 characters.']);
    exit;
}

try {
    // Check if review exists
    $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Review not found.']);
        exit;
    }
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (review_id, user_id, comment_text) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$review_id, $user_id, $comment_text]);
    
    $comment_id = $pdo->lastInsertId();
    
    // Get the inserted comment with user info
    $stmt = $pdo->prepare("
        SELECT c.comment_id, c.comment_text, c.created_at, u.name as user_name
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.comment_id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment posted successfully!',
        'comment' => $comment
    ]);
    
} catch (PDOException $e) {
    error_log("Post Comment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to post comment.']);
}
?>