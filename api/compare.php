<?php
/**
 * API: Compare Items
 * Returns side-by-side comparison of two items
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../functions/compute_score.php';

$item_a = intval($_GET['a'] ?? 0);
$item_b = intval($_GET['b'] ?? 0);

if ($item_a <= 0 || $item_b <= 0) {
    echo json_encode(['success' => false, 'message' => 'Two valid item IDs required.']);
    exit;
}

if ($item_a === $item_b) {
    echo json_encode(['success' => false, 'message' => 'Please select two different items.']);
    exit;
}

try {
    $comparison = [];
    
    foreach ([$item_a, $item_b] as $item_id) {
        // Get item details
        $stmt = $pdo->prepare("
            SELECT item_id, title, description, metadata_json
            FROM items 
            WHERE item_id = ?
        ");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => "Item $item_id not found."]);
            exit;
        }
        
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
        $score = get_or_compute_score($pdo, $item_id);
        
        // Get top pros (positive reviews with rating >= 4)
        $stmt = $pdo->prepare("
            SELECT review_text, rating, sentiment_score
            FROM reviews 
            WHERE item_id = ? AND rating >= 4
            ORDER BY sentiment_score DESC, rating DESC
            LIMIT 3
        ");
        $stmt->execute([$item_id]);
        $pros = $stmt->fetchAll();
        
        // Get top cons (negative reviews with rating <= 2)
        $stmt = $pdo->prepare("
            SELECT review_text, rating, sentiment_score
            FROM reviews 
            WHERE item_id = ? AND rating <= 2
            ORDER BY sentiment_score ASC, rating ASC
            LIMIT 3
        ");
        $stmt->execute([$item_id]);
        $cons = $stmt->fetchAll();
        
        // Parse metadata
        $metadata = json_decode($item['metadata_json'], true);
        
        $comparison[] = [
            'item_id' => $item['item_id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'metadata' => $metadata,
            'score' => round($score, 2),
            'avg_rating' => round((float)$stats['avg_rating'], 1),
            'avg_sentiment' => round((float)$stats['avg_sentiment'], 2),
            'review_count' => (int)$stats['review_count'],
            'pros' => array_map(function($p) {
                return substr($p['review_text'], 0, 100) . (strlen($p['review_text']) > 100 ? '...' : '');
            }, $pros),
            'cons' => array_map(function($c) {
                return substr($c['review_text'], 0, 100) . (strlen($c['review_text']) > 100 ? '...' : '');
            }, $cons)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparison' => $comparison
    ]);
    
} catch (PDOException $e) {
    error_log("Compare Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Comparison failed.']);
}
?>