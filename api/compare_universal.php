<?php
/**
 * API: Universal Comparison
 * Compares any combination of items and posts
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$item_a = $_GET['a'] ?? '';
$item_b = $_GET['b'] ?? '';

// Log for debugging
error_log("Compare Universal: a=$item_a, b=$item_b");

if (empty($item_a) || empty($item_b)) {
    echo json_encode(['success' => false, 'message' => 'Two items required for comparison.']);
    exit;
}

try {
    $comparison = [];
    
    foreach ([$item_a, $item_b] as $identifier) {
        // Parse identifier: format is "type_id" (e.g., "item_5" or "post_12")
        $parts = explode('_', $identifier, 2);
        
        error_log("Parsing identifier: $identifier => " . print_r($parts, true));
        
        if (count($parts) !== 2) {
            echo json_encode([
                'success' => false, 
                'message' => "Invalid item format: $identifier. Expected format: type_id"
            ]);
            exit;
        }
        
        $type = $parts[0];
        $id = intval($parts[1]);
        
        error_log("Type: $type, ID: $id");
        
        if ($id <= 0) {
            echo json_encode([
                'success' => false, 
                'message' => "Invalid ID in: $identifier"
            ]);
            exit;
        }
        
        if ($type === 'item') {
            // Get official item data
            $item = getOfficialItem($pdo, $id);
            if (!$item) {
                echo json_encode(['success' => false, 'message' => "Official item $id not found."]);
                exit;
            }
            $comparison[] = $item;
            
        } elseif ($type === 'post') {
            // Get user post data
            $post = getUserPost($pdo, $id);
            if (!$post) {
                echo json_encode(['success' => false, 'message' => "User post $id not found."]);
                exit;
            }
            $comparison[] = $post;
            
        } else {
            echo json_encode([
                'success' => false, 
                'message' => "Invalid item type: $type. Expected 'item' or 'post'"
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'comparison' => $comparison
    ]);
    
} catch (PDOException $e) {
    error_log("Universal Comparison Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Comparison failed: ' . $e->getMessage()
    ]);
}

/**
 * Get official item data with all stats
 */
function getOfficialItem(PDO $pdo, int $item_id): ?array {
    // Get item details
    $stmt = $pdo->prepare("SELECT * FROM items WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) return null;
    
    // Get review stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as review_count,
            AVG(rating) as avg_rating,
            AVG(sentiment_score) as avg_sentiment
        FROM reviews 
        WHERE item_id = ?
    ");
    $stmt->execute([$item_id]);
    $stats = $stmt->fetch();
    
    // Get score
    $stmt = $pdo->prepare("SELECT score_value FROM scores WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $score_row = $stmt->fetch();
    $score = $score_row ? (float)$score_row['score_value'] : 0;
    
    // Get pros
    $stmt = $pdo->prepare("
        SELECT review_text 
        FROM reviews 
        WHERE item_id = ? AND rating >= 4
        ORDER BY sentiment_score DESC, rating DESC
        LIMIT 3
    ");
    $stmt->execute([$item_id]);
    $pros = array_map(function($r) {
        return substr($r['review_text'], 0, 100) . (strlen($r['review_text']) > 100 ? '...' : '');
    }, $stmt->fetchAll());
    
    // Get cons
    $stmt = $pdo->prepare("
        SELECT review_text 
        FROM reviews 
        WHERE item_id = ? AND rating <= 2
        ORDER BY sentiment_score ASC, rating ASC
        LIMIT 3
    ");
    $stmt->execute([$item_id]);
    $cons = array_map(function($r) {
        return substr($r['review_text'], 0, 100) . (strlen($r['review_text']) > 100 ? '...' : '');
    }, $stmt->fetchAll());
    
    return [
        'type' => 'item',
        'id' => $item['item_id'],
        'title' => $item['title'],
        'description' => $item['description'],
        'rating' => round((float)$stats['avg_rating'], 1),
        'sentiment' => round((float)$stats['avg_sentiment'], 2),
        'score' => round($score, 1),
        'review_count' => (int)$stats['review_count'],
        'metadata' => json_decode($item['metadata_json'], true),
        'pros' => $pros,
        'cons' => $cons
    ];
}

/**
 * Get user post data with all stats
 */
function getUserPost(PDO $pdo, int $post_id): ?array {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        return null;
    }
    
    // Get post details
    $stmt = $pdo->prepare("
        SELECT 
            up.*,
            u.name as user_name,
            c.name as category_name
        FROM user_posts up
        JOIN users u ON up.user_id = u.user_id
        LEFT JOIN categories c ON up.category_id = c.category_id
        WHERE up.post_id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) return null;
    
    // Get likes/dislikes
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
            COALESCE(SUM(CASE WHEN like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes
        FROM post_likes 
        WHERE post_id = ?
    ");
    $stmt->execute([$post_id]);
    $likes = $stmt->fetch();
    
    // Get comment count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_comments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $comment_count = (int)$stmt->fetchColumn();
    
    // Build metadata
    $metadata = [
        'Category' => $post['category_name'] ?? 'N/A',
        'Author' => $post['user_name']
    ];
    
    if ($post['is_verified']) {
        $metadata['Verified Purchase'] = 'Yes';
    }
    
    return [
        'type' => 'post',
        'id' => $post['post_id'],
        'title' => $post['product_name'],
        'description' => $post['post_text'],
        'rating' => (float)$post['rating'],
        'sentiment' => (float)$post['sentiment_score'],
        'review_count' => $comment_count,
        'metadata' => $metadata,
        'tags' => $post['tags'],
        'likes' => (int)$likes['likes'],
        'dislikes' => (int)$likes['dislikes'],
        'user_name' => $post['user_name'],
        'created_at' => $post['created_at'],
        'is_verified' => (bool)$post['is_verified']
    ];
}
?>