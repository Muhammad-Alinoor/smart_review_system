<?php
/**
 * API: Search Items
 * Full-text search on items, returns top results sorted by score
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../functions/compute_score.php';

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query required.']);
    exit;
}

try {
    // Log search query
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO search_logs (query, user_id) VALUES (?, ?)");
        $stmt->execute([$query, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO search_logs (query) VALUES (?)");
        $stmt->execute([$query]);
    }
    
    // Search items by title and description
    $search_term = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT 
            i.item_id,
            i.title,
            i.description,
            i.metadata_json,
            COALESCE(s.score_value, 0) as score,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.review_id) as review_count
        FROM items i
        LEFT JOIN reviews r ON i.item_id = r.item_id
        LEFT JOIN scores s ON i.item_id = s.item_id
        WHERE i.title LIKE ? OR i.description LIKE ?
        GROUP BY i.item_id
        ORDER BY score DESC, avg_rating DESC
        LIMIT 10
    ");
    $stmt->execute([$search_term, $search_term]);
    $results = $stmt->fetchAll();
    
    // Update scores if they're stale (older than 1 hour)
    foreach ($results as &$item) {
        $item['score'] = get_or_compute_score($pdo, $item['item_id']);
        $item['avg_rating'] = round((float)$item['avg_rating'], 1);
        
        // Parse metadata JSON
        if (!empty($item['metadata_json'])) {
            $item['metadata'] = json_decode($item['metadata_json'], true);
            unset($item['metadata_json']);
        }
    }
    
    // Re-sort by updated scores
    usort($results, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (PDOException $e) {
    error_log("Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search failed.']);
}
?>