<?php
/**
 * API: Post Review
 * Accepts item_id, rating, review_text
 * Computes sentiment and updates item score
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../functions/analyze_sentiment.php';
require_once '../functions/compute_score.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to post a review.']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$item_id = intval($data['item_id'] ?? 0);
$rating = intval($data['rating'] ?? 0);
$review_text = trim($data['review_text'] ?? '');
$user_id = $_SESSION['user_id'];

// Validation
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}

if (empty($review_text) || strlen($review_text) < 10) {
    echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters.']);
    exit;
}

try {
    // Check if item exists
    $stmt = $pdo->prepare("SELECT item_id FROM items WHERE item_id = ?");
    $stmt->execute([$item_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit;
    }
    
    // Analyze sentiment
    $sentiment_score = analyze_sentiment($review_text);
    
    // Insert review
    $stmt = $pdo->prepare("
        INSERT INTO reviews (item_id, user_id, rating, review_text, sentiment_score) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$item_id, $user_id, $rating, $review_text, $sentiment_score]);
    
    $review_id = $pdo->lastInsertId();
    
    // Recompute item score
    $new_score = compute_score($pdo, $item_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Review posted successfully!',
        'review_id' => $review_id,
        'sentiment_score' => $sentiment_score,
        'item_score' => $new_score
    ]);
    
} catch (PDOException $e) {
    error_log("Post Review Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to post review.']);
}
