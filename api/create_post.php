<?php
/**
 * API: Create User Post
 * Creates a new product review post
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../functions/analyze_sentiment.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to post.']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$category_id = intval($data['category_id'] ?? 0);
$product_name = trim($data['product_name'] ?? '');
$rating = floatval($data['rating'] ?? 0);
$post_text = trim($data['post_text'] ?? '');
$tags = trim($data['tags'] ?? '');
$is_verified = !empty($data['is_verified']);

// Validation
if (empty($product_name)) {
    echo json_encode(['success' => false, 'message' => 'Product name is required.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}

if (strlen($post_text) < 20) {
    echo json_encode(['success' => false, 'message' => 'Review must be at least 20 characters.']);
    exit;
}

try {
    // Check if user_posts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'User posts feature not installed. Please import database/add_user_posts.sql first.',
            'error_code' => 'TABLE_MISSING'
        ]);
        exit;
    }
    
    // Analyze sentiment
    $sentiment_score = analyze_sentiment($post_text);
    
    // Insert post
    $stmt = $pdo->prepare("
        INSERT INTO user_posts (user_id, category_id, product_name, rating, post_text, tags, sentiment_score, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $category_id > 0 ? $category_id : null,
        $product_name,
        $rating,
        $post_text,
        $tags,
        $sentiment_score,
        $is_verified ? 1 : 0
    ]);
    
    $post_id = $pdo->lastInsertId();
    
    // Update or create community insights
    updateCommunityInsights($pdo, $product_name, $post_text, $sentiment_score);
    
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully!',
        'post_id' => $post_id,
        'sentiment_score' => $sentiment_score
    ]);
    
} catch (PDOException $e) {
    error_log("Create Post Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create post.']);
}

/**
 * Update community insights for a product
 */
function updateCommunityInsights(PDO $pdo, string $product_name, string $post_text, float $sentiment): void {
    try {
        // Extract keywords (simple word frequency)
        $words = extractKeywords($post_text);
        
        // Get existing insights
        $stmt = $pdo->prepare("SELECT * FROM community_insights WHERE product_name = ?");
        $stmt->execute([$product_name]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Merge keywords
            $positive = json_decode($existing['positive_keywords'], true) ?: [];
            $negative = json_decode($existing['negative_keywords'], true) ?: [];
            
            if ($sentiment > 0.3) {
                $positive = array_unique(array_merge($positive, $words));
            } elseif ($sentiment < -0.3) {
                $negative = array_unique(array_merge($negative, $words));
            }
            
            $stmt = $pdo->prepare("
                UPDATE community_insights 
                SET positive_keywords = ?, negative_keywords = ? 
                WHERE product_name = ?
            ");
            $stmt->execute([json_encode(array_slice($positive, 0, 10)), json_encode(array_slice($negative, 0, 10)), $product_name]);
        } else {
            // Create new insights
            $positive = $sentiment > 0.3 ? $words : [];
            $negative = $sentiment < -0.3 ? $words : [];
            
            $stmt = $pdo->prepare("
                INSERT INTO community_insights (product_name, positive_keywords, negative_keywords)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$product_name, json_encode($positive), json_encode($negative)]);
        }
    } catch (Exception $e) {
        error_log("Update Insights Error: " . $e->getMessage());
    }
}

/**
 * Extract meaningful keywords from text
 */
function extractKeywords(string $text): array {
    $text = strtolower($text);
    $text = preg_replace('/[^\p{L}\s]/u', '', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    // Remove common stop words
    $stopWords = ['the', 'is', 'are', 'was', 'were', 'and', 'but', 'for', 'with', 'this', 'that', 'from', 'have', 'has'];
    $words = array_diff($words, $stopWords);
    
    // Get most frequent words
    $frequency = array_count_values($words);
    arsort($frequency);
    
    return array_slice(array_keys($frequency), 0, 5);
}
?>